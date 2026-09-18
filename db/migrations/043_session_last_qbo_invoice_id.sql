-- ============================================================================
-- Migration 043: sessions.last_qbo_invoice_id
-- Reference: Michael, 2026-09-04, session close-out billing redesign.
--
-- Confirmed with Michael: "Generate Invoice" and "Send Invoice" are two
-- genuinely separate, real steps -- QBO's own real, dedicated "send"
-- endpoint (/invoice/{invoiceId}/send, verified directly against
-- QBO's own API docs) requires the real, internal QBO Invoice Id, not
-- the human-facing DocNumber Session already stored
-- (last_qbo_invoice_sent_number). This is the same distinction that
-- caused real confusion a few days ago (Id 163 vs. DocNumber 1048) --
-- the Id was always available in the real, live QBO response at
-- creation time, just never actually stored on this entity until now.
-- ============================================================================

ALTER TABLE sessions ADD COLUMN last_qbo_invoice_id VARCHAR(20);
