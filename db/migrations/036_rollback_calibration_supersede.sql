-- ============================================================================
-- Migration 036: Rollback of migration 035
-- Reference: Michael, 2026-08-31.
--
-- 035 added a `superseded` column and a unique index to
-- calibration_records, based on an incorrect assumption that this
-- feature didn't already exist (it did -- CalibrationService.java,
-- confirmed from Michael's 8/14 backup, already implements the real,
-- correct design: multiple CalibrationRecord rows per TestingSystem
-- over time are expected and normal, with isCurrentlyValid() simply
-- reading the most recent one by date, not filtering by a supersede
-- flag).
--
-- Left as-is, 035's unique index would actively break the real
-- system's normal operation: every row defaults to superseded=false
-- (nothing in the real, correct code ever sets it true), so the very
-- next routine 6-month 5-Filter re-calibration for any TestingSystem
-- would be rejected by this constraint as a duplicate.
-- ============================================================================

DROP INDEX IF EXISTS idx_calibration_records_one_active_per_system;
ALTER TABLE calibration_records DROP COLUMN IF EXISTS superseded;
