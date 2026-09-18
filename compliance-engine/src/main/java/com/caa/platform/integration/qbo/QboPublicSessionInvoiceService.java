package com.caa.platform.integration.qbo;

import com.caa.platform.client.Client;
import com.caa.platform.enrollment.Enrollment;
import com.caa.platform.enrollment.EnrollmentComponents;
import com.caa.platform.enrollment.EnrollmentPricingService;
import com.caa.platform.enrollment.EnrollmentRepository;
import com.caa.platform.enrollment.Payment;
import com.caa.platform.enrollment.PaymentRepository;
import com.caa.platform.enrollment.PaymentStatus;
import com.caa.platform.session.Session;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.http.ResponseEntity;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.web.util.UriComponentsBuilder;

import java.math.BigDecimal;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

/**
 * Michael, 2026-09-01 -- QBO Per-Student Invoicing, Stage 3B. Mirrors
 * QboInvoiceService's own structure (real Customer sync, real Item
 * find-or-create, real Invoice creation) but for the genuinely
 * different Public-session shape: one line PER student, not one flat
 * fee for the whole session -- confirmed with Michael as one invoice
 * per client, per session (a real QBO constraint: one CustomerRef per
 * invoice, and a Public session can have several different clients'
 * employees enrolled together).
 *
 * findOrCreateItem()/findIncomeAccount() below are a deliberate,
 * near-exact duplicate of QboInvoiceService's own private methods --
 * genuinely identical, business-logic-free QBO mechanics -- kept
 * separate rather than extracted into a shared service, matching this
 * project's own established pattern of keeping parallel
 * implementations independent (StaffPasswordResetService/
 * ClientPasswordResetService) rather than risking a refactor of
 * already-working, proven code for this new, separate piece.
 */
@Service
public class QboPublicSessionInvoiceService {

    private static final Logger log = LoggerFactory.getLogger(QboPublicSessionInvoiceService.class);

    private static final String FIELD_ITEM_NAME = "Public Session Field Fee";
    private static final String VR_ITEM_NAME = "VR Session Fee";
    private static final String LECTURE_ITEM_NAME = "Self-Paced Lecture Fee";
    private static final String LATE_FEE_ITEM_NAME = "Late Enrollment Fee";

    private final QboApiClient apiClient;
    private final QboCustomerSyncService customerSyncService;
    private final EnrollmentPricingService pricingService;
    private final EnrollmentRepository enrollmentRepository;
    private final PaymentRepository paymentRepository;

    public QboPublicSessionInvoiceService(QboApiClient apiClient, QboCustomerSyncService customerSyncService,
                                           EnrollmentPricingService pricingService,
                                           EnrollmentRepository enrollmentRepository,
                                           PaymentRepository paymentRepository) {
        this.apiClient = apiClient;
        this.customerSyncService = customerSyncService;
        this.pricingService = pricingService;
        this.enrollmentRepository = enrollmentRepository;
        this.paymentRepository = paymentRepository;
    }

