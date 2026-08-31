ALTER TABLE sessions DROP CONSTRAINT IF EXISTS chk_quoted_headcount_only_private;
ALTER TABLE sessions ADD CONSTRAINT chk_quoted_headcount_only_private
    CHECK (quoted_headcount IS NULL OR school_type IN ('private', 'semi_private', 'vtca'));