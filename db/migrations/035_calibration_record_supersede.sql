-- ============================================================================
-- Migration 035: Truck/Trailer Equipment feature -- 5-Filter supersede
-- Reference: Michael, 2026-08-31.
--
-- calibration_records already exists (confirmed with Michael as the
-- current, real server schema matching CalibrationRecord.java as
-- already shared) -- this migration adds only what's new: supersede
-- support, matching LectureCertificate's exact pattern (Lecture
-- Certificate Upload feature, migration 034). A new 5-Filter record
-- for a TestingSystem supersedes its prior one; full history retained,
-- never deleted. Scoped per testing_system_id, not per trailer -- a
-- Trailer's Primary and Secondary systems each keep their own,
-- entirely independent history.
-- ============================================================================

ALTER TABLE calibration_records ADD COLUMN superseded BOOLEAN NOT NULL DEFAULT FALSE;

-- Michael, 2026-08-31 -- WARNING, worth checking before running this:
-- if any testing_system_id already has more than one existing
-- calibration_records row, this CREATE UNIQUE INDEX will fail
-- immediately -- every pre-existing row defaults to superseded=false
-- above, so two or more rows for the same system would violate this
-- constraint the moment it's created. Likely a non-issue on a fresh/
-- test dataset, but worth a quick check first:
--   SELECT testing_system_id, COUNT(*) FROM calibration_records
--   GROUP BY testing_system_id HAVING COUNT(*) > 1;
-- If that returns any rows, mark all but the newest per system
-- superseded=true manually before running the index creation below.
--
-- Michael, 2026-08-31 -- same defensive guarantee as
-- idx_lecture_certificates_one_active_per_student (migration 034): at
-- most one non-superseded 5-Filter record per TestingSystem, enforced
-- at the database itself, not just trusted to the service layer's own
-- supersede-then-insert logic. Also directly serves
-- CalibrationService.isCurrentlyValid()'s own real lookup.
CREATE UNIQUE INDEX idx_calibration_records_one_active_per_system
    ON calibration_records (testing_system_id) WHERE NOT superseded;