    /**
     * Michael, 2026-09-01 -- every enrollment passed in must already
     * share the same client and session -- validated explicitly here,
     * not assumed, since a mistake in the caller (Laravel side) mixing
     * enrollments from two different clients would otherwise produce a
     * real, wrong QBO invoice billed to the wrong company.
     *
     * A $0 line (lecture-exempt student, or a VR-no-cost client) is
     * skipped entirely -- confirmed with Michael: "no value in adding
     * a line item that shows $0." If every enrollment in the batch is
     * $0, no real QBO Invoice is created at all (QBO requires at least
     * one line) -- each is still marked handled via a $0 Payment row,
     * so they don't linger as "un-invoiced" forever for something that
     * never needed billing.
     */
    @Transactional
    public Map<String, Object> generatePublicSessionInvoice(List<Long> enrollmentIds) {
        if (enrollmentIds == null || enrollmentIds.isEmpty()) {
            throw new IllegalArgumentException("No enrollments to invoice.");
        }

        List<Enrollment> enrollments = enrollmentRepository.findAllById(enrollmentIds);
        if (enrollments.size() != enrollmentIds.size()) {
            throw new IllegalArgumentException("One or more enrollment IDs were not found.");
        }

        Client client = enrollments.get(0).getClient();
        Session session = enrollments.get(0).getSession();
        for (Enrollment e : enrollments) {
            if (!e.getClient().getId().equals(client.getId())) {
                throw new IllegalArgumentException("All enrollments being invoiced together must share the same client.");
            }
            if (!e.getSession().getId().equals(session.getId())) {
                throw new IllegalArgumentException("All enrollments being invoiced together must share the same session.");
            }
            if (e.getPaymentStatus() != PaymentStatus.PENDING) {
                throw new IllegalArgumentException("Enrollment " + e.getId() + " has already been invoiced.");
            }
            if (e.isOutsideAttendee()) {
                throw new IllegalArgumentException("Enrollment " + e.getId() + " is an outside attendee -- excluded from CAA's own billing.");
            }
        }

        List<Map<String, Object>> lines = new ArrayList<>();
        boolean anyLate = false;

        for (Enrollment e : enrollments) {
            BigDecimal price = pricingService.computePrice(e);
            if (price.compareTo(BigDecimal.ZERO) > 0) {
                String itemName = itemNameFor(e, session);
                String itemId = findOrCreateItem(itemName);
                lines.add(buildLine(price, itemId, describeEnrollment(e)));
            }
            if (pricingService.isLateEnrollment(e)) {
                anyLate = true;
            }
        }

        if (anyLate) {
            BigDecimal lateFee = pricingService.lateFeeAmount();
            String lateFeeItemId = findOrCreateItem(LATE_FEE_ITEM_NAME);
            lines.add(buildLine(lateFee, lateFeeItemId, "Late Enrollment Fee"));
        }

        Map<String, Object> invoice = null;
        if (!lines.isEmpty()) {
            String qboCustomerId = customerSyncService.syncCustomer(client);
            String billingEmail = client.getBillingEmail() != null ? client.getBillingEmail() : client.getEmail();

            Map<String, Object> invoicePayload = new HashMap<>();
            invoicePayload.put("CustomerRef", Map.of("value", qboCustomerId));
            invoicePayload.put("Line", lines);
            if (billingEmail != null && !billingEmail.isBlank()) {
                invoicePayload.put("BillEmail", Map.of("Address", billingEmail));
                // Michael, 2026-09-01 -- Client Auto-Notify feature.
                // Same reasoning as QboInvoiceService's own private-
                // session invoice -- EmailStatus "NeedToSend" auto-
                // emails on creation, requires BillEmail, hence nested
                // in this same null-check.
                invoicePayload.put("EmailStatus", "NeedToSend");
            }

            ResponseEntity<Map> response = apiClient.post("invoice", invoicePayload);
            Map<String, Object> body = response.getBody();
            if (body == null) {
                throw new IllegalStateException("QuickBooks Invoice creation returned an empty response.");
            }
            invoice = (Map<String, Object>) body.get("Invoice");
            if (invoice == null) {
                throw new IllegalStateException("QuickBooks Invoice creation response did not include the new Invoice.");
            }
            log.info("Created QBO Invoice for client {} / session {} (Invoice Id={})", client.getId(), session.getId(), invoice.get("Id"));
        }

        String qbInvoiceId = invoice != null ? (String) invoice.get("Id") : "NO_CHARGE";
        for (Enrollment e : enrollments) {
            BigDecimal price = pricingService.computePrice(e);

            Payment payment = new Payment();
            payment.setEnrollment(e);
            payment.setQbInvoiceId(qbInvoiceId);
            payment.setAmount(price);
            payment.setStatus(PaymentStatus.INVOICED);
            paymentRepository.save(payment);

            e.setPaymentStatus(PaymentStatus.INVOICED);
            enrollmentRepository.save(e);
        }

        return invoice != null ? invoice : Map.of("noCharge", true, "message", "Every selected enrollment was $0 -- no QuickBooks invoice was needed.");
    }

    private String itemNameFor(Enrollment e, Session session) {
        if (e.getEnrollmentComponents() == EnrollmentComponents.LECTURE_ONLY) {
            return LECTURE_ITEM_NAME;
        }
        return session.isVrSession() ? VR_ITEM_NAME : FIELD_ITEM_NAME;
    }

    private String describeEnrollment(Enrollment e) {
        String name = e.getStudent().getName();
        return name != null ? name : ("Student #" + e.getStudent().getId());
    }

    private Map<String, Object> buildLine(BigDecimal amount, String itemId, String description) {
        return Map.of(
                "Amount", amount,
                "Description", description,
                "DetailType", "SalesItemLineDetail",
                "SalesItemLineDetail", Map.of(
                        "ItemRef", Map.of("value", itemId),
                        "Qty", 1,
                        "UnitPrice", amount
                )
        );
    }

    /**
     * Michael, 2026-09-01 -- near-exact duplicate of QboInvoiceService's
     * own findOrCreateItem(), kept separate rather than shared (see
     * this class's own Javadoc).
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
