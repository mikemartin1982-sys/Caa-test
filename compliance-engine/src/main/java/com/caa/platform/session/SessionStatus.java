package com.caa.platform.session;
 
/**
 * Mirrors DIBs' existing calendar legend/status categories (staff review,
 * 2026-08-15) -- replaces the earlier placeholder enum, which never
 * matched real usage.
 *
 * "Publ & Conf VR" (DIBs' legend) is NOT a distinct value here -- it's
 * PUBLISHED_CONFIRMED displayed in a different color specifically when
 * schoolType == VR. That's a calendar-display concern, not backend state.
 */
public enum SessionStatus {
    /** Quote given to a prospective client, awaiting their decision (Private/Semi-Private, including VTCA). */
    PROPOSED,
    /** Quote given, client went with someone else -- session never happens. */
    LOST_BID,
    /** Default/active state, not yet published. */
    UNPUBLISHED,
    /** Published, but the Confirmed checkbox (Section 4c) hasn't been checked yet. */
    PUBLISHED_NOT_CONFIRMED,
    /** Published AND confirmed. */
    PUBLISHED_CONFIRMED,
    /** Was scheduled/confirmed, canceled before it happened. */
    CANCELED,
    /** Session happened, administrative close-out complete (Section 4g). */
    CLOSED_OUT
}
