package com.caa.platform.session;

import com.caa.platform.client.PurchaseOrder;
import com.caa.platform.client.PurchaseOrderRepository;
import org.springframework.stereotype.Service;

import java.math.BigDecimal;
import java.util.Comparator;
import java.util.List;
import java.util.Optional;

/**
 * Michael, 2026-08-23 -- resolves which PO actually applies to a
 * session, WITHOUT generating an invoice -- there's no real QuickBooks
 * connection yet, so building actual invoice generation now would be
 * untestable and premature (same reasoning already applied to the QBO
 * dual-field sync pattern skipped on 2026-08-22). This is deliberately
 * just the resolution logic and pathing, ready for whenever invoice
 * generation is actually built.
 *
 * Two genuinely different kinds of PO, confirmed with Michael:
 *   - Session.poForInvoice: a one-off PO number the client's own
 *     purchasing department generates from a specific bid, tied to
 *     that one session. No balance to track, nothing to "exhaust" --
 *     if set, it always wins outright, no interaction with any client
 *     Persistent PO balance at all.
 *   - Client-level Persistent PO (PurchaseOrder entity): a reusable
 *     pool of funds covering sessions by default, actually spent down
 *     over time. Only used as a fallback when the session has no PO
 *     of its own.
 *
 * If a client has more than one active Persistent PO at once (e.g. VR
 * token estimates don't perfectly account for employee turnover, so a
 * new PO gets issued before the old one's exhausted), default to
 * whichever expires SOONEST -- confirmed with Michael: a deliberate
 * use-it-or-lose-it choice, so the older PO's funds get used up before
 * they go to waste, rather than sitting unused while a newer PO gets
 * drawn down instead. A PO with no expiration date set is treated as
 * expiring last (nothing urging it to be used first).
 *
 * Only meaningful for Private/Semi-Private sessions with a host client
 * on file -- a Public session has no single client to resolve a
 * Persistent PO against.
 */
@Service
public class SessionPoResolutionService {

    private final SessionAuthorizedClientRepository authorizedClientRepository;
    private final PurchaseOrderRepository purchaseOrderRepository;

    public SessionPoResolutionService(SessionAuthorizedClientRepository authorizedClientRepository,
                                       PurchaseOrderRepository purchaseOrderRepository) {
        this.authorizedClientRepository = authorizedClientRepository;
        this.purchaseOrderRepository = purchaseOrderRepository;
    }

    public enum PoSource { SESSION, PERSISTENT, NONE }

    public record ResolvedPo(PoSource source, String poNumber, BigDecimal amountRemaining,
                              java.time.LocalDate expirationDate, String note) {}

    public ResolvedPo resolve(Session session) {
        if (session.getPoForInvoice() != null && !session.getPoForInvoice().isBlank()) {
            return new ResolvedPo(PoSource.SESSION, session.getPoForInvoice(), null, null,
                    "Session-specific PO -- takes precedence over any client Persistent PO.");
        }

        SessionAuthorizedClient host = authorizedClientRepository.findBySessionIdAndIsHostTrue(session.getId())
                .orElse(null);
        if (host == null) {
            return new ResolvedPo(PoSource.NONE, null, null, null,
                    "No session-level PO, and no host client to check for a Persistent PO.");
        }

        List<PurchaseOrder> activePos = purchaseOrderRepository.findByClientIdAndActiveTrue(host.getClient().getId());
        Optional<PurchaseOrder> chosen = activePos.stream()
                .filter(po -> po.getAmountRemaining().compareTo(BigDecimal.ZERO) > 0)
                .min(Comparator.comparing(
                        PurchaseOrder::getExpirationDate,
                        Comparator.nullsLast(Comparator.naturalOrder())));

        if (chosen.isEmpty()) {
            return new ResolvedPo(PoSource.NONE, null, null, null,
                    "No session-level PO, and the host client has no active Persistent PO with remaining balance.");
        }

        PurchaseOrder po = chosen.get();
        return new ResolvedPo(PoSource.PERSISTENT, po.getPoNumber(), po.getAmountRemaining(), po.getExpirationDate(),
                "Falling back to the client's Persistent PO expiring soonest (used-up-first, per Michael, 2026-08-23).");
    }
}
