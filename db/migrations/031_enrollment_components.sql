-- ============================================================================
-- Migration 031: Enrollment components (Lecture Only / Field Only)
-- Reference: Michael, 2026-08-25 -- Client Portal Enroll rebuild.
--
-- Confirmed with Michael:
--   - Two real values: LECTURE_ONLY (self-paced lecture -- delivery
--     mechanism to be wired in separately later, but the enrollment
--     record itself is real now) and FIELD_ONLY. "Both" is a UI-level
--     choice, not a third stored value -- it results in two real
--     enrollment rows created together (one of each), not one row
--     with a "both" value.
--   - VR does NOT need its own component value -- Session.vrSession
--     is already the flag for that (a deliberate, confirmed design:
--     VR is a delivery-method modifier, never its own category). A
--     FIELD_ONLY enrollment into a vrSession=true session IS the VR
--     field enrollment; the session itself already carries that fact.
--   - In-person lecture requirement is established when a session is
--     planned with the client (pricing/scheduling, staff-side), not
--     something the enrollment record itself needs to track.
--
-- The existing (student_id, session_id) unique constraint (migration
-- 003) was built specifically to stop accidental duplicate enrollment
-- of the same student in the same session -- but under this new
-- shape, "Both" legitimately creates two rows for that exact
-- (student_id, session_id) pair. The constraint needs a third column
-- to keep blocking the real problem (a second LECTURE_ONLY, a second
-- FIELD_ONLY) while allowing exactly one of each per student per
-- session.
--
-- Existing rows default to FIELD_ONLY -- every enrollment created
-- before this migration was implicitly "the field portion" (the only
-- thing this system tracked before now), so this preserves their
-- real, historical meaning rather than guessing.
-- ============================================================================

ALTER TABLE enrollments ADD COLUMN enrollment_components VARCHAR(20) NOT NULL DEFAULT 'FIELD_ONLY';

ALTER TABLE enrollments DROP CONSTRAINT enrollments_student_id_session_id_key;

ALTER TABLE enrollments ADD CONSTRAINT enrollments_student_session_components_key
    UNIQUE (student_id, session_id, enrollment_components);
