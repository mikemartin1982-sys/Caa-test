package com.caa.platform.integration.qbo;

import com.caa.platform.enrollment.Payment;
import com.caa.platform.enrollment.PaymentRepository;
import com.caa.platform.enrollment.PaymentStatus;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.http.ResponseEntity;
import org.springframework.scheduling.annotation.Scheduled;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.math.BigDecimal;
import java.util.List;
import java.util.Map;
import java.util.stream.Collectors;

/**
 * Michael, 2026-09-01 -- Client Auto-Notify feature. Webhooks would be
 * the more standard way to detect a QBO invoice being paid, but
 * Intuit needs a real, publicly-reachable URL to push to -- everything
 * here runs on 127.0.0.1 today, so that's not viable yet. Polling
 * instead, confirmed with Michael as "every few minutes."
 *
 * Deliberately checks only OUR OWN, already-known invoices (via
 * Payment.qbInvoiceId, one lookup per real, distinct invoice ID) --
 * never a broad QBO-wide query. Confirmed directly with Michael: this
 * same QBO company may already have real, unrelated, outstanding
 * invoices from current DIBs -- a broad query risked catching those
 * too and sending incorrect notification emails for enrollments that
 * have nothing to do with this platform.
 *
 * REQUIRES @EnableScheduling on the application's main class (or a
 * @Configuration class) -- without it, @Scheduled methods are simply
 * never invoked at all, with no error. This is the first scheduled
 * task in this project; that annotation likely doesn't exist yet.
 */
@Service
public class QboPaymentPollingService {

    private static final Logger log = LoggerFactory.getLogger(QboPaymentPollingService.class);

    private final QboApiClient apiClient;
    private final PaymentRepository paymentRepository;
    private final QboPaymentNotificationService notificationService;

    public QboPaymentPollingService(QboApiClient apiClient, PaymentRepository paymentRepository,
                                     QboPaymentNotificationService notificationService) {
        this.apiClient = apiClient;
        this.paymentRepository = paymentRepository;
        this.notificationService = notificationService;
    }

    /**
     * Michael, 2026-09-01 -- every 5 minutes, confirmed with Michael as
     * matching "every few minutes" -- fixedDelay (not fixedRate), so a
     * slow run never overlaps the next one starting before it finishes.
     */
    @Scheduled(fixedDelay = 5 * 60 * 1000)
    public void pollForPaidInvoices() {
        // Michael, 2026-09-01 -- calling checkAllKnownInvoices() here
        // is a self-invocation (same class instance), which bypasses
        // Spring's proxy entirely -- @Transactional on that method
        // therefore does NOT actually apply on this, the scheduled
        // path, only when a different bean (the new manual-refresh
        // endpoint) calls it externally, through the real proxy. Left
        // as-is deliberately: this method never touches a lazy-loaded
        // relationship at all (unlike EnrollmentPricingService's own,
        // genuine need for @Transactional), so this is a real,
        // documented inconsistency, not a correctness bug.
        checkAllKnownInvoices();
    }

    /**
     * Michael, 2026-09-01 -- extracted from pollForPaidInvoices() so
     * the new manual "Refresh" invoice-checker page (Option B,
     * confirmed with Michael) can trigger the exact same real check-
     * and-act logic on demand, not a separate, parallel copy of it.
     * This is also what prevents the double-send risk Option B could
     * otherwise introduce: a Payment only ever gets notified once it's
     * moved off PENDING/INVOICED, so whichever caller -- the scheduled
     * job or a manual refresh -- gets there first for a given invoice
     * is the only one that acts on it, even if both happened to run
     * at nearly the same moment.
     */
    @Transactional
    public List<InvoiceCheckResult> checkAllKnownInvoices() {
        // Michael, 2026-09-01 -- found live: this previously exited
        // silently when there was nothing to check, with no log line
        // at all -- meant there was no way to tell "the job hasn't run
        // yet" apart from "it ran fine, just nothing to do" -- both
        // looked identical: silence. Logged explicitly now, every run.
        List<Payment> unpaid = paymentRepository.findByStatus(PaymentStatus.INVOICED);
        log.info("QBO payment poll running -- {} un-paid invoice(s) to check.", unpaid.size());
        if (unpaid.isEmpty()) {
            return List.of();
        }

        // Michael, 2026-09-01 -- one Invoice can cover multiple
        // students (per-client, per-session batching) -- grouped by
        // qbInvoiceId so each real, distinct QBO invoice is checked
        // exactly once per poll, not once per student on it.
        Map<String, List<Payment>> byInvoice = unpaid.stream()
                .filter(p -> p.getQbInvoiceId() != null && !"NO_CHARGE".equals(p.getQbInvoiceId()))
                .collect(Collectors.groupingBy(Payment::getQbInvoiceId));

        List<InvoiceCheckResult> results = new java.util.ArrayList<>();

        for (Map.Entry<String, List<Payment>> entry : byInvoice.entrySet()) {
            String invoiceId = entry.getKey();
            try {
                // Michael, 2026-09-01 -- found live: a second silent
                // path -- if this returned false (genuinely not yet
                // paid, the normal case for most checks), nothing was
                // ever logged either. Every checked invoice now leaves
                // a real, visible line -- paid or not -- showing the
                // actual balance QBO returned, not just a bare
                // true/false, so a genuinely unexpected value (e.g.
                // null, meaning the invoice lookup itself came back
                // empty) is visible too, not indistinguishable from a
                // real, normal "still owes money" case.
                BigDecimal balance = fetchBalance(invoiceId);
                if (balance != null && balance.compareTo(BigDecimal.ZERO) == 0) {
                    for (Payment payment : entry.getValue()) {
                        payment.setStatus(PaymentStatus.PAID);
                        paymentRepository.save(payment);
                    }
                    log.info("QBO Invoice {} confirmed paid -- {} enrollment(s) updated.", invoiceId, entry.getValue().size());
                    notificationService.notifyStudentsForInvoice(invoiceId);
                    results.add(new InvoiceCheckResult(invoiceId, balance, true, null));
                } else {
                    log.info("QBO Invoice {} checked -- balance = {}, not yet paid.", invoiceId, balance);
                    results.add(new InvoiceCheckResult(invoiceId, balance, false, null));
                }
            } catch (Exception e) {
                // Michael, 2026-09-01 -- one invoice's check failing
                // (a transient QBO API error, a stale/deleted invoice,
                // etc.) must never block checking every other, unrelated
                // invoice in this same poll -- caught and logged per
                // invoice, not per batch.
                log.error("Failed to check payment status for QBO Invoice {}", invoiceId, e);
                results.add(new InvoiceCheckResult(invoiceId, null, false, e.getMessage()));
            }
        }

        return results;
    }

    /** Michael, 2026-09-01 -- one real, per-invoice outcome from a single check-and-act pass -- what the new manual refresh endpoint returns to Laravel. */
    public record InvoiceCheckResult(String invoiceId, BigDecimal balance, boolean newlyPaid, String error) {}

    @SuppressWarnings("unchecked")
    private BigDecimal fetchBalance(String invoiceId) {
        ResponseEntity<Map> response = apiClient.get("invoice/" + invoiceId);
        Map<String, Object> body = response.getBody();
        if (body == null) {
            return null;
        }
        Map<String, Object> invoice = (Map<String, Object>) body.get("Invoice");
        if (invoice == null || invoice.get("Balance") == null) {
            return null;
        }
        return new BigDecimal(String.valueOf(invoice.get("Balance")));
    }
}
