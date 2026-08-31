-- ============================================================================
-- Migration 017: Live Digital Testing State
-- Reference: Michael, 2026-08-16 -- the real, live-test interaction model:
--
--   Sign-In -> (Practice, no digital mechanics needed -- driven verbally
--   by the Field Manager) -> Testing:
--
--   1. Operator records the TRUE opacity value for the current point via
--      their tablet, as they control the smoke generator.
--   2. Every actively-testing student independently glances at the smoke
--      and submits their own guess via the Digital Testing WebApp (a
--      slider, 0-100 in 5% increments).
--   3. Each guess is scored immediately against the true value (Method9
--      ScoringService.scoreObservation, already built) -- Digital
--      Testing Admin shows a live green/orange/red indicator per
--      student per point, using the EXACT thresholds already documented
--      in that service's own comments (< 15% green, 15-19% orange
--      UI-only warning, >= 20% red / actual failedReading).
--   4. The Operator advances to the next point once every active
--      student has submitted. After point 50 (25 White + 25 Black),
--      final scoring runs for every student's run.
--
-- This is SESSION-level shared state (one live point/true-value shared
-- by the whole class watching the same smoke), not per-CertificationRun
-- -- each student still gets their own CertificationRun + Observations
-- (the existing 1:1 per-student model), coordinated by writing the same
-- true value across every active student's run for the current point.
-- ============================================================================

ALTER TABLE sessions ADD COLUMN live_test_active BOOLEAN NOT NULL DEFAULT false;
ALTER TABLE sessions ADD COLUMN live_test_point_number SMALLINT;
ALTER TABLE sessions ADD COLUMN live_test_color plume_color;
ALTER TABLE sessions ADD COLUMN live_test_true_opacity SMALLINT;
