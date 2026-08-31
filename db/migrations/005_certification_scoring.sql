-- ============================================================================
-- Migration 005: Method 9 Certification Scoring Architecture
-- Reference: CAA-Website-Rebuild-Architecture.md, Section 3, 3b
--
-- Core rules encoded here (see Section 3b for full narrative):
--  - Every CertificationRun is exactly 50 points (initial) or exactly 25
--    points (a White-only split-run retake) -- never a range.
--  - 50-point run: points 1-25 = White, 26-50 = Black, fixed order.
--  - 25-point retake: its own 1-25, White only. Black is NEVER split.
--  - Pass/fail is per-color, independently: cumulative deviation > 37 OR
--    any single reading missing by 20%+ = fail for that color. There is
--    no separate 15% threshold (15% is a UI-only warning color, Section 4g).
--  - Split-run eligibility: White fails + Black passes -> White-only retake
--    eligible (subject to CA/TX/VR restrictions). Black failing (regardless
--    of White) always requires a full new 50-point run, never a retake.
--  - Succession: a retake must share the same session_id as the run it
--    succeeds, with no other runs in between -- no time limit otherwise.
-- ============================================================================

CREATE TYPE plume_color AS ENUM ('WHITE', 'BLACK');

-- ---------------------------------------------------------------------------
-- certification_runs
-- ---------------------------------------------------------------------------
CREATE TABLE certification_runs (
    id                          BIGSERIAL PRIMARY KEY,
    session_id                   BIGINT NOT NULL REFERENCES sessions(id),
    run_number                    INTEGER NOT NULL,             -- e.g. Run 1, Run 2 within the session
    point_count                    SMALLINT NOT NULL CHECK (point_count IN (25, 50)),

    performed_at                    TIMESTAMPTZ NOT NULL,

    -- Succession chain for split-run retakes (Section 3b): must share the
    -- same session_id as the run being succeeded, no runs in between.
    prior_run_id                     BIGINT REFERENCES certification_runs(id),

    -- Chain of custody (Section 4h)
    trailer_id                        BIGINT NOT NULL REFERENCES trailers(id),
    testing_system_id                  BIGINT NOT NULL REFERENCES testing_systems(id),
    administered_by                     BIGINT NOT NULL REFERENCES staff_users(id),

    -- White result -- always present
    white_cumulative_deviation            NUMERIC(6,2),
    white_failed_reading                    BOOLEAN NOT NULL DEFAULT false,  -- any single reading missed by >= 20%
    white_pass                                BOOLEAN,

    -- Black result -- NULL when point_count = 25 (a White-only retake never
    -- touches Black; Black is never split, Section 3b)
    black_cumulative_deviation              NUMERIC(6,2),
    black_failed_reading                      BOOLEAN NOT NULL DEFAULT false,
    black_pass                                  BOOLEAN,

    created_at                                   TIMESTAMPTZ NOT NULL DEFAULT now(),

    UNIQUE (session_id, run_number),

    CONSTRAINT chk_25pt_is_white_only
        CHECK (point_count = 50 OR (black_cumulative_deviation IS NULL AND black_pass IS NULL)),

    CONSTRAINT chk_50pt_requires_both_colors
        CHECK (point_count = 25 OR (black_cumulative_deviation IS NOT NULL))
);

-- Now that certification_runs exists, wire up the ComplianceFlag join table's
-- deferred FK from migration 004.
ALTER TABLE compliance_flag_runs
    ADD CONSTRAINT fk_compliance_flag_runs_run
    FOREIGN KEY (certification_run_id) REFERENCES certification_runs(id);

CREATE INDEX idx_certification_runs_session ON certification_runs (session_id);
CREATE INDEX idx_certification_runs_prior ON certification_runs (prior_run_id);
CREATE INDEX idx_certification_runs_system ON certification_runs (testing_system_id);

