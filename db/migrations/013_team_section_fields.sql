-- ============================================================================
-- Migration 013: TEAM Section (Proctors, Truck, Trailer-per-session)
-- Reference: real DIBs session-details source review, 2026-08-16.
--
-- Session Confirmed/Confirmed Comment and Field Manager/Operator already
-- existed (migration 009). Team Comments stays on the existing
-- SessionComment relation (TEAM_COMMENT type) -- confirmed with Michael:
-- entries save once, no edits, matching what's already built.
--
-- staged_location is intentionally a plain string for now (Michael:
-- "can be hashed out later... but the field should be present") -- not
-- the structured parking/home/service/airport model DIBs uses.
-- ============================================================================

ALTER TABLE staff_users ADD COLUMN initials VARCHAR(5);

CREATE TABLE trucks (
    id                  BIGSERIAL PRIMARY KEY,
    identifier          VARCHAR(255) NOT NULL UNIQUE,
    paired_trailer_id   BIGINT REFERENCES trailers(id),
    created_at          TIMESTAMPTZ NOT NULL DEFAULT now()
);

ALTER TABLE sessions ADD COLUMN proctor1_id BIGINT REFERENCES staff_users(id);
ALTER TABLE sessions ADD COLUMN proctor2_id BIGINT REFERENCES staff_users(id);
ALTER TABLE sessions ADD COLUMN proctor3_id BIGINT REFERENCES staff_users(id);
ALTER TABLE sessions ADD COLUMN truck_id BIGINT REFERENCES trucks(id);
ALTER TABLE sessions ADD COLUMN trailer_id BIGINT REFERENCES trailers(id);
ALTER TABLE sessions ADD COLUMN staged_location VARCHAR(255);
