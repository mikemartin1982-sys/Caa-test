-- ============================================================================
-- Migration 041: Client.lecture_fee_exempt / Student.lecture_fee_exempt
-- Reference: Michael, 2026-09-03, lecture billing exemption.
--
-- Confirmed with Michael: two real, independent scopes needed --
-- client-level (management offering the lecture at no charge, e.g. a
-- government agency or specific client), and student-level (logistics
-- resolving a one-off technical issue for a specific person). Either
-- flag being true exempts that LECTURE_ONLY enrollment from the
-- $50 charge (see EnrollmentPricingService.computePrice()).
--
-- Michael, 2026-09-03 -- no reason-tracking field added -- confirmed
-- with Michael as not currently tracked in DIBs either, so not needed
-- now. Noted here as a real, possible future revisit if management
-- ever wants a recorded reason, not decided against permanently.
-- ============================================================================

ALTER TABLE clients ADD COLUMN lecture_fee_exempt BOOLEAN NOT NULL DEFAULT FALSE;
ALTER TABLE students ADD COLUMN lecture_fee_exempt BOOLEAN NOT NULL DEFAULT FALSE;
