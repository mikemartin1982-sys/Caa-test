-- ============================================================================
-- Migration 040: Enrollment.brevo_notified_at / brevo_notification_error
-- Reference: Michael, 2026-09-03, Client Auto-Notify feature.
--
-- Confirmed with Michael: the invoice-checker page needs to track and
-- show notification state for the Private/Semi-Private path too, same
-- as it already does for Public via Payment. That path is
-- deliberately NOT tied to Payment at all (fires at enrollment, not
-- billing), so Enrollment is the real, correct anchor for this
-- tracking -- mirrors Payment.brevo_notified_at/brevo_notification_error
-- (migration 039) exactly, just on a different table.
-- ============================================================================

ALTER TABLE enrollments ADD COLUMN brevo_notified_at TIMESTAMPTZ;
ALTER TABLE enrollments ADD COLUMN brevo_notification_error VARCHAR(500);
