-- ============================================================================
-- Migration 009: Session Lifecycle Flags Correction
-- Reference: real DIBs session-details source review, 2026-08-15.
--
-- The session_status enum (migration 008) wrongly consolidated what
-- DIBs actually tracks as INDEPENDENT boolean flags into one
-- mutually-exclusive status. Confirmed directly by Michael: Confirmed
-- and Closed Out are two separate, sequential events (confirmed early
-- when details are verified; closed out later by Chasity Miranda once
-- billing wraps up) -- a session can be published AND confirmed AND
-- closed-out all at once. Lost Bid is a third independent flag from
-- the Private School Bid section.
--
-- This drops the flawed enum/column entirely and replaces it with the
-- correct model: published (already existed) + confirmed + closedOut +
-- bidLost as independent booleans.
-- ============================================================================

ALTER TABLE sessions DROP COLUMN status;
DROP TYPE session_status;

ALTER TABLE sessions ADD COLUMN confirmed BOOLEAN NOT NULL DEFAULT false;
ALTER TABLE sessions ADD COLUMN confirmed_comment TEXT;
ALTER TABLE sessions ADD COLUMN closed_out BOOLEAN NOT NULL DEFAULT false;
ALTER TABLE sessions ADD COLUMN bid_lost BOOLEAN NOT NULL DEFAULT false;
ALTER TABLE sessions ADD COLUMN bid_lost_reason TEXT;
