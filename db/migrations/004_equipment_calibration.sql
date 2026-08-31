-- ============================================================================
-- Migration 004: Testing Equipment, Trailers, and 5-Filter Calibration
-- Reference: CAA-Website-Rebuild-Architecture.md, Section 4h
-- ============================================================================

CREATE TYPE system_designation AS ENUM ('PRIMARY', 'SECONDARY');
CREATE TYPE maintenance_event_type AS ENUM ('SIGNIFICANT_REPAIR', 'REPLACE');
CREATE TYPE maintenance_component AS ENUM ('MONITOR', 'LIGHT_SOURCE', 'PHOTO_CELL', 'OP_AMP_CARD', 'DATA_SOURCE');
CREATE TYPE calibration_trigger AS ENUM ('SCHEDULED_6_MONTH', 'FOLLOWING_REPAIR', 'FOLLOWING_REPLACE');
CREATE TYPE compliance_flag_status AS ENUM ('OPEN', 'ACKNOWLEDGED', 'RESOLVED');

-- ---------------------------------------------------------------------------
-- trailers
-- Section 4h: each trailer individually identified (e.g. "Abby"), carries
-- an EPA Method 9 (1974) equipment set. Has one Primary and one Secondary
-- TestingSystem, and its own set of 3 CalibrationPanes.
-- ---------------------------------------------------------------------------
CREATE TABLE trailers (
    id                      BIGSERIAL PRIMARY KEY,
    identifier              VARCHAR(100) NOT NULL UNIQUE,  -- e.g. "Abby"
    equipment_set_description TEXT,
    created_at               TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- ---------------------------------------------------------------------------
-- calibration_panes
-- Section 4h: each Trailer has its own set of 3 glass panes with known
-- opacity values, NIST-verified annually. Unique identifier ties to tablet.
-- ---------------------------------------------------------------------------
CREATE TABLE calibration_panes (
    id                          BIGSERIAL PRIMARY KEY,
    trailer_id                  BIGINT NOT NULL REFERENCES trailers(id),
    pane_identifier              VARCHAR(100) NOT NULL UNIQUE,  -- associated to the tablet
    certified_opacity_value       NUMERIC(5,2) NOT NULL,
    last_nist_verification_date    DATE NOT NULL,               -- annual cadence
    created_at                     TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX idx_calibration_panes_trailer ON calibration_panes (trailer_id);

-- ---------------------------------------------------------------------------
-- testing_systems
-- Section 4h: Primary + Secondary per trailer. Each component has a
-- distinct identifier scheme (Light Source: numerical, Photo Cell: alpha
-- + internal Op-Amp Card ID, Data Source: 3-digit numerical, Monitor: TBD).
-- ---------------------------------------------------------------------------
CREATE TABLE testing_systems (
    id                  BIGSERIAL PRIMARY KEY,
    trailer_id          BIGINT NOT NULL REFERENCES trailers(id),
    designation          system_designation NOT NULL,

    light_source_id       VARCHAR(50),   -- numerical identifier
    photo_cell_id          VARCHAR(50),   -- alpha identifier
    op_amp_card_id          VARCHAR(50),   -- distinct sub-component identifier, housed inside Photo Cell
    data_source_id           VARCHAR(3),    -- three-digit numerical identifier
    monitor_id                VARCHAR(50),   -- identifier scheme not yet specified (Section 9 open item)

    created_at                TIMESTAMPTZ NOT NULL DEFAULT now(),
    UNIQUE (trailer_id, designation)
);

-- ---------------------------------------------------------------------------
-- maintenance_events
-- Section 4h: Significant Repair (component swap within a piece) or
-- Replace (whole piece swapped) — triggers a new 5-Filter requirement
-- independent of the 6-month schedule.
-- ---------------------------------------------------------------------------
CREATE TABLE maintenance_events (
    id                  BIGSERIAL PRIMARY KEY,
    testing_system_id    BIGINT NOT NULL REFERENCES testing_systems(id),
    event_type            maintenance_event_type NOT NULL,
    component_affected     maintenance_component NOT NULL,
    event_date              DATE NOT NULL DEFAULT CURRENT_DATE,
    created_at               TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX idx_maintenance_events_system ON maintenance_events (testing_system_id);

-- ---------------------------------------------------------------------------
-- calibration_records ("5-Filter")
-- Section 4h: required every 6 months, or immediately on a Significant
-- Repair/Replace event. Tracks per-parameter results for the six EPA
-- Section 3.1.2 criteria — any one parameter failing invalidates the whole
-- 5-Filter even if others pass. Sourced from Stacktest.net (platform reads,
-- does not originate).
-- ---------------------------------------------------------------------------
CREATE TABLE calibration_records (
    id                          BIGSERIAL PRIMARY KEY,
    testing_system_id            BIGINT NOT NULL REFERENCES testing_systems(id),
    performed_at                  TIMESTAMPTZ NOT NULL,
    trigger_reason                 calibration_trigger NOT NULL,

    -- EPA Method 9 Section 3.1.2 parameters (Section 4h) — each independently pass/fail
    light_source_voltage_pass        BOOLEAN,   -- incandescent lamp +/-5% of nominal rated voltage
    photocell_spectral_response_pass  BOOLEAN,   -- photopic response, +/-3% opacity
    angle_of_view_pass                 BOOLEAN,   -- 15 degrees maximum total angle
    angle_of_projection_pass            BOOLEAN,   -- 15 degrees maximum total angle
    calibration_error_pass               BOOLEAN,   -- zero/span drift max +/-1% opacity over 30 min
    response_time_pass                     BOOLEAN,   -- +/-5 seconds

    overall_pass                            BOOLEAN NOT NULL,
    expires_at                                TIMESTAMPTZ,  -- performed_at + 6 months, computed at app layer

    created_at                                 TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX idx_calibration_records_system ON calibration_records (testing_system_id);
CREATE INDEX idx_calibration_records_expires ON calibration_records (expires_at);

-- ---------------------------------------------------------------------------
-- pane_readings
-- Section 4h: the actual 5-Filter procedure. 3 panes x 5 readings each,
-- low-to-high succession -- 15 rows per CalibrationRecord. Tolerance +/-3%
-- from the pane's certified opacity value. This is where "5-Filter" gets
-- its name (5 repetitions per pane).
-- ---------------------------------------------------------------------------
CREATE TABLE pane_readings (
    id                      BIGSERIAL PRIMARY KEY,
    calibration_record_id    BIGINT NOT NULL REFERENCES calibration_records(id) ON DELETE CASCADE,
    calibration_pane_id       BIGINT NOT NULL REFERENCES calibration_panes(id),
    sequence_number             SMALLINT NOT NULL CHECK (sequence_number BETWEEN 1 AND 5),
    recorded_value                NUMERIC(5,2) NOT NULL,   -- from Monitor display, via Chart Recorder
    deviation                       NUMERIC(5,2) NOT NULL,   -- recorded_value vs pane's certified_opacity_value
    within_tolerance                  BOOLEAN NOT NULL,        -- deviation within +/-3%

    UNIQUE (calibration_record_id, calibration_pane_id, sequence_number)
);

CREATE INDEX idx_pane_readings_record ON pane_readings (calibration_record_id);

-- ---------------------------------------------------------------------------
-- daily_calibration_checks
-- Section 4h: CAA quality-assurance practice, NOT a Method requirement.
-- Operator performs each day before a school begins, using Tablet/Chart
-- Recorder. Lighter-weight than CalibrationRecord -- no ComplianceFlag tie-in.
-- ---------------------------------------------------------------------------
CREATE TABLE daily_calibration_checks (
    id                  BIGSERIAL PRIMARY KEY,
    testing_system_id    BIGINT NOT NULL REFERENCES testing_systems(id),
    session_day_id         BIGINT NOT NULL REFERENCES session_days(id),
    operator_id              BIGINT NOT NULL REFERENCES staff_users(id),
    check_date                DATE NOT NULL DEFAULT CURRENT_DATE,
    result                      BOOLEAN NOT NULL,
    created_at                   TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- ---------------------------------------------------------------------------
-- compliance_flags
-- Section 4h: raised when a CertificationRun happens under an expired or
-- failed 5-Filter. Does NOT block testing in real time -- flagged for audit
-- review, must be resolved once staff are aware. Tracked to completion.
-- ---------------------------------------------------------------------------
CREATE TABLE compliance_flags (
    id                      BIGSERIAL PRIMARY KEY,
    testing_system_id        BIGINT NOT NULL REFERENCES testing_systems(id),
    reason                     VARCHAR(50) NOT NULL,  -- '5filter_expired' | '5filter_failed'
    raised_at                    TIMESTAMPTZ NOT NULL DEFAULT now(),
    status                         compliance_flag_status NOT NULL DEFAULT 'OPEN',
    resolution_notes                 TEXT,
    resolved_by                       BIGINT REFERENCES staff_users(id),
    resolved_at                        TIMESTAMPTZ
);

-- Links a ComplianceFlag to the CertificationRun(s) administered during the
-- expired/failed window. Table added here; certification_run_id FK is
-- deferred until migration 005 defines certification_runs.
CREATE TABLE compliance_flag_runs (
    compliance_flag_id     BIGINT NOT NULL REFERENCES compliance_flags(id) ON DELETE CASCADE,
    certification_run_id   BIGINT NOT NULL,  -- FK added in migration 005
    PRIMARY KEY (compliance_flag_id, certification_run_id)
);

-- ---------------------------------------------------------------------------
-- chart_recorder_exports
-- Section 4h: .ZIP export emailed at school completion. Retention +
-- verification artifact (Chasity confirms equipment/5-Filter validity) +
-- issue/delay notes ("No Issues" if none). NOT the live data path --
-- Stacktest.net is (Section 4g). No automated parsing required.
-- ---------------------------------------------------------------------------
CREATE TABLE chart_recorder_exports (
    id                  BIGSERIAL PRIMARY KEY,
    session_id           BIGINT NOT NULL REFERENCES sessions(id),
    generated_at           TIMESTAMPTZ NOT NULL DEFAULT now(),
    zip_file_reference       VARCHAR(500),
    recipients                 TEXT,            -- comma-separated; at minimum Chasity Miranda; operator self-send is common practice
    issue_delay_notes            TEXT NOT NULL DEFAULT 'No Issues'
);

CREATE INDEX idx_chart_recorder_exports_session ON chart_recorder_exports (session_id);
