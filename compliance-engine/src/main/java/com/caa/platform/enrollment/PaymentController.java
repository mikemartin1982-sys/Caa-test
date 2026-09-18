package com.caa.platform.enrollment;

import com.caa.platform.integration.qbo.QboPaymentNotificationService;
import com.caa.platform.integration.qbo.QboPaymentPollingService;
import org.springframework.http.ResponseEntity;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.web.bind.annotation.*;

import java.math.BigDecimal;
import java.time.OffsetDateTime;
import java.util.List;

/**
 * Michael, 2026-09-01 -- Client Auto-Notify feature. Backs the new
 * invoice-checker page -- confirmed with Michael as scoped to only our
 * own known invoices (real Payment rows), the exact same records the
 * scheduled polling job already tracks, nothing broader.
 */
@RestController
@RequestMapping("/api/v1/payments")
public class PaymentController {

    private final PaymentRepository paymentRepository;
    private final QboPaymentPollingService pollingService;
    private final QboPaymentNotificationService notificationService;

    public PaymentController(PaymentRepository paymentRepository, QboPaymentPollingService pollingService,
                              QboPaymentNotificationService notificationService) {
        this.paymentRepository = paymentRepository;
        this.pollingService = pollingService;
        this.notificationService = notificationService;
    }

    public record PaymentStatusRow(Long paymentId, String qbInvoiceId, PaymentStatus status, BigDecimal amount,
                                    String studentName, String clientName,
                                    OffsetDateTime brevoNotifiedAt, String brevoNotificationError) {}

    /**
     * Michael, 2026-09-01 -- read-only, never triggers a real QBO
     * check itself -- just what's already known in our own DB, for
     * the page's initial load. PENDING excluded -- nothing's been
     * invoiced yet, so there's nothing meaningful to show.
     */
    @GetMapping("/known-invoices")
    @Transactional(readOnly = true)
    public ResponseEntity<List<PaymentStatusRow>> knownInvoices() {
        List<Payment> payments = paymentRepository.findRecentByStatusIn(List.of(PaymentStatus.INVOICED, PaymentStatus.PAID), OffsetDateTime.now().minusDays(7));
        return ResponseEntity.ok(payments.stream().map(this::toRow).toList());
    }

    /**
     * Michael, 2026-09-01 -- "Refresh" -- Option B, confirmed with
     * Michael: actually re-checks real, current balances against QBO
     * and acts (marks paid, fires Brevo) right now, not just displays.
     * Calls the exact same, shared checkAllKnownInvoices() the
     * scheduled job itself uses -- see that method's own Javadoc for
     * why this is what prevents a double-send if a refresh happens to
     * land at nearly the same moment as a scheduled poll.
     *
     * @Transactional (not readOnly) -- deliberately NOT split into a
     * separate read-only fetch afterward. Spring's default REQUIRED
     * propagation means checkAllKnownInvoices()'s own @Transactional
     * joins this same, already-open transaction (a different bean,
     * called externally through the real proxy, unlike the scheduled
     * job's self-invocation case) -- keeping it open through both the
     * real writes AND the lazy Student/Client loads in toRow() below,
     * avoiding the same class of LazyInitializationException already
     * found and fixed in pricingPreview() earlier tonight.
     */
    @PostMapping("/refresh-invoices")
    @Transactional
    public ResponseEntity<List<PaymentStatusRow>> refreshInvoices() {
        pollingService.checkAllKnownInvoices();
        List<Payment> payments = paymentRepository.findRecentByStatusIn(List.of(PaymentStatus.INVOICED, PaymentStatus.PAID), OffsetDateTime.now().minusDays(7));
        return ResponseEntity.ok(payments.stream().map(this::toRow).toList());
    }

    public record RetryNotificationRequest(String qbInvoiceId) {}

    /**
     * Michael, 2026-09-02 -- Client Auto-Notify feature. Real, manual
     * retry for an invoice with Payment rows stuck permanently at "Not
     * yet" (e.g. Invoice 146 -- marked PAID before a real Brevo API
     * key existed, so the one moment it could have notified had
     * already passed, with no automatic path to ever try again).
     * Deliberately manual, not automatic -- confirmed with Michael:
     * an automatic retry risks looping forever on a genuinely
     * permanent failure (a student with no email on file would never
     * succeed no matter how many times it's retried), and a manual
     * button gives staff a real chance to fix the underlying issue
     * first. Safe to call more than once for the same invoice --
     * notifyStudentsForInvoice()'s own isNotifiable() now skips
     * anything already genuinely notified, so this never duplicate-
     * sends to students who already succeeded.
     */
    @PostMapping("/retry-notification")
    @Transactional
    public ResponseEntity<List<PaymentStatusRow>> retryNotification(@RequestBody RetryNotificationRequest req) {
        notificationService.notifyStudentsForInvoice(req.qbInvoiceId());
        List<Payment> payments = paymentRepository.findRecentByStatusIn(List.of(PaymentStatus.INVOICED, PaymentStatus.PAID), OffsetDateTime.now().minusDays(7));
        return ResponseEntity.ok(payments.stream().map(this::toRow).toList());
    }

    private PaymentStatusRow toRow(Payment payment) {
        Enrollment enrollment = payment.getEnrollment();
        return new PaymentStatusRow(
                payment.getId(),
                payment.getQbInvoiceId(),
                payment.getStatus(),
                payment.getAmount(),
                enrollment.getStudent().getName(),
                enrollment.getClient().getRecordName(),
                payment.getBrevoNotifiedAt(),
                payment.getBrevoNotificationError()
        );
    }
}
