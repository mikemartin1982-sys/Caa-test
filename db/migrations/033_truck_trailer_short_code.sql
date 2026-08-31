-- ============================================================================
-- Migration 033: Truck/Trailer short codes (calendar display)
-- Reference: Michael, 2026-08-29 -- staff calendar, Phase 1.
--
-- Confirmed with Michael: Truck/Trailer.identifier stays the long form
-- used everywhere else (e.g. "Metris", "Abby") -- the calendar
-- specifically needs a short code instead (e.g. "V21" for the vehicle
-- pulling "Abby"), matching the real, existing DIBs convention exactly
-- (confirmed: "keep that consistent"). Same pattern StaffUser.initials
-- already uses for the same reason -- a short, explicit field, not
-- derived from the long name.
-- ============================================================================

ALTER TABLE trucks ADD COLUMN short_code VARCHAR(10);
ALTER TABLE trailers ADD COLUMN short_code VARCHAR(10);
