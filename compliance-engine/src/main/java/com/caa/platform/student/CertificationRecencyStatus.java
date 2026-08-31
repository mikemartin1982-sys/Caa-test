package com.caa.platform.student;

/**
 * Michael, 2026-08-23 -- Client Page roster (Layer 2). Purely
 * informational, not an enforcement gate -- CAA is a certification
 * vendor, not an enforcement agency. The only thing CAA actually
 * enforces is Alt 152-A requiring a lecture on file before VR testing
 * can begin; everything else here is a display-only signal for staff
 * and clients.
 *
 * Confirmed with Michael: the underlying "1 year" rule is really about
 * LECTURE validity -- a lecture stays valid as long as the student
 * recertifies in the field within a calendar year of their last
 * certification; past that, the lecture itself lapses and a refresher
 * is needed. That lecture requirement only genuinely applies to VR
 * (Alt 152-A) and Texas (the only traditional-smoke-school state with
 * one) -- but this status itself is computed uniformly for every
 * student regardless of state or VR status, as a general recency
 * indicator, since it's informational rather than an enforcement rule
 * tied to those specific contexts.
 *
 * NEAR_EXPIRATION's 60-day window matches DIBs' real, existing
 * behavior -- a scheduled job emails each client's contact at the
 * 60-day mark listing their employees' upcoming expirations. That
 * scheduled job and its email are NOT built here (this project has no
 * task-scheduling mechanism yet, and email sending is still a stub
 * everywhere else it's come up) -- this enum only provides the
 * underlying computation so it exists and is available; whether/where
 * to surface NEAR_EXPIRATION on the roster display itself is left for
 * management to decide, not baked into this being built.
 */
public enum CertificationRecencyStatus {
    CERTIFIED,
    NEAR_EXPIRATION,
    EXPIRED
}
