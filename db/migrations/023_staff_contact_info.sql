-- ============================================================================
-- Migration 023: Staff Contact Info
-- Reference: Michael, 2026-08-19 -- the session confirmation email's
-- signature and reply-to (Confirm/Request-a-Change buttons) need to
-- reflect whichever staff member actually sent it, not a hardcoded
-- person. "Wouldn't want someone else's confirmations coming to me --
-- it breaks the chain." None of email/phone/mobile/job_title existed
-- on staff_users before this -- there was genuinely nothing to pull
-- from.
-- ============================================================================

ALTER TABLE staff_users ADD COLUMN email VARCHAR(255);
ALTER TABLE staff_users ADD COLUMN phone VARCHAR(50);
ALTER TABLE staff_users ADD COLUMN mobile_phone VARCHAR(50);
ALTER TABLE staff_users ADD COLUMN job_title VARCHAR(255);
