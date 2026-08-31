package com.caa.platform.vr;

import com.caa.platform.client.Client;
import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;

import java.math.BigDecimal;
import java.time.LocalDate;
import java.time.OffsetDateTime;

/**
 * Section 3a: bulk VR purchases. Priced PER-TRANSACTION, not cumulative
 * across a client's purchase history (deliberate -- headcounts churn).
 * priceePaidPerToken reflects the applicable tier ($275/$250/$225) or the
 * client's VR pricing override (Section 4b), if set.
 */
@Entity
@Table(name = "vr_token_blocks")
@Getter
@Setter
@NoArgsConstructor
public class VRTokenBlock {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "client_id", nullable = false)
    private Client client;

    @Column(name = "tokens_purchased", nullable = false)
    private Integer tokensPurchased;

    @Column(name = "tokens_remaining", nullable = false)
    private Integer tokensRemaining;

    @Column(name = "purchase_date", nullable = false)
    private LocalDate purchaseDate = LocalDate.now();

    @Column(name = "price_paid_per_token", nullable = false, precision = 8, scale = 2)
    private BigDecimal pricePaidPerToken;

    @Column(name = "qb_invoice_id")
    private String qbInvoiceId;

    @Column(name = "created_at", nullable = false, updatable = false)
    private OffsetDateTime createdAt = OffsetDateTime.now();
}
