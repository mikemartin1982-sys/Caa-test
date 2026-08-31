package com.caa.platform.integration.qbo;

import com.caa.platform.client.Client;
import com.caa.platform.session.Session;
import com.caa.platform.session.SessionAuthorizedClient;
import com.caa.platform.session.SessionAuthorizedClientRepository;
import com.caa.platform.session.SessionRepository;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.http.ResponseEntity;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.web.util.UriComponentsBuilder;

import java.time.OffsetDateTime;
import java.util.List;
import java.util.Map;

/**
 * Michael, 2026-08-25 -- QBO integration, layer 6 (Invoice
 * generation). Confirmed with Michael: TODAY's scope is deliberately
 * narrow -- one flat-fee line for a PRIVATE session (Session.quotedPrice),
 * proving the core mechanism (real Customer, real Class, real Item,
 * real Invoice) end to end. Public sessions, with a line per student
 * or per component, are explicitly a separate, later piece -- this
 * service is not built to handle that shape yet.
 *
 * Billing target: confirmed with Michael as "whomever we have set in
 * the Invoice reference fields on a client page" -- billingContactName/
 * billingEmail if set, falling back to the primary contact
 * (firstName+lastName/email) otherwise. For a Private session
 * specifically, the BILLING CLIENT itself is resolved from the
 * session's own HOST (SessionAuthorizedClient.isHost=true) -- there's
 * no Enrollment to derive it from at all for a session with no
 * enrolled students, which Session 6 (today's test case) genuinely
 * has none of.
 */
@Service
public class QboInvoiceService {

    private static final Logger log = LoggerFactory.getLogger(QboInvoiceService.class);
    private static final String PRIVATE_SESSION_ITEM_NAME = "Private Session Fee";

    private final QboApiClient apiClient;
    private final SessionRepository sessionRepository;
    private final SessionAuthorizedClientRepository authorizedClientRepository;
    private final QboCustomerSyncService customerSyncService;

    public QboInvoiceService(QboApiClient apiClient, SessionRepository sessionRepository,
                              SessionAuthorizedClientRepository authorizedClientRepository,
                              QboCustomerSyncService customerSyncService) {
        this.apiClient = apiClient;
        this.sessionRepository = sessionRepository;
        this.authorizedClientRepository = authorizedClientRepository;
        this.customerSyncService = customerSyncService;
    }

    /**
     * Michael, 2026-08-25 -- today's scope only: one flat-fee line for
     * a Private session's quotedPrice. Requires the session already
     * have a qboClassRefId (from publish()) -- doesn't attempt to sync
     * the Class itself here, since Class sync is tied specifically to
     * the publish action, not invoice generation.
     */
    @Transactional
    public Map<String, Object> generatePrivateSessionInvoice(Long sessionId) {
        Session session = sessionRepository.findById(sessionId)
                .orElseThrow(() -> new IllegalArgumentException("Session not found: " + sessionId));

        if (session.getSchoolType() != com.caa.platform.session.SchoolType.PRIVATE) {
            throw new IllegalStateException(
                    "This endpoint only handles PRIVATE sessions today -- Public/per-student invoicing is a separate, later piece.");
        }
        if (session.getQuotedPrice() == null) {
            throw new IllegalStateException("Session " + sessionId + " has no quotedPrice set.");
        }
        if (session.getQboClassRefId() == null || session.getQboClassRefId().isBlank()) {
            throw new IllegalStateException(
                    "Session " + sessionId + " has no qboClassRefId -- publish the session first (or retry /sync-qbo-class) before generating an invoice.");
        }

        Client host = authorizedClientRepository.findBySessionIdAndIsHostTrue(sessionId)
                .map(SessionAuthorizedClient::getClient)
                .orElseThrow(() -> new IllegalStateException("Session " + sessionId + " has no host client -- cannot determine who to bill."));

        String qboCustomerId = customerSyncService.syncCustomer(host);
        String itemId = findOrCreateItem(PRIVATE_SESSION_ITEM_NAME);

        // Michael, 2026-08-25 -- "we copy over whatever we put in the
        // client contact field unless they tell us otherwise" --
        // confirmed billing-contact fallback behavior.
        String billingEmail = host.getBillingEmail() != null ? host.getBillingEmail() : host.getEmail();

        Map<String, Object> lineItem = Map.of(
                "Amount", session.getQuotedPrice(),
                "Description", session.getLocationName() != null ? session.getLocationName() : "Private Session",
                "DetailType", "SalesItemLineDetail",
                "SalesItemLineDetail", Map.of(
                        "ItemRef", Map.of("value", itemId),
                        "ClassRef", Map.of("value", session.getQboClassRefId()),
                        "Qty", 1,
                        "UnitPrice", session.getQuotedPrice()
                )
        );

        java.util.Map<String, Object> invoicePayload = new java.util.HashMap<>();
        invoicePayload.put("CustomerRef", Map.of("value", qboCustomerId));
        invoicePayload.put("Line", List.of(lineItem));
        if (billingEmail != null && !billingEmail.isBlank()) {
            invoicePayload.put("BillEmail", Map.of("Address", billingEmail));
        }

        ResponseEntity<Map> response = apiClient.post("invoice", invoicePayload);
        Map<String, Object> body = response.getBody();
        if (body == null) {
            throw new IllegalStateException("QuickBooks Invoice creation returned an empty response.");
        }
        Map<String, Object> invoice = (Map<String, Object>) body.get("Invoice");
        if (invoice == null) {
            throw new IllegalStateException("QuickBooks Invoice creation response did not include the new Invoice.");
        }

        session.setLastQboInvoiceGeneratedAt(OffsetDateTime.now());
        session.setLastQboInvoiceSentNumber((String) invoice.get("DocNumber"));
        sessionRepository.save(session);

        log.info("Created QBO Invoice for session {} (Invoice Id={}, DocNumber={})", sessionId, invoice.get("Id"), invoice.get("DocNumber"));
        return invoice;
    }

