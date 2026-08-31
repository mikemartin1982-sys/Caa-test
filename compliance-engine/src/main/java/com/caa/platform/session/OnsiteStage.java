package com.caa.platform.session;

/**
 * Digital Testing stage, INFERRED (not stored) from Session's
 * onsiteSignInEnabled/onsiteTestingEnabled + the existing closedOut
 * flag -- see Session.getOnsiteStage() for the exact logic. Order
 * confirmed with Michael, 2026-08-16.
 *
 * CLOSED has two independent paths to it: the session's real Closed
 * Out/billing-lock flag (Chasity's role), OR both onsite booleans set
 * true together -- a Field Manager explicitly marking Digital Testing
 * done for the day, which must NOT touch billing lock or disable
 * Session Details edits. Confirmed these are deliberately different
 * concepts that happen to share a display state.
 *
 * PRACTICE is a real, structured exercise -- NOT the same thing as
 * Enrollment's existing practiceRun field, despite the similar name
 * (an earlier version of this comment wrongly conflated the two).
 * Practice means: the system provides three reference points (25%,
 * 50%, 75% opacity), then each student submits at least 3 of their own
 * points calibrated against those references. None of that structure
 * (reference points, student practice-point submissions) is modeled
 * yet -- this enum only tracks which of the four stages the session is
 * currently in.
 */
public enum OnsiteStage {
    SIGN_IN,
    PRACTICE,
    TESTING,
    CLOSED
}
