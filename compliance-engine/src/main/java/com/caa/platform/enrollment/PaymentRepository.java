package com.caa.platform.enrollment;

import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;

/**
 * Michael, 2026-08-31 -- QBO Per-Student Invoicing. Correction: this
 * file originally claimed Payment.java was "confirmed dormant" -- that
 * was never actually verified, just assumed, and it was wrong.
 * EnrollmentController.java already depends on this exact interface,
 * expecting findByEnrollmentId() to return a List, not a single
 * Optional -- Payment has no unique constraint on enrollmentId at all
 * (a voided-and-reissued invoice could legitimately mean more than one
 * Payment row for the same enrollment over time), which the original
 * Optional-based signature didn't account for. Fixed to match the
 * real, already-expected shape.
 */
public interface PaymentRepository extends JpaRepository<Payment, Long> {

    List<Payment> findByEnrollmentId(Long enrollmentId);

    /**
     * Michael, 2026-09-01 -- Client Auto-Notify feature. The real
     * candidates for the payment-polling job -- every Payment not yet
     * confirmed paid.
     */
    List<Payment> findByStatus(PaymentStatus status);

    /** Michael, 2026-09-01 -- Client Auto-Notify feature. The invoice-checker readout -- every Payment currently relevant to show, PENDING excluded since nothing's been invoiced yet. */
    List<Payment> findByStatusIn(List<PaymentStatus> statuses);

    /**
     * Michael, 2026-09-03 -- invoice-checker readout, 7-day filter.
     * Confirmed with Michael: "purge" means filter this list, never
     * delete real, historical billing data -- the underlying Payment
     * row stays forever either way. A row genuinely still stuck (never
     * successfully sent -- brevoNotifiedAt null) stays visible
     * indefinitely, no matter how old -- matches Michael's own wording
     * ("once it goes 'sent to Brevo'") as the 7-day clock only
     * starting on a real, confirmed success.
     */
    @org.springframework.data.jpa.repository.Query(
            "SELECT p FROM Payment p WHERE p.status IN :statuses "
            + "AND (p.brevoNotifiedAt IS NULL OR p.brevoNotifiedAt >= :cutoff)")
    List<Payment> findRecentByStatusIn(List<PaymentStatus> statuses, java.time.OffsetDateTime cutoff);

    /** Every Payment row sharing one real QBO invoice -- Payment.qbInvoiceId is what ties them together as "the same invoice." */
    List<Payment> findByQbInvoiceId(String qbInvoiceId);
}
