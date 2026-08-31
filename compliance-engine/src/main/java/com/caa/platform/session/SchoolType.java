package com.caa.platform.session;

/**
 * Section 4: a deliberate staff choice, not a derived state. PRIVATE is
 * client-ID-only, structurally locked to that one host with no outside
 * attendance possible. SEMI_PRIVATE is the staff choice that unlocks the
 * self-service mechanism -- the host can then add outside organizations
 * to {@link SessionAuthorizedClient} themselves.
 *
 * VTCA (Virginia Transportation Construction Alliance) confirmed as its
 * own peer value here against a real DIBs screenshot (2026-08-15,
 * "Session Type: VTCA" shown alongside Public/Private/Semi-Private/VR in
 * the same dropdown) -- an earlier design treating VTCA as a boolean flag
 * on PRIVATE was wrong and corrected before it shipped. Structurally,
 * VTCA behaves exactly like PRIVATE (single locked host client, no
 * outside attendance -- see SessionAuthorizationService) -- the real
 * difference is pricing: a pre-agreed contract rate rather than the
 * usual staff-judgment quote, using the same quotedPrice/quotedHeadcount
 * fields Private already has, not separate storage.
 *
 * PROPOSED confirmed against DIBs' real "Session Type" dropdown source
 * (2026-08-15) -- a genuine 5th peer value, not a status. Structurally
 * like PRIVATE (single owner client) but doesn't appear on the public
 * calendar -- represents the pre-bid-won stage before a session becomes
 * PRIVATE/SEMI_PRIVATE.
 *
 * VR was REMOVED as a value here (was previously present) -- DIBs' own
 * Session Type dropdown has no VR option at all. VR is a delivery-method
 * MODIFIER, not a type: it can apply to either PUBLIC (the common case)
 * or PRIVATE (bulk-purchase clients), never a category of its own. See
 * Session.vrSession.
 */
public enum SchoolType {
    PUBLIC,
    PRIVATE,
    SEMI_PRIVATE,
    PROPOSED,
    VTCA
}
