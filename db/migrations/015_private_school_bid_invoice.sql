-- ============================================================================
-- Migration 015: PRIVATE SCHOOL BID AND INVOICE Section
-- Reference: real DIBs session-details source review, 2026-08-16.
--
-- Per Michael: build the full bid section's fields/schema, but bid
-- generation (a real .docx, sending emails, QBO API calls) does NOT
-- need to function yet -- storage only.
--
-- "Last Website Invoice Sent" deliberately excluded -- DIBs' own
-- tooltip confirms it's dead: "we have not been using web invoices.
-- We use QBO invoices only."
--
-- bid_lost/bid_lost_reason already exist (migration 009).
--
-- Copy-forward reasoning (applied in SessionCopyForwardService, not
-- here): standing client billing requirements (PO-upfront, no-public,
-- cert-of-completion, no-addons, addons-change-order) and attendee-
-- count/discount/lunch estimates carry forward as reasonable starting
-- defaults for a recurring client relationship. Occurrence-specific
-- negotiation notes, the custom email message, PO-for-invoice, revision
-- number, and all "last generated/sent" timestamps do NOT -- a new
-- copied session represents a fresh bid cycle with its own history.
-- ============================================================================

ALTER TABLE sessions ADD COLUMN bid_extra_details_lecture TEXT;
ALTER TABLE sessions ADD COLUMN bid_extra_details_field TEXT;
ALTER TABLE sessions ADD COLUMN bid_caa_notes TEXT;

ALTER TABLE sessions ADD COLUMN bid_num_selfpaced_lecture_attendees INTEGER;
ALTER TABLE sessions ADD COLUMN bid_num_inperson_lecture_attendees INTEGER;
ALTER TABLE sessions ADD COLUMN bid_num_field_attendees INTEGER;
ALTER TABLE sessions ADD COLUMN bid_discount_field_for_public NUMERIC(8, 2);

ALTER TABLE sessions ADD COLUMN bid_need_po_upfront BOOLEAN NOT NULL DEFAULT false;
ALTER TABLE sessions ADD COLUMN bid_no_public_allowed BOOLEAN NOT NULL DEFAULT false;
ALTER TABLE sessions ADD COLUMN bid_requires_cert_of_completion BOOLEAN NOT NULL DEFAULT false;
ALTER TABLE sessions ADD COLUMN bid_no_addons BOOLEAN NOT NULL DEFAULT false;
ALTER TABLE sessions ADD COLUMN bid_addons_require_change_order BOOLEAN NOT NULL DEFAULT false;

-- Raw DIBs radio values kept verbatim (avail/caa/none/no/client) despite
-- the apparent none/no redundancy, for fidelity to the real source.
CREATE TYPE bid_lunch_option AS ENUM ('AVAIL', 'CAA', 'NONE', 'NO', 'CLIENT');
ALTER TABLE sessions ADD COLUMN bid_lunch_option bid_lunch_option;
ALTER TABLE sessions ADD COLUMN bid_lunch_cost NUMERIC(8, 2);

ALTER TABLE sessions ADD COLUMN bid_revision_number INTEGER NOT NULL DEFAULT 0;
ALTER TABLE sessions ADD COLUMN bid_email_message TEXT;
ALTER TABLE sessions ADD COLUMN po_for_invoice VARCHAR(100);

ALTER TABLE sessions ADD COLUMN last_bid_generated_at TIMESTAMPTZ;
ALTER TABLE sessions ADD COLUMN last_bid_sent_at TIMESTAMPTZ;
ALTER TABLE sessions ADD COLUMN last_qbo_invoice_generated_at TIMESTAMPTZ;
ALTER TABLE sessions ADD COLUMN last_qbo_invoice_sent_number VARCHAR(20);
