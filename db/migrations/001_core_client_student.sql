-- ============================================================================
-- Migration 001: Core Client, Student, Staff, and Inquiry entities
-- Reference: CAA-Website-Rebuild-Architecture.md, Section 3, 3c
-- ============================================================================

-- ---------------------------------------------------------------------------
-- staff_users
-- Section 3: role-based access. Compliance Administrator gates Method 9 /
-- Alt-152-a rule changes (Section 3b) and is currently held only by
-- Derek Mason and Joe Spivey.
-- ---------------------------------------------------------------------------
CREATE TYPE staff_role AS ENUM ('STAFF', 'COMPLIANCE_ADMINISTRATOR');

CREATE TABLE staff_users (
    id              BIGSERIAL PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,
    username        VARCHAR(100) NOT NULL UNIQUE,
    role            staff_role NOT NULL DEFAULT 'STAFF',
    created_at      TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- ---------------------------------------------------------------------------
-- clients
-- Section 3 / 3c: Company is the raw legal name as staff enters it.
-- record_name and portal_display_name are auto-derived (Section 3c) —
-- derivation happens in the application layer, not as a DB trigger, since
-- the legal-suffix-stripping logic needs a maintained suffix list rather
-- than blind comma-splitting (Section 3c "Resolved" note).
-- ---------------------------------------------------------------------------
CREATE TABLE clients (
    id                      BIGSERIAL PRIMARY KEY,
    qbo_reference_id        VARCHAR(100),                  -- QuickBooks Customer ID
    company                 VARCHAR(255) NOT NULL,          -- raw legal name, e.g. "Compliance Assurance Associates, Inc"
    record_name             VARCHAR(255),                   -- auto-derived: "{Company, suffix stripped} - {City}, {ST}"
    portal_display_name     VARCHAR(255),                   -- auto-derived: "{Company, suffix stripped}"
    first_name              VARCHAR(100),
    last_name               VARCHAR(100),
    address                 VARCHAR(255),
    city                    VARCHAR(100),
    state                   VARCHAR(2),
    zip                     VARCHAR(10),
    phone                   VARCHAR(20),
    email                   VARCHAR(255),
    lead_source              VARCHAR(100),                   -- "How They Heard About CAA" (Section 3c)
    pref_newsletter          BOOLEAN NOT NULL DEFAULT false,
    pref_class_confirms      BOOLEAN NOT NULL DEFAULT false,
    pref_cert_reminders      BOOLEAN NOT NULL DEFAULT false,
    vr_client                BOOLEAN NOT NULL DEFAULT false, -- gates Public VR Session portal eligibility (Section 4b)
    vr_pricing_override_rate NUMERIC(8,2),                   -- nullable custom per-token rate (Section 4b)
    vr_pricing_no_cost       BOOLEAN NOT NULL DEFAULT false,  -- No-Cost/$0 flag, e.g. government agencies (Section 4b)
    brevo_contact_id         VARCHAR(100),                   -- separate reference; one-directional sync platform -> Brevo (Section 3)
    created_at               TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at               TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX idx_clients_company ON clients (company);
CREATE INDEX idx_clients_qbo_reference_id ON clients (qbo_reference_id);

-- VR pricing override review trail (Section 4b: "flagged for Derek/Joe review
-- when set or changed, but never hard-blocked from management setting it
-- directly"). Immutable append-only log, not a gate on the write itself.
CREATE TABLE vr_pricing_override_reviews (
    id              BIGSERIAL PRIMARY KEY,
    client_id       BIGINT NOT NULL REFERENCES clients(id),
    changed_by      BIGINT NOT NULL REFERENCES staff_users(id),
    new_rate        NUMERIC(8,2),
    new_no_cost     BOOLEAN,
    changed_at      TIMESTAMPTZ NOT NULL DEFAULT now(),
    review_status   VARCHAR(20) NOT NULL DEFAULT 'pending_review' -- pending_review / reviewed
);

-- ---------------------------------------------------------------------------
-- inquiries
-- Section 3c: public "New Client Account" form submissions land here as
-- structured data. Deliberately does NOT auto-create a client record
-- (security decision against bot/bad-actor abuse) — staff convert manually.
-- ---------------------------------------------------------------------------
CREATE TYPE inquiry_status AS ENUM ('PENDING', 'CONVERTED');

CREATE TABLE inquiries (
    id                      BIGSERIAL PRIMARY KEY,
    company                 VARCHAR(255) NOT NULL,
    first_name              VARCHAR(100),
    last_name               VARCHAR(100),
    email                   VARCHAR(255),
    phone                   VARCHAR(20),
    company_address         VARCHAR(255),
    city                    VARCHAR(100),
    state                   VARCHAR(2),
    zip                     VARCHAR(10),
    lead_source              VARCHAR(100),
    pref_newsletter          BOOLEAN NOT NULL DEFAULT false,
    pref_class_confirms      BOOLEAN NOT NULL DEFAULT false,
    pref_cert_reminders      BOOLEAN NOT NULL DEFAULT false,
    submitted_at             TIMESTAMPTZ NOT NULL DEFAULT now(),
    status                   inquiry_status NOT NULL DEFAULT 'PENDING',
    converted_client_id      BIGINT REFERENCES clients(id)   -- paper trail: set when staff convert to a Client
);

-- ---------------------------------------------------------------------------
-- students
-- Section 3: "employees" managed by a client via the Client Portal.
-- practice_run_* and lecture_* are person-level facts (not per-enrollment) —
-- they cascade to the Client Page and every Session Roster the student
-- appears on (Section 4f).
-- ---------------------------------------------------------------------------
CREATE TABLE students (
    id                          BIGSERIAL PRIMARY KEY,
    student_number              VARCHAR(50) NOT NULL UNIQUE,  -- used for lecture sign-in (Section 4f)
    name                        VARCHAR(255) NOT NULL,
    phone                       VARCHAR(20) NOT NULL,          -- required, need not be unique (Section 3)
    email                       VARCHAR(255) NOT NULL,         -- required, need not be unique (Section 3)
    cross_provider_identifier   VARCHAR(100),                  -- for tracking across providers (practice run, etc.)
    employer_client_id          BIGINT REFERENCES clients(id), -- primary employer, distinct from billing client on an Enrollment

    -- Texas practice-run rule (Section 4) — Texas only, tracked per person
    texas_practice_run_completed BOOLEAN NOT NULL DEFAULT false,
    texas_practice_run_id        BIGINT,                        -- FK added in migration 004 (certification_runs)

    -- Lecture completion — person-level fact (Section 4f)
    lecture_complete             BOOLEAN NOT NULL DEFAULT false,
    lecture_completion_date      DATE,
    lecture_completion_source    VARCHAR(20),                   -- 'caa_lecture' | 'uploaded_certificate'
    lecture_certificate_upload   VARCHAR(500),                  -- file ref, if uploaded from another provider

    -- Staff override flags (Section 4b) — independent of enrollment status
    self_paced_lecture_allowed   BOOLEAN NOT NULL DEFAULT true,
    vr_allowed                   BOOLEAN NOT NULL DEFAULT true,

    -- Iowa disclosure (Section 4) — self-reported, staff-checked
    iowa_500_plume_completion    BOOLEAN NOT NULL DEFAULT false,

    preferred_contact_method     VARCHAR(10) DEFAULT 'email',   -- 'email' | 'phone'

    created_at                   TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at                   TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX idx_students_employer_client ON students (employer_client_id);
CREATE INDEX idx_students_student_number ON students (student_number);
