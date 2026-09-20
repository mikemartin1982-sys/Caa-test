-- A live Smoke School creates one CertificationRun per participating
-- student. Every participant in the same class run deliberately shares the
-- same run_number, so (session_id, run_number) cannot be unique.
ALTER TABLE certification_runs
    DROP CONSTRAINT IF EXISTS certification_runs_session_id_run_number_key;

-- Preserve efficient lookup of all student runs in one class run without
-- imposing uniqueness across those students.
CREATE INDEX IF NOT EXISTS idx_certification_runs_session_run_number
    ON certification_runs (session_id, run_number);
