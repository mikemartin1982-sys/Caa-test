-- ============================================================================
-- Migration 002: Session, scheduling, and related entities
-- Reference: CAA-Website-Rebuild-Architecture.md, Section 3, 3a, 4c, 4d
-- ============================================================================

CREATE TYPE school_type AS ENUM ('VR', 'PRIVATE', 'SEMI_PRIVATE', 'PUBLIC');
CREATE TYPE session_format AS ENUM ('IN_PERSON', 'VR', 'PRIVATE', 'ONLINE');
CREATE TYPE session_status AS ENUM ('scheduled', 'confirmed', 'in_progress', 'completed', 'cancelled');
-- NOTE: deliberately left lowercase and NOT fixed to match Java's uppercase
-- convention (unlike the other enums in this file) -- this whole type gets
-- dropped and replaced by migration 008, which needs these exact lowercase
-- values to correctly match the OLD data during its backfill step. Fixing
-- this here would break that migration's WHEN clauses.
CREATE TYPE region_type AS ENUM ('CENTRAL', 'EASTERN', 'WESTERN', 'TEXAS');

-- ---------------------------------------------------------------------------
-- sessions
-- Section 3: the central scheduling entity. schoolType is a deliberate
-- staff choice (Section 4) — Private is locked to the host only, structurally;
-- Semi-Private unlocks the self-service authorized-client mechanism.
-- No hard capacity cap (Section 4) — only the Texas proctor ratio constrains
-- headcount, evaluated per staggered_arrival_blocks, not total enrollment.
-- ---------------------------------------------------------------------------
CREATE TABLE sessions (
    id                          BIGSERIAL PRIMARY KEY,
    school_type                 school_type NOT NULL,
    format                      session_format NOT NULL,

    -- Region: auto-derived from physical_address at save time (Section 3b).
    -- Staff can override if the derived region is inaccurate; override is logged.
    region                      region_type,
    region_overridden           BOOLEAN NOT NULL DEFAULT false,
    region_override_by          BIGINT REFERENCES staff_users(id),
    region_override_at          TIMESTAMPTZ,

    -- Location (Section 4d) — applies to all in-person types; public display
    -- of address/GPS is Public-only, Private/Semi-Private is staff nav only.
    location_name                VARCHAR(255),
    address_street                VARCHAR(255),
    address_city                  VARCHAR(100),
    address_state                 VARCHAR(2),
    address_zip                   VARCHAR(10),
    grid_lat                      NUMERIC(10,6),
    grid_lng                      NUMERIC(10,6),
    grid_pin_distance_miles       NUMERIC(6,2),   -- computed on save; warn if > 0.5 mi (Section 4d)

    -- Private / Semi-Private pricing (Section 3a)
    quoted_price                  NUMERIC(10,2),
    quoted_headcount               INTEGER,
    overage_rate_per_person        NUMERIC(8,2),

    -- Public pricing (Section 3a) — management-set fixed rates
    field_certification_price      NUMERIC(8,2),
    self_paced_lecture_price        NUMERIC(8,2),
    late_fee_amount                 NUMERIC(8,2),
    late_fee_day_threshold          INTEGER,        -- e.g. enrollments under 7 days out

    external_registration_name      VARCHAR(255),   -- third-party registration override (Section 4d)
    external_registration_phone     VARCHAR(20),
    external_registration_notes     TEXT,

    public_session_notes            TEXT,           -- freeform, public-facing (Section 4d)

    -- PO / NET terms (Section 3a) — staff-entered directly on the session
    po_number                       VARCHAR(100),
    net_terms_days                  INTEGER,        -- e.g. 30 / 60 / 90

    status                          session_status NOT NULL DEFAULT 'scheduled',

    -- Publish gate (Section 4c): available only once required fields are
    -- complete (location_name, address, pricing, GPS). Sticky once true.
    published                       BOOLEAN NOT NULL DEFAULT false,

    -- Texas proctor assignment (Section 4)
    proctors_assigned               INTEGER NOT NULL DEFAULT 1,

    field_manager_id                BIGINT REFERENCES staff_users(id),
    operator_id                     BIGINT REFERENCES staff_users(id),

    is_public_vr_session             BOOLEAN NOT NULL DEFAULT false,  -- recurring monthly VR cohort (Section 4b)

    -- "Copy Forward 6 Months" provenance (Section 4c)
    copied_from_session_id           BIGINT REFERENCES sessions(id),

    created_at                       TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at                       TIMESTAMPTZ NOT NULL DEFAULT now(),

    CONSTRAINT chk_quoted_headcount_only_private
        CHECK (quoted_headcount IS NULL OR school_type IN ('PRIVATE', 'SEMI_PRIVATE'))
);

