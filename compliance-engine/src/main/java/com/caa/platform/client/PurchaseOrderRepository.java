package com.caa.platform.client;

import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;

public interface PurchaseOrderRepository extends JpaRepository<PurchaseOrder, Long> {
    List<PurchaseOrder> findByClientIdOrderByCreatedAtDesc(Long clientId);

    /**
     * Michael, 2026-08-23 -- used by SessionPoResolutionService to find
     * a client's Persistent PO fallback. Remaining-balance filtering
     * happens in the service layer, not here -- amountRemaining is
     * calculated (startingAmount - amountUsed), not a stored column, so
     * it can't be expressed as a derived query method name.
     */
    List<PurchaseOrder> findByClientIdAndActiveTrue(Long clientId);
}
