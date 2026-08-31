-- ============================================================================
-- Migration 014: SCHOOL INFO Section + FIELD-site Correction + Canceled Flag
-- Reference: real DIBs session-details source review, 2026-08-16.
--
-- CORRECTION: migration 012 (FIELD section) wrongly assumed the existing
-- generic address fields (address_street/city/state/zip, grid_lat/lng)
-- already covered "Field Address/City/State/Zip/Lat/Long" -- they
-- actually represent DIBs' SEPARATE "School Address" fields (confirmed
-- by their usage in certificates/emails/comparisons, all public-display
-- contexts matching DIBs' school_name semantics, not field_name).
-- Confirmed with Michael: School location and Field testing location
-- ARE genuinely different places sometimes -- adding the missing
-- Field-site fields here rather than continuing to conflate them.
--
-- School Address/City/State/Zip/Lat/Long themselves need NO new columns
-- -- they're the existing generic address_street/city/state/zip/
-- grid_lat/grid_lng fields, just now correctly documented as such.
--
-- canceled: a proper boolean, NOT DIBs' text-hack convention (typing
-- "canceled" into the school name) -- confirmed with Michael.
-- ============================================================================

ALTER TABLE sessions ADD COLUMN field_facility VARCHAR(64);
ALTER TABLE sessions ADD COLUMN field_address VARCHAR(63);
ALTER TABLE sessions ADD COLUMN field_city VARCHAR(31);
ALTER TABLE sessions ADD COLUMN field_state VARCHAR(2);
ALTER TABLE sessions ADD COLUMN field_zip VARCHAR(15);
ALTER TABLE sessions ADD COLUMN field_lat NUMERIC(10, 6);
ALTER TABLE sessions ADD COLUMN field_lng NUMERIC(10, 6);

ALTER TABLE sessions ADD COLUMN use_client_info BOOLEAN NOT NULL DEFAULT false;
ALTER TABLE sessions ADD COLUMN school_url VARCHAR(100);
ALTER TABLE sessions ADD COLUMN school_geo_area VARCHAR(100);

ALTER TABLE sessions ADD COLUMN canceled BOOLEAN NOT NULL DEFAULT false;