-- ---------------------------------------------------------------------------
-- observations
-- One row per point. 50 rows for an initial run (1-25 White, 26-50 Black,
-- fixed order), 25 rows for a split-run retake (its own 1-25, White only).
-- true_opacity_value and student_estimated_opacity both render to the
-- nearest 5% (Digital Testing / VEO form-reader convention, Section 4g) --
-- both are always multiples of 5.
-- ---------------------------------------------------------------------------
CREATE TABLE observations (
    id                          BIGSERIAL PRIMARY KEY,
    certification_run_id         BIGINT NOT NULL REFERENCES certification_runs(id) ON DELETE CASCADE,
    plume_reference                VARCHAR(255),          -- VR: plume filename; in-person: Stacktest.net reference
    color                            plume_color NOT NULL,
    point_number                      SMALLINT NOT NULL,     -- 1-25 (White) or 26-50 (Black) for a 50pt run; 1-25 for a 25pt retake

    true_opacity_value                  SMALLINT NOT NULL CHECK (true_opacity_value % 5 = 0),
    student_estimated_opacity             SMALLINT NOT NULL CHECK (student_estimated_opacity % 5 = 0),
    deviation                               SMALLINT NOT NULL,  -- ABS(true - estimated), always a multiple of 5
    failed_reading                            BOOLEAN NOT NULL DEFAULT false,  -- deviation >= 20

    UNIQUE (certification_run_id, point_number)
);

CREATE INDEX idx_observations_run ON observations (certification_run_id);
CREATE INDEX idx_observations_color ON observations (certification_run_id, color);

-- ---------------------------------------------------------------------------
-- certifications
-- The overall pass/fail result for an Enrollment. white_run_id / black_run_id
-- point to which CertificationRun satisfied each color -- same run for a
-- standard pass, two different runs only for a White-split-run pass
-- (Black is always satisfied by the run that satisfies the overall pass).
-- ---------------------------------------------------------------------------
CREATE TABLE certifications (
    id                      BIGSERIAL PRIMARY KEY,
    enrollment_id             BIGINT NOT NULL REFERENCES enrollments(id) UNIQUE,
    issue_date                  DATE,
    expiration_date               DATE,
    pdf_certificate_link            VARCHAR(500),

    black_cumulative_deviation        NUMERIC(6,2),
    white_cumulative_deviation          NUMERIC(6,2),
    any_failed_reading                    BOOLEAN NOT NULL DEFAULT false,

    white_run_id                            BIGINT REFERENCES certification_runs(id),
    black_run_id                              BIGINT REFERENCES certification_runs(id),

    pass_fail                                  BOOLEAN,

    created_at                                   TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at                                     TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX idx_certifications_enrollment ON certifications (enrollment_id);

-- ---------------------------------------------------------------------------
-- split_run_authorizations
-- Section 3b: only ever applies to a White failure (Black is never
-- split-eligible). Immutable once saved. Toggle must be disabled for
-- Western (CA) / Texas regions, or VR format (EPA Alt-152-a).
-- ---------------------------------------------------------------------------
CREATE TABLE split_run_authorizations (
    id                      BIGSERIAL PRIMARY KEY,
    certification_id          BIGINT NOT NULL REFERENCES certifications(id),
    allowed                      BOOLEAN NOT NULL,
    authorized_by                  BIGINT NOT NULL REFERENCES staff_users(id),
    authorized_at                    TIMESTAMPTZ NOT NULL DEFAULT now(),
    region_at_time                     region_type,
    format_at_time                       session_format
    -- Immutability enforced at the application layer; consider
    -- REVOKE UPDATE, DELETE ON split_run_authorizations FROM app_role;
);

CREATE INDEX idx_split_run_auth_certification ON split_run_authorizations (certification_id);

-- ---------------------------------------------------------------------------
-- Now wire up the FKs deferred from earlier migrations, since
-- certification_runs finally exists.
-- ---------------------------------------------------------------------------
ALTER TABLE students
    ADD CONSTRAINT fk_students_texas_practice_run
    FOREIGN KEY (texas_practice_run_id) REFERENCES certification_runs(id);

ALTER TABLE enrollments
    ADD CONSTRAINT fk_enrollments_certifying_run
    FOREIGN KEY (certifying_run_id) REFERENCES certification_runs(id);

ALTER TABLE enrollments
    ADD CONSTRAINT fk_enrollments_practice_run
    FOREIGN KEY (practice_run_id) REFERENCES certification_runs(id);
