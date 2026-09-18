-- ============================================================================
-- Migration 039: Payment.brevo_notified_at / brevo_notification_error
-- Reference: Michael, 2026-09-01, Client Auto-Notify feature.
--
-- Confirmed with Michael: a new invoice-checker page needs to show
-- whether a Brevo notification actually went out for a given Payment,
-- not just its own PENDING/INVOICED/PAID status -- nothing previously
-- recorded this at all, only logged it, which the readout can't read
-- back after the fact.
-- ============================================================================

ALTER TABLE payments ADD COLUMN brevo_notified_at TIMESTAMPTZ;
ALTER TABLE payments ADD COLUMN brevo_notification_error VARCHAR(500);
