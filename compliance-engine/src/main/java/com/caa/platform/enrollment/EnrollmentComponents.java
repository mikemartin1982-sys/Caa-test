package com.caa.platform.enrollment;

/**
 * Michael, 2026-08-25 -- Client Portal Enroll rebuild. Confirmed with
 * Michael: two real values only. "Both" is a UI-level choice, not a
 * third stored value here -- it results in two real Enrollment rows
 * created together (one LECTURE_ONLY, one FIELD_ONLY), not one row
 * carrying a "both" value. See migration 031's own comment for the
 * full reasoning, including why VR doesn't need its own value here
 * (Session.vrSession already carries that fact).
 */
public enum EnrollmentComponents {
    LECTURE_ONLY,
    FIELD_ONLY
}
