package com.caa.platform.client;

import com.caa.platform.session.Session;
import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;

/**
 * Michael, 2026-08-22 -- replaces DIBs' PO_assoc_sessions (a
 * comma-separated text field on the PO record) with a real, queryable
 * join table. Same reasoning already applied throughout this project
 * to other DIBs denormalized-list fields (e.g. SessionNotifiedClient).
 */
@Entity
@Table(name = "client_purchase_order_sessions")
@Getter
@Setter
@NoArgsConstructor
public class PurchaseOrderSession {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "purchase_order_id", nullable = false)
    @com.fasterxml.jackson.annotation.JsonIgnore
    private PurchaseOrder purchaseOrder;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "session_id", nullable = false)
    private Session session;
}