CREATE INDEX idx_sessions_school_type ON sessions (school_type);
CREATE INDEX idx_sessions_region ON sessions (region);
CREATE INDEX idx_sessions_published ON sessions (published);
CREATE INDEX idx_sessions_is_public_vr ON sessions (is_public_vr_session);

-- ---------------------------------------------------------------------------
-- session_days
-- Section 3: multi-day session support. Private/Semi-Private start time is
-- negotiated; Public defaults ~8am-5pm. Client-level/session-level data
-- carries across all days of the same Session.
-- ---------------------------------------------------------------------------
CREATE TABLE session_days (
    id              BIGSERIAL PRIMARY KEY,
    session_id      BIGINT NOT NULL REFERENCES sessions(id) ON DELETE CASCADE,
    day_number      INTEGER NOT NULL,       -- 1, 2, 3...
    session_date    DATE NOT NULL,
    start_time      TIME NOT NULL,
    end_time        TIME NOT NULL,
    UNIQUE (session_id, day_number)
);

-- ---------------------------------------------------------------------------
-- session_authorized_clients
-- Section 3 / 4: shared Private/Semi-Private mechanism. is_host=true entry
-- is created when staff enter a Client ID. For 'private' school_type, this
-- must be the ONLY row (enforced at the application layer per Section 4 —
-- Private is structurally locked, no additions by staff or client).
-- ---------------------------------------------------------------------------
CREATE TABLE session_authorized_clients (
    id              BIGSERIAL PRIMARY KEY,
    session_id      BIGINT NOT NULL REFERENCES sessions(id) ON DELETE CASCADE,
    client_id       BIGINT NOT NULL REFERENCES clients(id),
    is_host         BOOLEAN NOT NULL DEFAULT false,
    added_by        BIGINT REFERENCES staff_users(id),   -- null if added by the client via portal
    added_at        TIMESTAMPTZ NOT NULL DEFAULT now(),
    UNIQUE (session_id, client_id)
);

CREATE UNIQUE INDEX idx_one_host_per_session
    ON session_authorized_clients (session_id)
    WHERE is_host = true;

-- ---------------------------------------------------------------------------
-- staggered_arrival_blocks
-- Section 4: Texas-only. Proctor threshold (26) evaluated per block, not
-- total session enrollment. A block at/under 26 satisfies the requirement
-- on its own.
-- ---------------------------------------------------------------------------
CREATE TABLE staggered_arrival_blocks (
    id              BIGSERIAL PRIMARY KEY,
    session_day_id  BIGINT NOT NULL REFERENCES session_days(id) ON DELETE CASCADE,
    block_number    INTEGER NOT NULL,
    start_time      TIME NOT NULL,
    end_time        TIME NOT NULL,
    UNIQUE (session_day_id, block_number)
);

-- ---------------------------------------------------------------------------
-- session_comments
-- Section 4c: Confirmed checkbox + required comment, and Team Comments.
-- Write-once / immutable — no edit or delete, ever. Timestamps stored UTC,
-- displayed Central Time as M/D/YYYY h:mm AM/PM. Scoped strictly to the
-- individual Session instance — does not carry forward on Copy Forward.
-- ---------------------------------------------------------------------------
CREATE TYPE session_comment_type AS ENUM ('CONFIRMATION', 'TEAM_COMMENT');

CREATE TABLE session_comments (
    id              BIGSERIAL PRIMARY KEY,
    session_id      BIGINT NOT NULL REFERENCES sessions(id) ON DELETE CASCADE,
    comment_type    session_comment_type NOT NULL,
    text            TEXT NOT NULL,
    author_id       BIGINT NOT NULL REFERENCES staff_users(id),
    created_at_utc  TIMESTAMPTZ NOT NULL DEFAULT now()
    -- Immutability enforced at the application layer (no UPDATE/DELETE exposed);
    -- consider REVOKE UPDATE, DELETE ON session_comments FROM app_role; at deploy time.
);

CREATE INDEX idx_session_comments_session ON session_comments (session_id);
