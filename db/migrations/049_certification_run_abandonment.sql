-- An unfinished live-test attempt may be ended when staff mark its student
-- DNC/DNA. Preserve its genuine observations for audit, but distinguish it
-- from both an active run and a normally completed/scored run.
ALTER TABLE certification_runs
    ADD COLUMN abandoned_at TIMESTAMPTZ;

ALTER TABLE certification_runs
    DROP CONSTRAINT chk_50pt_requires_both_colors;

ALTER TABLE certification_runs
    ADD CONSTRAINT chk_50pt_requires_both_colors
    CHECK (
        point_count = 25
        OR in_progress
        OR abandoned_at IS NOT NULL
        OR black_cumulative_deviation IS NOT NULL
    );

ALTER TABLE certification_runs
    ADD CONSTRAINT chk_abandoned_run_not_in_progress
    CHECK (abandoned_at IS NULL OR NOT in_progress);
