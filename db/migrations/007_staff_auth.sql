-- ============================================================================
-- Migration 007: Staff Authentication
-- Reference: CAA-Website-Rebuild-Architecture.md, Section 3 (StaffUser)
--
-- Adds a password hash column so the Compliance Engine API can actually
-- authenticate requests. Previously wide open -- flagged repeatedly as a
-- real risk once this started running on real infrastructure.
-- ============================================================================

ALTER TABLE staff_users
    ADD COLUMN password_hash VARCHAR(255);

-- Nullable initially so existing seeded rows (e.g. any StaffUser created
-- before this migration) don't break; the application should require a
-- password on new StaffUser creation going forward via the API.
