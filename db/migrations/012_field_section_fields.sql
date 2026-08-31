-- ============================================================================
-- Migration 012: FIELD Section Remaining Fields
-- Reference: real DIBs session-details source review, 2026-08-16.
--
-- Most of DIBs' FIELD section (address, lat/long, Start/End, Start2/End2)
-- already exists in this schema: address fields on sessions directly,
-- and Start/End + Start2/End2 map to session_days' existing dayNumber +
-- startTime/endTime (a cleaner relational design than DIBs' flat
-- columns -- day 1 and day 2 are just two session_days rows).
--
-- "Duplicate to 3rd Day" is an ACTION in DIBs (creates a 3rd day-row on
-- click), not a stored field -- no column needed for it here.
--
-- Only 3 genuinely new fields: timezone abbreviation, on-site contact
-- name/phone.
-- ============================================================================

ALTER TABLE sessions ADD COLUMN field_timezone VARCHAR(3);
ALTER TABLE sessions ADD COLUMN field_contact VARCHAR(100);
ALTER TABLE sessions ADD COLUMN field_contact_phone VARCHAR(30);
