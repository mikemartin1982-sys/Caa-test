-- ============================================================================
-- Migration 018: CertificationRun In-Progress Flag
-- Reference: real live-test flow (LiveTestingService, migration 017)
-- collided with the existing chk_50pt_requires_both_colors constraint,
-- caught live 2026-08-16.
--
-- chk_50pt_requires_both_colors (migration 005) assumed a CertificationRun
-- is always created ATOMICALLY with its full scoring already computed --
-- true for the original batch-creation endpoint (CertificationRunController
-- .create(), which scores in-memory before the first save), but the new
-- live-test flow genuinely needs to create an unscored, in-progress run
-- FIRST (so incoming Observations have a real certification_run_id to
-- attach to as each point comes in), and only scores it once all 50
-- points are collected.
--
-- Fix: an in_progress flag, defaulting false so the existing batch-
-- creation path (always already-scored at insert time) needs NO changes
-- at all -- only LiveTestingService.startLiveTest() explicitly sets this
-- true, and completeLiveTest() flips it back to false once real scoring
-- runs. The constraint now only applies once a run is no longer in
-- progress.
-- ============================================================================

ALTER TABLE certification_runs ADD COLUMN in_progress BOOLEAN NOT NULL DEFAULT false;

ALTER TABLE certification_runs DROP CONSTRAINT chk_50pt_requires_both_colors;

ALTER TABLE certification_runs ADD CONSTRAINT chk_50pt_requires_both_colors
    CHECK (point_count = 25 OR in_progress OR black_cumulative_deviation IS NOT NULL);
