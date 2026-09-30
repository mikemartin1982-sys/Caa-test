-- ============================================================================
-- 008d: convert chart_recorder_exports.recipients to TEXT, only if needed.
-- Michael, 2026-09-30 -- migration 004 already creates recipients as TEXT
-- (comma-separated), so on a fresh database the old unconditional
-- array_to_string() conversion failed. Guarded so it only converts a
-- database that still has the older array column, and is a no-op otherwise.
-- ============================================================================

DO $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_name = 'chart_recorder_exports'
          AND column_name = 'recipients'
          AND data_type = 'ARRAY'
    ) THEN
        ALTER TABLE chart_recorder_exports
            ALTER COLUMN recipients TYPE TEXT USING array_to_string(recipients, ',');
    END IF;
END $$;
