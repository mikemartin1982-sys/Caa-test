-- ============================================================================
-- Migration 008: Session Status Taxonomy + VTCA School Type
-- Reference: staff calendar review, 2026-08-15 -- replicating DIBs'
-- existing calendar legend/status categories, which the original
-- session_status enum (a scaffold placeholder) never matched.
--
-- New status values map directly to DIBs' calendar legend:
--   PROPOSED                 -- quote given, awaiting client decision
--                                (Private/Semi-Private/VTCA)
--   LOST_BID                 -- quote given, client went elsewhere
--   UNPUBLISHED               -- default/active, not yet published
--   PUBLISHED_NOT_CONFIRMED   -- published, Confirmed checkbox not yet checked
--   PUBLISHED_CONFIRMED       -- published AND confirmed
--   CANCELED                  -- was scheduled/confirmed, canceled before it happened
--   CLOSED_OUT                -- session happened, close-out complete
--
-- "Publ & Conf VR" (DIBs' legend) is NOT a distinct backend status -- it's
-- PUBLISHED_CONFIRMED shown in a different color specifically when
-- school_type = 'vr'. That's a display-layer distinction, not data.
--
-- VTCA (Virginia Transportation Construction Alliance) IS its own school
-- type here, confirmed against a real DIBs "Edit Session" screenshot
-- (2026-08-15) showing "Session Type: VTCA" as a peer dropdown value
-- alongside Public/Private/Semi-Private/VR -- an earlier boolean-flag-
-- on-Private design was wrong and never got implemented, corrected here
-- before anything shipped. Structurally VTCA behaves like Private
-- (single locked host client, no outside attendance) -- the only real
-- difference is a pre-agreed contract rate instead of the usual
-- staff-judgment quote, which reuses the existing quoted_price/
-- quoted_headcount fields rather than needing separate storage.
-- ============================================================================

-- Postgres doesn't support cleanly dropping enum values, so we swap the
-- type wholesale: new type -> new column -> backfill -> drop old -> rename.
CREATE TYPE session_status_v2 AS ENUM (
    'PROPOSED',
    'LOST_BID',
    'UNPUBLISHED',
    'PUBLISHED_NOT_CONFIRMED',
    'PUBLISHED_CONFIRMED',
    'CANCELED',
    'CLOSED_OUT'
);

ALTER TABLE sessions ADD COLUMN status_v2 session_status_v2 NOT NULL DEFAULT 'UNPUBLISHED';

-- Best-effort backfill from the old placeholder values to the new taxonomy.
-- Old enum values are lowercase (see migration 002's CREATE TYPE) --
-- matching that exactly, not the Java enum's uppercase constant names.
UPDATE sessions SET status_v2 = CASE
    WHEN status = 'cancelled' THEN 'CANCELED'::session_status_v2
    WHEN status = 'completed' THEN 'CLOSED_OUT'::session_status_v2
    WHEN published = true THEN 'PUBLISHED_NOT_CONFIRMED'::session_status_v2
    ELSE 'UNPUBLISHED'::session_status_v2
END;

ALTER TABLE sessions DROP COLUMN status;
ALTER TABLE sessions RENAME COLUMN status_v2 TO status;
DROP TYPE session_status;
ALTER TYPE session_status_v2 RENAME TO session_status;

-- VTCA as a 5th school_type value -- simple ADD VALUE suffices here since,
-- unlike session_status above, we're only adding a value, not removing
-- any of the existing four.
ALTER TYPE school_type ADD VALUE 'VTCA';

-- quoted_price/quoted_headcount's existing CHECK constraint (migration 002)
-- only allowed private/semi_private -- VTCA needs the same fields for its
-- contract-based pricing, so it's added to the allowed list here.
-- IF EXISTS guards against drift between what's in migration 002 on disk
-- now vs. what actually got applied when it first ran (harmless either way).
ALTER TABLE sessions DROP CONSTRAINT IF EXISTS chk_quoted_headcount_only_private;
ALTER TABLE sessions ADD CONSTRAINT chk_quoted_headcount_only_private
    CHECK (quoted_headcount IS NULL OR school_type IN ('PRIVATE', 'SEMI_PRIVATE', 'VTCA'));

