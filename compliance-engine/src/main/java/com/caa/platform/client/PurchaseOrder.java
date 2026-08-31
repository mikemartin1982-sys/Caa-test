package com.caa.platform.client;
 
import com.caa.platform.common.AuditableEntity;
import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;
 
import java.math.BigDecimal;
import java.time.LocalDate;
 
/**
 * Michael, 2026-08-22 -- clients often run one PO across a full year or
 * multiple seasons, not per-session; confirmed with Michael this
 * needed real, full tracking (balance, expiration, threshold alerts),
 * not a stripped-down version. Multiple POs per Client are allowed
 * (no unique constraint), matching real-world cases where an old PO
 * expires and a new one replaces it, or a client runs concurrent POs
 * for different departments/budgets.
 */
@Entity
@Table(name = "client_purchase_orders")
@Getter
@Setter
@NoArgsConstructor
public class PurchaseOrder extends AuditableEntity {
 
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;
 
    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "client_id", nullable = false)
    @com.fasterxml.jackson.annotation.JsonIgnore
    private Client client;
 
    /** Limited to 30 characters -- matches QBO's CustomField.StringValue field on the invoice (DIBs' own real constraint, carried forward). */
    @Column(name = "po_number", length = 30)
    private String poNumber;
 
    @Column(nullable = false)
    private boolean active = false;
 
    @Column(name = "expiration_date")
    private LocalDate expirationDate;
 
    @Column(name = "short_description", length = 50)
    private String shortDescription;
 
    @Column(name = "contact_first_name", length = 50)
    private String contactFirstName;
 
    @Column(name = "contact_last_name", length = 50)
    private String contactLastName;
 
    /** Optional -- if blank, PO-related notifications (expiration, low balance) fall back to the Client's own billing/primary contact. */
    @Column(name = "contact_email", length = 50)
    private String contactEmail;
 
    @Column(name = "starting_amount", precision = 10, scale = 2, nullable = false)
    private BigDecimal startingAmount = BigDecimal.ZERO;
 
    @Column(name = "amount_used", precision = 10, scale = 2, nullable = false)
    private BigDecimal amountUsed = BigDecimal.ZERO;
 
    @Column(name = "threshold_amount", precision = 10, scale = 2, nullable = false)
    private BigDecimal thresholdAmount = BigDecimal.ZERO;
 
    /** Set once a low-balance/expiration warning email has actually been sent, so it isn't sent repeatedly. Not yet wired to an email service. */
    @Column(name = "exp_email_sent")
    private LocalDate expEmailSent;
 
    /** Calculated, not stored -- startingAmount minus amountUsed. */
    public BigDecimal getAmountRemaining() {
        BigDecimal start = startingAmount != null ? startingAmount : BigDecimal.ZERO;
        BigDecimal used = amountUsed != null ? amountUsed : BigDecimal.ZERO;
        return start.subtract(used);
    }
}