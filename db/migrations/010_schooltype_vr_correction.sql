-- ============================================================================
-- Migration 010: SchoolType Correction (remove VR, add PROPOSED)
-- Reference: real DIBs session-details source review, 2026-08-15.
--
-- DIBs' actual Session Type dropdown is Public/Private/Semi-Private/
-- Proposed/VTCA -- five values, no VR at all. Confirmed with Michael:
-- VR is not its own type -- it's a delivery-method MODIFIER that can
-- apply to either Public (the common case) or Private (bulk-purchase
-- clients), never its own category. The vr_session boolean (renamed
-- from is_public_vr_session, whose narrower "Public" framing no longer
-- fits) now covers both cases.
--
-- Existing 'VR'-typed rows (none expected in real data yet, but handled
-- safely regardless) backfill to PUBLIC + vr_session=true, matching the
-- "generally VR is Public" default described.
-- ============================================================================

-- Rename the modifier flag first, then backfill it FROM THE OLD
-- school_type column BEFORE that column gets dropped below -- doing
-- this after the drop would lose the very data needed to backfill it.
ALTER TABLE sessions RENAME COLUMN is_public_vr_session TO vr_session;
UPDATE sessions SET vr_session = true WHERE school_type = 'VR';

-- Same swap pattern as migration 008/009 -- Postgres can't remove an enum
-- value directly, only add one, so we're recreating the type wholesale
-- since this changes both directions (remove VR, add PROPOSED).
CREATE TYPE school_type_v2 AS ENUM ('PUBLIC', 'PRIVATE', 'SEMI_PRIVATE', 'PROPOSED', 'VTCA');

ALTER TABLE sessions ADD COLUMN school_type_v2 school_type_v2;

UPDATE sessions SET school_type_v2 = CASE
    WHEN school_type = 'VR' THEN 'PUBLIC'::school_type_v2
    ELSE school_type::text::school_type_v2
END;

ALTER TABLE sessions ALTER COLUMN school_type_v2 SET NOT NULL;

-- Drop and recreate the CHECK constraint that referenced the old type
-- (migration 002/008) before we can drop the old column/type.
ALTER TABLE sessions DROP CONSTRAINT IF EXISTS chk_quoted_headcount_only_private;

ALTER TABLE sessions DROP COLUMN school_type;
ALTER TABLE sessions RENAME COLUMN school_type_v2 TO school_type;
DROP TYPE school_type;
ALTER TYPE school_type_v2 RENAME TO school_type;

ALTER TABLE sessions ADD CONSTRAINT chk_quoted_headcount_only_private
    CHECK (quoted_headcount IS NULL OR school_type IN ('PRIVATE', 'SEMI_PRIVATE', 'VTCA', 'PROPOSED'));
