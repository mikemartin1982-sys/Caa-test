-- ============================================================================
-- Migration 038: Drop Session.late_fee_amount / late_fee_day_threshold
-- Reference: Michael, 2026-08-31, QBO Per-Student Invoicing.
--
-- Confirmed with Michael directly: the late fee shouldn't be present
-- in Session Details at all -- it's now assessed at enrollment time
-- via SessionDay (the session's real start date), using fixed
-- constants ($25, 7 days) in EnrollmentPricingService, not per-session
-- configurable values. These two columns are genuinely unused by any
-- real code as of this migration -- confirmed neither was ever wired
-- up to anything beyond the entity itself.
-- ============================================================================

ALTER TABLE sessions DROP COLUMN IF EXISTS late_fee_amount;
ALTER TABLE sessions DROP COLUMN IF EXISTS late_fee_day_threshold;
