-- ============================================================================
-- Migration 003: Enrollment, Payment, VR Token Block entities
-- Reference: CAA-Website-Rebuild-Architecture.md, Section 3, 3a, 4f, 4g, 7
-- ============================================================================

CREATE TYPE payment_status AS ENUM ('PENDING', 'INVOICED', 'PAID', 'OVERDUE', 'VOIDED');
CREATE TYPE roster_status AS ENUM ('ARR', 'CERTIFIED', 'DNC', 'DNA');

-- ---------------------------------------------------------------------------
-- enrollments
-- Section 3 / 4f / 4g: payment_status lives on Session Details (also
-- surfaced on the Roster for Public/VR, Section 4f); roster_status drives
-- close-out (Section 4g). 'arr' and 'certified' auto-populate; 'dnc'/'dna'
-- are staff-set. certifying_run_id / practice_run_id / staggered_block_id
-- FKs added in migration 004/002-followup once those tables exist.
-- ---------------------------------------------------------------------------
CREATE TABLE enrollments (
    id                          BIGSERIAL PRIMARY KEY,
    student_id                  BIGINT NOT NULL REFERENCES students(id),
    client_id                   BIGINT NOT NULL REFERENCES clients(id),  -- billing/paying entity
    session_id                  BIGINT NOT NULL REFERENCES sessions(id),

    enrollment_date              TIMESTAMPTZ NOT NULL DEFAULT now(),

    payment_status                payment_status NOT NULL DEFAULT 'PENDING',
    roster_status                 roster_status,

    outside_attendee              BOOLEAN NOT NULL DEFAULT false,  -- semi-private only, excluded from CAA billing
    lecture_access_granted        BOOLEAN NOT NULL DEFAULT false,

    certifying_run_id             BIGINT,   -- FK added in migration 004
    practice_run_id                BIGINT,   -- FK added in migration 004
    staggered_arrival_block_id     BIGINT REFERENCES staggered_arrival_blocks(id),

    created_at                    TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at                    TIMESTAMPTZ NOT NULL DEFAULT now(),

    UNIQUE (student_id, session_id)
);

CREATE INDEX idx_enrollments_session ON enrollments (session_id);
CREATE INDEX idx_enrollments_client ON enrollments (client_id);
CREATE INDEX idx_enrollments_student ON enrollments (student_id);
CREATE INDEX idx_enrollments_roster_status ON enrollments (roster_status);

-- ---------------------------------------------------------------------------
-- payments
-- Section 7: QuickBooks invoice tracking. terms captures due-on-receipt vs
-- NET-x. Synced from QuickBooks via webhook or polling (Section 7,
-- PaymentStatusSyncStrategy — kept swappable pending Section 9 confirmation).
-- ---------------------------------------------------------------------------
CREATE TABLE payments (
    id                      BIGSERIAL PRIMARY KEY,
    enrollment_id            BIGINT NOT NULL REFERENCES enrollments(id),
    qb_invoice_id             VARCHAR(100) NOT NULL,
    amount                    NUMERIC(10,2) NOT NULL,
    terms                     VARCHAR(50),        -- 'due_on_receipt' | 'net_30' | 'net_60' | 'net_90' etc.
    status                    payment_status NOT NULL DEFAULT 'INVOICED',
    payment_date              DATE,
    created_at                TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at                TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX idx_payments_enrollment ON payments (enrollment_id);
CREATE INDEX idx_payments_qb_invoice_id ON payments (qb_invoice_id);

-- ---------------------------------------------------------------------------
-- vr_token_blocks
-- Section 3a: bulk VR purchases. Priced per-transaction, not cumulative
-- across a client's purchase history (deliberate — headcounts churn).
-- Overridden entirely if the client has a VR pricing override (Section 4b).
-- ---------------------------------------------------------------------------
CREATE TABLE vr_token_blocks (
    id                  BIGSERIAL PRIMARY KEY,
    client_id           BIGINT NOT NULL REFERENCES clients(id),
    tokens_purchased     INTEGER NOT NULL,
    tokens_remaining      INTEGER NOT NULL,
    purchase_date          DATE NOT NULL DEFAULT CURRENT_DATE,
    price_paid_per_token    NUMERIC(8,2) NOT NULL,  -- reflects tier ($275/$250/$225) or override rate
    qb_invoice_id            VARCHAR(100),
    created_at                TIMESTAMPTZ NOT NULL DEFAULT now(),

    CONSTRAINT chk_tokens_remaining_valid CHECK (tokens_remaining >= 0 AND tokens_remaining <= tokens_purchased)
);

CREATE INDEX idx_vr_token_blocks_client ON vr_token_blocks (client_id);
