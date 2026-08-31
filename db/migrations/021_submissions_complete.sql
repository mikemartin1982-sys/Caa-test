-- ============================================================================
-- Migration 021: Submissions-Complete Flag
-- Reference: Michael, 2026-08-17 -- completes the final-answers-
-- confirmation feature started in migration 020 (final_answers_confirmed
-- on certification_runs, signature_image_path on certifications).
--
-- submissions_complete is a NEW, separate signal from in_progress
-- (migration 018) -- in_progress still means "not yet graded/scored";
-- submissions_complete means "all required point observations are in,
-- but maybe not yet confirmed or graded." This split is what lets
-- split-run participants (done at point 25) stop being waited on for
-- POINT-BY-POINT gating the instant they finish, while remaining
-- gradable independently of whenever the full-run group finishes at
-- 50 -- the existing "don't make them wait" behavior, unchanged.
-- ============================================================================

ALTER TABLE certification_runs ADD COLUMN submissions_complete BOOLEAN NOT NULL DEFAULT false;
