package com.caa.platform.client;

import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;

public interface PurchaseOrderSessionRepository extends JpaRepository<PurchaseOrderSession, Long> {
    List<PurchaseOrderSession> findByPurchaseOrderId(Long purchaseOrderId);
    boolean existsByPurchaseOrderIdAndSessionId(Long purchaseOrderId, Long sessionId);
}
