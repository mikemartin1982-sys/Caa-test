-- ============================================================================
-- Migration 027: Client Billing Contact + Purchase Order Tracking
-- Reference: Michael, 2026-08-22 -- Client Page rebuild.
--
-- billing_contact_name/billing_email/billing_phone: a real, distinct
-- contact for where an invoice actually needs to land to get paid
-- (e.g. an "ap@company.com" AP department inbox), separate from the
-- primary contact (first_name/last_name/email/phone) who manages the
-- training relationship day to day. Confirmed with Michael: only
-- meaningful for Organization clients -- an Individual pays directly
-- at certification time, no separate billing contact needed. Not
-- enforced at the DB level (nullable for everyone); the UI shows this
-- block only for Organization.
--
-- client_purchase_orders: a genuinely new subsystem. Clients often run
-- one PO across a full year or multiple seasons, not per-session --
-- confirmed with Michael this needed real, full tracking (balance,
-- expiration, threshold alerts), not a stripped-down version.
--
-- client_purchase_order_sessions: DIBs stores a PO's associated
-- sessions as a comma-separated text field (PO_assoc_sessions). Built
-- here as a real join table instead -- same pattern already used
-- throughout this project (e.g. SessionNotifiedClient) of replacing
-- DIBs' denormalized text-list fields with proper linked records.
--
-- Explicitly deferred (Michael, 2026-08-22): the "Website Database vs
-- QBO Billing-info" dual-field/copy-button pattern DIBs uses to
-- reconcile drift against QuickBooks' own separate customer record.
-- There's no real QBO API connection yet, so a manual copy-button UI
-- would only create the appearance of syncing without actually
-- syncing anything -- worse than not having it. qboReferenceId
-- (already existing) remains the link for whenever real API access
-- exists to build this properly.
-- ============================================================================

ALTER TABLE clients ADD COLUMN billing_contact_name VARCHAR(255);
ALTER TABLE clients ADD COLUMN billing_email VARCHAR(255);
ALTER TABLE clients ADD COLUMN billing_phone VARCHAR(50);

CREATE TABLE client_purchase_orders (
    id BIGSERIAL PRIMARY KEY,
    client_id BIGINT NOT NULL REFERENCES clients(id),
    po_number VARCHAR(30),
    active BOOLEAN NOT NULL DEFAULT false,
    expiration_date DATE,
    short_description VARCHAR(50),
    contact_first_name VARCHAR(50),
    contact_last_name VARCHAR(50),
    contact_email VARCHAR(50),
    starting_amount NUMERIC(10, 2) NOT NULL DEFAULT 0,
    amount_used NUMERIC(10, 2) NOT NULL DEFAULT 0,
    threshold_amount NUMERIC(10, 2) NOT NULL DEFAULT 0,
    -- Set when a low-balance/expiration warning email has already been
    -- sent for this PO, so we don't send it repeatedly. Not yet wired
    -- to an actual email service -- see Session's own
    -- lastBidGeneratedAt/lastBidSentAt for the established pattern to
    -- follow when that's built.
    exp_email_sent DATE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE client_purchase_order_sessions (
    id BIGSERIAL PRIMARY KEY,
    purchase_order_id BIGINT NOT NULL REFERENCES client_purchase_orders(id),
    session_id BIGINT NOT NULL REFERENCES sessions(id),
    UNIQUE (purchase_order_id, session_id)
);
