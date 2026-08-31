-- ============================================================================
-- Migration 022: Client Account Creation
-- Reference: Michael, 2026-08-19 -- general prospective-client account
-- creation, independent of which testing path (VR or traditional
-- staff-scheduled smoke school) they end up on. Both Individual and
-- Organization accounts get real login credentials at creation, and
-- pick their testing-path preference right at signup.
--
-- password_hash follows the EXACT same pattern as staff_users.password_hash
-- (migration referenced in StaffUser) -- bcrypt, verified live via Spring
-- Security, never stored or checked a second time on the Laravel side.
--
-- client_type distinguishes Individual (a single person is both the
-- account holder and the test-taker) from Organization (the account
-- holder manages employees separately, added later via the org portal).
-- Deliberately NOT inferring this from whether `company` is blank --
-- an explicit column is much harder to get wrong later.
--
-- vr_client (already existing, migration for Section 4b) is reused as
-- the testing-path preference captured at signup, rather than adding a
-- redundant new column -- it already means exactly "eligible/intends to
-- test via the Public VR Session."
-- ============================================================================

ALTER TABLE clients ADD COLUMN password_hash VARCHAR(255);

CREATE TYPE client_type AS ENUM ('INDIVIDUAL', 'ORGANIZATION');
ALTER TABLE clients ADD COLUMN client_type client_type;

-- email isn't unique-constrained today (legacy inquiry-converted clients
-- can share or lack an email) -- a full unique constraint would risk
-- breaking existing data. Scoped to password_hash IS NOT NULL instead:
-- only self-serve accounts (which always have a password) need a
-- guaranteed-unique email for login lookup to work safely.
CREATE UNIQUE INDEX clients_email_unique_when_has_password
    ON clients (email) WHERE password_hash IS NOT NULL;
