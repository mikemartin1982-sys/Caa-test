-- ============================================================================
-- Migration 026: Remove Lunch Option
-- Reference: Michael, 2026-08-19 -- "we have not done a lunch option at
-- a client site since I have worked here so I genuinely feel it is not
-- needed." Removed from Session Details and the generated Bid PDF.
-- ============================================================================

ALTER TABLE sessions DROP COLUMN bid_lunch_option;
ALTER TABLE sessions DROP COLUMN bid_lunch_cost;
DROP TYPE bid_lunch_option;
