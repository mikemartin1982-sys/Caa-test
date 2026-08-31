-- ============================================================================
-- Migration 006: VR Coaching Outreach Log
-- Reference: CAA-Website-Rebuild-Architecture.md, Section 4b
--
-- Escalation rules (e.g. "after N attempts, call") live in staff SOPs, not
-- hardcoded system logic (Section 4b) -- this table just records what
-- outreach actually happened, so the SOP can evolve without a code change.
-- ============================================================================

CREATE TYPE outreach_method AS ENUM ('EMAIL', 'PHONE');

CREATE TABLE outreach_logs (
    id              BIGSERIAL PRIMARY KEY,
    student_id       BIGINT NOT NULL REFERENCES students(id),
    outreach_date      DATE NOT NULL DEFAULT CURRENT_DATE,
    method               outreach_method NOT NULL,
    staff_member_id       BIGINT NOT NULL REFERENCES staff_users(id),
    notes                    TEXT,
    created_at                 TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX idx_outreach_logs_student ON outreach_logs (student_id);