    /**
     * Michael, 2026-08-25 -- same match-before-create principle as
     * Customer/Class sync. QBO requires a Service-type Item to
     * reference a real IncomeAccountRef on creation (confirmed against
     * Intuit's own docs) -- rather than also building Account
     * creation, which is genuinely out of scope for today, this
     * resolves the first existing Income-type account already present
     * in the company (every QBO company, including a fresh sandbox,
     * ships with default chart-of-accounts entries).
     */
    @SuppressWarnings("unchecked")
    private String findOrCreateItem(String name) {
        String escapedName = name.replace("'", "''");
        String query = "SELECT * FROM Item WHERE Name = '" + escapedName + "'";
        String path = UriComponentsBuilder.fromPath("query").queryParam("query", query).build().toUriString();

        ResponseEntity<Map> response = apiClient.get(path);
        Map<String, Object> body = response.getBody();
        if (body != null) {
            Map<String, Object> queryResponse = (Map<String, Object>) body.get("QueryResponse");
            if (queryResponse != null) {
                List<Map<String, Object>> items = (List<Map<String, Object>>) queryResponse.get("Item");
                if (items != null && !items.isEmpty()) {
                    return (String) items.get(0).get("Id");
                }
            }
        }

        String incomeAccountId = findIncomeAccount();

        Map<String, Object> payload = Map.of(
                "Name", name,
                "Type", "Service",
                "IncomeAccountRef", Map.of("value", incomeAccountId)
        );
        ResponseEntity<Map> createResponse = apiClient.post("item", payload);
        Map<String, Object> createBody = createResponse.getBody();
        if (createBody == null) {
            throw new IllegalStateException("QuickBooks Item creation returned an empty response.");
        }
        Map<String, Object> item = (Map<String, Object>) createBody.get("Item");
        if (item == null) {
            throw new IllegalStateException("QuickBooks Item creation response did not include the new Item.");
        }
        log.info("Created new QBO Item '{}' (Id={})", name, item.get("Id"));
        return (String) item.get("Id");
    }

    @SuppressWarnings("unchecked")
    private String findIncomeAccount() {
        String path = UriComponentsBuilder.fromPath("query")
                .queryParam("query", "SELECT * FROM Account WHERE AccountType = 'Income'")
                .build()
                .toUriString();
        ResponseEntity<Map> response = apiClient.get(path);
        Map<String, Object> body = response.getBody();
        if (body != null) {
            Map<String, Object> queryResponse = (Map<String, Object>) body.get("QueryResponse");
            if (queryResponse != null) {
                List<Map<String, Object>> accounts = (List<Map<String, Object>>) queryResponse.get("Account");
                if (accounts != null && !accounts.isEmpty()) {
                    return (String) accounts.get(0).get("Id");
                }
            }
        }
        throw new IllegalStateException("No existing Income account found in this QBO company -- required to create a new Item.");
    }
}
