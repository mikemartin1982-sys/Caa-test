package com.caa.platform.enrollment;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;
import org.hibernate.annotations.JdbcTypeCode;
import org.hibernate.type.SqlTypes;

import java.math.BigDecimal;
import java.time.LocalDate;
import java.time.OffsetDateTime;

/** Section 7: QuickBooks invoice tracking. */
@Entity
@Table(name = "payments")
@Getter
@Setter
@NoArgsConstructor
public class Payment {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "enrollment_id", nullable = false)
    private Enrollment enrollment;

    @Column(name = "qb_invoice_id", nullable = false)
    private String qbInvoiceId;

    @Column(nullable = false, precision = 10, scale = 2)
    private BigDecimal amount;

    /** 'due_on_receipt' | 'net_30' | 'net_60' | 'net_90' etc. */
    private String terms;

    @Enumerated(EnumType.STRING)
    @JdbcTypeCode(SqlTypes.NAMED_ENUM)
    @Column(nullable = false)
    private PaymentStatus status = PaymentStatus.INVOICED;

    @Column(name = "payment_date")
    private LocalDate paymentDate;

    /**
     * Michael, 2026-09-01 -- Client Auto-Notify feature. Set the moment
     * a real Brevo notification for this Payment's own enrollment
     * genuinely succeeds -- null means either never attempted, or
     * attempted and failed (see brevoNotificationError below for
     * which). Confirmed with Michael: needed so a new invoice-status
     * readout can show whether notification actually happened, not
     * just whether the invoice itself was marked paid.
     */
    @Column(name = "brevo_notified_at")
    private OffsetDateTime brevoNotifiedAt;

    /** Set only when an attempted notification failed -- null on success or if never attempted at all. */
    @Column(name = "brevo_notification_error", length = 500)
    private String brevoNotificationError;

    @Column(name = "created_at", nullable = false, updatable = false)
    private OffsetDateTime createdAt = OffsetDateTime.now();

    @Column(name = "updated_at")
    private OffsetDateTime updatedAt = OffsetDateTime.now();
}
