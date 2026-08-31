-- ============================================================================
-- Migration 016: Digital Testing Onsite Stage Control
-- Reference: real DIBs source (Digital-Testing section, disabled/read-only
-- there, controlled elsewhere) + direct confirmation with Michael,
-- 2026-08-16.
--
-- Two independent booleans, matching DIBs' actual field-level model
-- (onsite_field_signin_enable / onsite_field_testing_enable) -- NOT a
-- single stage enum. Stage (Sign-In / Practice / Testing / Closed) is
-- INFERRED from these two plus the existing closed_out flag (migration
-- 009), not stored separately:
--   closed_out=true                          -> Closed
--   sign_in_enabled=true                     -> Sign-In
--   sign_in_enabled=false, testing_enabled=true  -> Testing
--   both false, not closed out               -> Practice (a real,
--     structured exercise -- three reference points at 25/50/75%
--     opacity, then each student submits at least 3 of their own
--     points calibrated against them; NOT the same thing as
--     Enrollment's existing practice_run field despite the similar
--     name -- that structure isn't modeled yet, only the stage is).
-- ============================================================================

ALTER TABLE sessions ADD COLUMN onsite_sign_in_enabled BOOLEAN NOT NULL DEFAULT false;
ALTER TABLE sessions ADD COLUMN onsite_testing_enabled BOOLEAN NOT NULL DEFAULT false;
