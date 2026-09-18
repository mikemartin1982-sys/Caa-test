package com.caa.platform.integration.qbo;

import com.caa.platform.client.Client;
import com.caa.platform.enrollment.Enrollment;
import com.caa.platform.enrollment.EnrollmentComponents;
import com.caa.platform.enrollment.EnrollmentRepository;
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
 * generation) for PRIVATE sessions. Public sessions, with a line per
 * student or per component, are a separate, later piece -- this
 * service is not built to handle that shape (see
 * QboPublicSessionInvoiceService instead).
 *
 * Michael, 2026-09-02 -- found live: the real, confirmed overage rule
 * ("covers up to the negotiated, per-deal number of field attendees --
 * Session.bidNumFieldAttendees, commonly 15 but sometimes genuinely
 * more -- $275/person over that, Session.fieldTest") was never
 * actually applied here -- only the flat base fee was ever billed,
 * meaning any session that genuinely exceeded its own included
 * headcount had been under-billed since this feature was built.
 * Fixed -- a second, conditional line is now added when field
 * attendance (FIELD_ONLY enrollments, outsideAttendee excluded)
 * exceeds the session's own real, negotiated included headcount, not
 * a fixed, universal number.
 *
 * Billing target: confirmed with Michael as "whomever we have set in
 * the Invoice reference fields on a client page" -- billingContactName/
 * billingEmail if set, falling back to the primary contact
 * (firstName+lastName/email) otherwise. For a Private session
 * specifically, the BILLING CLIENT itself is resolved from the
 * session's own HOST (SessionAuthorizedClient.isHost=true) -- there's
 * no Enrollment to derive it from at all for a session with no
 * enrolled students.
 */
@Service
public class QboInvoiceService {

    private static final Logger log = LoggerFactory.getLogger(QboInvoiceService.class);
    // Michael, 2026-09-04 -- renamed from "Private Session Fee" -- confirmed with Michael as genuinely inaccurate now that this same service also handles Semi-Private sessions.
    private static final String PRIVATE_SESSION_ITEM_NAME = "On-Site Smoke School";
    private static final String OVERAGE_ITEM_NAME = "Private Session Overage Fee";

    private final QboApiClient apiClient;
    private final SessionRepository sessionRepository;
    private final SessionAuthorizedClientRepository authorizedClientRepository;
    private final QboCustomerSyncService customerSyncService;
    private final EnrollmentRepository enrollmentRepository;
    private final com.caa.platform.session.SessionPoResolutionService poResolutionService;

    public QboInvoiceService(QboApiClient apiClient, SessionRepository sessionRepository,
                              SessionAuthorizedClientRepository authorizedClientRepository,
                              QboCustomerSyncService customerSyncService,
                              EnrollmentRepository enrollmentRepository,
                              com.caa.platform.session.SessionPoResolutionService poResolutionService) {
        this.apiClient = apiClient;
        this.sessionRepository = sessionRepository;
        this.authorizedClientRepository = authorizedClientRepository;
        this.customerSyncService = customerSyncService;
        this.enrollmentRepository = enrollmentRepository;
        this.poResolutionService = poResolutionService;
    }

    /**
     * Michael, 2026-09-02 -- one flat-fee line for the session's own
     * privateCost, plus a second, conditional line for real field-
     * attendance overage beyond the session's own real, negotiated
     * included headcount (Session.bidNumFieldAttendees -- see this
     * class's own Javadoc). Requires the session already have a
     * qboClassRefId (from publish()) -- doesn't attempt to sync the
     * Class itself here, since Class sync is tied specifically to the
     * publish action, not invoice generation.
     */
    @Transactional
    public Map<String, Object> generatePrivateSessionInvoice(Long sessionId) {
        Session session = sessionRepository.findById(sessionId)
                .orElseThrow(() -> new IllegalArgumentException("Session not found: " + sessionId));

        // Michael, 2026-09-03 -- found live: this previously hard-
        // blocked outright the moment lastQboInvoiceGeneratedAt was
        // set, with no way to tell the difference between "a real,
        // still-open invoice already exists" and "the prior one was
        // genuinely voided directly in QBO, outside this app's own
        // knowledge entirely" (exactly what happened with Session 6's
        // own Doc #1038, voided together during an earlier
        // investigation). Confirmed with Michael: only check QBO when
        // the guard would otherwise block anyway (no slowdown for a
        // brand-new session), and fail CLOSED (keep blocking) if the
        // check itself can't complete or the prior invoice is still
        // genuinely open/paid -- a real, temporary inconvenience during
        // a rare QBO outage is a much smaller cost than risking a
        // duplicate charge slipping through. Verified directly (web
        // search, Intuit's own docs): QBO has no dedicated "status"
        // field at all, but voiding an invoice reliably, officially
        // zeroes out its own TotalAmt (not just Balance) -- a real,
        // distinct, checkable signal, not confusable with "paid" (a
        // paid invoice keeps its real, original TotalAmt; only
        // Balance goes to zero).
        if (session.getLastQboInvoiceGeneratedAt() != null) {
            if (!wasVoided(session.getLastQboInvoiceSentNumber())) {
                throw new IllegalStateException(
                        "Session " + sessionId + " already has a QBO invoice generated (" + session.getLastQboInvoiceSentNumber()
                                + ", " + session.getLastQboInvoiceGeneratedAt() + ") -- refusing to generate a second, duplicate one.");
            }
            log.info("Session {}'s prior QBO invoice ({}) was confirmed voided in QBO -- clearing the stale flag and allowing regeneration.",
                    sessionId, session.getLastQboInvoiceSentNumber());
            session.setLastQboInvoiceGeneratedAt(null);
            session.setLastQboInvoiceSentNumber(null);
            sessionRepository.save(session);
        }

        // Michael, 2026-09-04 -- expanded to SEMI_PRIVATE too. Confirmed
        // with Michael directly: Semi-Private follows the exact same
        // billing path as Private (the client handles payment on their
        // side) -- this was previously PRIVATE-only, a real, separate,
        // still-open gap, now closed alongside the close-out redesign.
        if (session.getSchoolType() != com.caa.platform.session.SchoolType.PRIVATE
                && session.getSchoolType() != com.caa.platform.session.SchoolType.SEMI_PRIVATE) {
            throw new IllegalStateException(
                    "This endpoint only handles PRIVATE/SEMI_PRIVATE sessions -- Public/per-student invoicing is a separate, different path.");
        }
        if (session.getPrivateCost() == null) {
            throw new IllegalStateException("Session " + sessionId + " has no privateCost set.");
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

        // Michael, 2026-09-04 -- found live: outsideAttendee students
        // were previously excluded from this count entirely, borrowed
        // from a DIFFERENT principle (Public sessions excluding them
        // from CAA's own billing) that doesn't actually apply here.
        // Confirmed with Michael directly: an outside org's own
        // students genuinely DO count toward real, physical overage --
        // they're taking up a real seat -- the host still gets billed
        // the same overage rate either way, but the invoice itself now
        // shows a real breakdown of who actually contributed to it, so
        // the host "can proceed accordingly" (Michael's own words) --
        // e.g. billing that outside org separately on their own.
        //
        // Confirmed with Michael: enrollment date is the real,
        // traceable method for WHICH students count as "within" vs
        // "overage" -- the first N enrolled (by date), where N is the
        // real, negotiated included headcount, are within; anyone
        // enrolled after that is the real overage. Confirmed this
        // included headcount is safe to read live/current at invoice-
        // generation time (not a historical snapshot) -- any real
        // adjustment always happens before the session's own actual
        // start date, with a fresh bid generated for transparency, so
        // by the time close-out invoicing happens the number is
        // already genuinely settled.
        List<Enrollment> fieldEnrollments = enrollmentRepository.findBySessionId(sessionId).stream()
                .filter(e -> e.getEnrollmentComponents() == EnrollmentComponents.FIELD_ONLY)
                .sorted(java.util.Comparator.comparing(Enrollment::getEnrollmentDate))
                .toList();
        long fieldHeadcount = fieldEnrollments.size();

        List<Map<String, Object>> lines = new java.util.ArrayList<>();

        Integer includedFieldHeadcount = session.getBidNumFieldAttendees();
        if (includedFieldHeadcount == null && fieldHeadcount > 0) {
            // Michael, 2026-09-02 -- same "honest failure, not a silent
            // guess" principle as the missing-fieldTest case below --
            // the real, negotiated included headcount is unknown, so
            // this can't safely determine whether overage applies at
            // all, let alone how much. Requires a human to set the
            // real number before invoicing, rather than assuming 15
            // (or any other default) and risking a real, wrong charge.
            throw new IllegalStateException(
                    "Session " + sessionId + " has " + fieldHeadcount + " field attendee(s) but no bidNumFieldAttendees set -- "
                            + "cannot determine the real, negotiated included headcount for this client's deal.");
        }

        lines.add(baseLineItem(session, itemId, fieldHeadcount, includedFieldHeadcount));

        if (includedFieldHeadcount != null && fieldHeadcount > includedFieldHeadcount) {
            long overageCount = fieldHeadcount - includedFieldHeadcount;
            if (session.getFieldTest() == null) {
                throw new IllegalStateException(
                        "Session " + sessionId + " has " + fieldHeadcount + " field attendees (over the included "
                                + includedFieldHeadcount + ") but has no overage rate (Session.fieldTest) set.");
            }
            java.math.BigDecimal overageAmount = session.getFieldTest().multiply(java.math.BigDecimal.valueOf(overageCount));
            String overageItemId = findOrCreateItem(OVERAGE_ITEM_NAME);

            // Michael, 2026-09-04 -- the real students causing this
            // overage are the ones enrolled AFTER the first, real,
            // included N (by enrollment date) -- grouped by their own,
            // actual Client (Enrollment.client), not the session's own
            // host, so a genuinely different, outside org's own
            // students are correctly attributed to their own company,
            // not silently folded into the host's own count.
            List<Enrollment> overageEnrollments = fieldEnrollments.subList((int) includedFieldHeadcount.longValue(), (int) fieldHeadcount);
            String overageBreakdown = buildOverageBreakdown(host, overageEnrollments);

            lines.add(Map.of(
                    "Amount", overageAmount,
                    "Description", "Overage (" + overageBreakdown + ")",
                    "DetailType", "SalesItemLineDetail",
                    "SalesItemLineDetail", Map.of(
                            "ItemRef", Map.of("value", overageItemId),
                            "ClassRef", Map.of("value", session.getQboClassRefId()),
                            "Qty", overageCount,
                            "UnitPrice", session.getFieldTest()
                    )
            ));
            log.info("Session {} has {} field attendees -- adding overage line for {} attendee(s) at {}/each ({}).",
                    sessionId, fieldHeadcount, overageCount, session.getFieldTest(), overageBreakdown);
        }

        // Michael, 2026-08-25 -- "we copy over whatever we put in the
        // client contact field unless they tell us otherwise" --
        // confirmed billing-contact fallback behavior.
        String billingEmail = host.getBillingEmail() != null ? host.getBillingEmail() : host.getEmail();

        java.util.Map<String, Object> invoicePayload = new java.util.HashMap<>();
        invoicePayload.put("CustomerRef", Map.of("value", qboCustomerId));
        invoicePayload.put("Line", lines);

        // Michael, 2026-09-04 -- confirmed with Michael directly: the
        // real, dedicated PO custom field configured in QBO couldn't be
        // reliably located through the API after real, extensive
        // investigation (neither the standard SalesFormsPrefs custom
        // fields nor VendorAndPurchasePrefs.POCustomField showed it) --
        // CustomerMemo used instead as the safe, always-available
        // fallback, matching Intuit's own community-recommended
        // workaround for exactly this purpose. Reuses the real, already
        // -built resolution priority (Session's own PO first, falling
        // back to the client's Persistent PO, confirmed with Michael as
        // "the invoice SHOULD reference that") -- SessionPoResolutionService
        // was already correctly built for this, just never actually
        // wired into real invoice generation until now.
        com.caa.platform.session.SessionPoResolutionService.ResolvedPo resolvedPo = poResolutionService.resolve(session);
        if (resolvedPo.source() != com.caa.platform.session.SessionPoResolutionService.PoSource.NONE) {
            invoicePayload.put("CustomerMemo", Map.of("value", "PO: " + resolvedPo.poNumber()));
        }

        if (billingEmail != null && !billingEmail.isBlank()) {
            invoicePayload.put("BillEmail", Map.of("Address", billingEmail));
        }
        // Michael, 2026-09-04 -- found live: EmailStatus "NeedToSend"
        // here would auto-email the invoice the moment it's GENERATED,
        // directly conflicting with the close-out redesign's own,
        // deliberate two-step Generate/Send split -- a client would
        // have received two separate emails for the same invoice (one
        // automatic here, one from the real, later "Send Invoice"
        // action). Confirmed with Michael: removed entirely -- sending
        // only ever happens from the real, explicit Send action now.

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
        // Michael, 2026-09-04 -- the real, internal Id, genuinely
        // different from DocNumber above -- needed to actually send
        // this invoice later (QBO's own real /send endpoint requires
        // the internal Id, not the human-facing DocNumber). Already,
        // always present in this same response -- just never stored
        // on this entity until now.
        session.setLastQboInvoiceId(String.valueOf(invoice.get("Id")));
        sessionRepository.save(session);

        log.info("Created QBO Invoice for session {} (Invoice Id={}, DocNumber={})", sessionId, invoice.get("Id"), invoice.get("DocNumber"));
        return invoice;
    }

    /**
     * Michael, 2026-09-04 -- session close-out billing redesign.
     * Confirmed with Michael as a genuinely separate, second step from
     * generatePrivateSessionInvoice() above -- this actually emails the
     * already-generated invoice to the client. Verified directly
     * (web search, QBO's own API docs) as a real, dedicated endpoint
     * (POST /invoice/{invoiceId}/send), the same pattern QBO uses for
     * sending estimates and credit memos -- distinct from the Public
     * path's own approach (setting EmailStatus at creation time
     * instead). Requires lastQboInvoiceId to already be set -- if no
     * invoice has ever been generated for this session at all, there's
     * genuinely nothing to send.
     */
    @Transactional
    public Map<String, Object> sendInvoice(Long sessionId) {
        Session session = sessionRepository.findById(sessionId)
                .orElseThrow(() -> new IllegalArgumentException("Session not found: " + sessionId));

        if (session.getLastQboInvoiceId() == null || session.getLastQboInvoiceId().isBlank()) {
            throw new IllegalStateException(
                    "Session " + sessionId + " has no QBO invoice generated yet -- generate one first before it can be sent.");
        }

        String path = "invoice/" + session.getLastQboInvoiceId() + "/send";
        Map<String, Object> response = apiClient.postEmpty(path).getBody();
        log.info("Sent QBO Invoice {} for session {} to the client.", session.getLastQboInvoiceId(), sessionId);
        return response;
    }

    /**
     * Michael, 2026-09-03 -- real, live check against QBO's own data,
     * only ever called when the duplicate-invoice guard above would
     * otherwise block. Queries by DocNumber (not the real, internal
     * QBO Id) deliberately -- Session only ever stored the human-
     * facing DocNumber (lastQboInvoiceSentNumber), never the internal
     * Id, so this works retroactively for an invoice generated before
     * this fix existed too (e.g. Session 6's own Doc #1038), not just
     * future ones. Fails CLOSED on any real problem -- a query that
     * comes back empty, malformed, or throws is treated as "can't
     * confirm this was voided," not "assume it was."
     */
    private boolean wasVoided(String docNumber) {
        if (docNumber == null || docNumber.isBlank()) {
            return false;
        }
        try {
            String query = org.springframework.web.util.UriComponentsBuilder.fromPath("query")
                    .queryParam("query", "SELECT * FROM Invoice WHERE DocNumber = '" + docNumber + "'")
                    .build()
                    .toUriString();
            Map<String, Object> response = apiClient.get(query).getBody();
            if (response == null) {
                return false;
            }
            @SuppressWarnings("unchecked")
            Map<String, Object> queryResponse = (Map<String, Object>) response.get("QueryResponse");
            if (queryResponse == null) {
                return false;
            }
            @SuppressWarnings("unchecked")
            List<Map<String, Object>> invoices = (List<Map<String, Object>>) queryResponse.get("Invoice");
            if (invoices == null || invoices.isEmpty()) {
                return false;
            }
            Object totalAmt = invoices.get(0).get("TotalAmt");
            if (totalAmt == null) {
                return false;
            }
            return new java.math.BigDecimal(String.valueOf(totalAmt)).compareTo(java.math.BigDecimal.ZERO) == 0;
        } catch (Exception e) {
            log.warn("Could not verify whether QBO Invoice DocNumber {} was voided -- treating as still open (fail closed).", docNumber, e);
            return false;
        }
    }

    /**
     * Michael, 2026-09-03 -- found live: this read session.getQuotedPrice()
     * -- a real, genuinely SEPARATE field from privateCost, not the same
     * thing despite the naming/wording confusion (confirmed with Michael
     * directly, then corrected once the actual code was traced). The UI's
     * own "Private Cost" field, the "ready to publish" gate, and Session's
     * own doc comment all use privateCost -- quotedPrice was the wrong
     * field for this from the start. Session 10 copied Session 6's own
     * old quotedPrice (never touched by any real UI edit at all, since
     * nothing writes to it except copy-forward) while privateCost was
     * correctly, visibly updated to $4,500 -- explaining the invoice
     * silently, honestly billing the stale $1,500 the whole time.
     */
    /**
     * Michael, 2026-09-04 -- naming convention confirmed with Michael:
     * "On-Site Smoke School" replaces the old "Private Session Fee"
     * (now genuinely inaccurate, since this same service also handles
     * Semi-Private) -- description shows the real, actual attendance
     * against the session's own negotiated included headcount, so the
     * host can see this at a glance on the invoice itself, not just
     * infer it.
     */
    private Map<String, Object> baseLineItem(Session session, String itemId, long fieldHeadcount, Integer includedFieldHeadcount) {
        String description = includedFieldHeadcount != null
                ? "On-Site Smoke School -- " + fieldHeadcount + " attendee(s) of " + includedFieldHeadcount
                : "On-Site Smoke School";
        return Map.of(
                "Amount", session.getPrivateCost(),
                "Description", description,
                "DetailType", "SalesItemLineDetail",
                "SalesItemLineDetail", Map.of(
                        "ItemRef", Map.of("value", itemId),
                        "ClassRef", Map.of("value", session.getQboClassRefId()),
                        "Qty", 1,
                        "UnitPrice", session.getPrivateCost()
                )
        );
    }

    /**
     * Michael, 2026-09-04 -- real, per-org breakdown of exactly who
     * contributed to a real overage, confirmed with Michael as needed
     * "so they are aware and can proceed accordingly" -- a Semi-
     * Private session's own outside orgs (Enrollment.client, distinct
     * from the session's own host) each named and counted separately,
     * not folded into one generic "outside" total, since more than one
     * distinct outside org could genuinely be involved at once.
     * Confirmed with Michael: shows the host's own real count too,
     * even if zero -- so a host whose own people enrolled on time can
     * see clearly they weren't the ones who caused it.
     */
    private String buildOverageBreakdown(Client host, List<Enrollment> overageEnrollments) {
        // Michael, 2026-09-04 -- found live: Client has no real
        // equals()/hashCode() at all (only @Getter/@Setter, no
        // @EqualsAndHashCode) -- grouping by the raw Client object
        // itself would use Java's own default, reference-identity
        // equality, silently creating a separate group per enrollment
        // even when multiple genuinely share the same real client (a
        // lazily-loaded proxy isn't guaranteed to be the same Java
        // object instance across different Enrollment rows). Grouped
        // by the real, stable Client Id instead, which safely supports
        // real value equality by default.
        Map<Long, Client> clientById = new java.util.LinkedHashMap<>();
        Map<Long, Long> countByClientId = overageEnrollments.stream()
                .collect(java.util.stream.Collectors.groupingBy(
                        e -> {
                            clientById.putIfAbsent(e.getClient().getId(), e.getClient());
                            return e.getClient().getId();
                        },
                        java.util.LinkedHashMap::new,
                        java.util.stream.Collectors.counting()
                ));

        long hostCount = countByClientId.getOrDefault(host.getId(), 0L);
        StringBuilder sb = new StringBuilder("Within " + host.getRecordName() + ": " + hostCount);

        List<String> outsideParts = countByClientId.entrySet().stream()
                .filter(e -> !e.getKey().equals(host.getId()))
                .map(e -> clientById.get(e.getKey()).getRecordName() + ": " + e.getValue())
                .toList();
        if (!outsideParts.isEmpty()) {
            sb.append(" / Outside ").append(String.join(", ", outsideParts));
        }
        return sb.toString();
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
