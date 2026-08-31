ALTER TABLE sessions DROP CONSTRAINT IF EXISTS sessions_school_type_check;
ALTER TABLE sessions ADD CONSTRAINT sessions_school_type_check
    CHECK (school_type IN ('VR', 'PRIVATE', 'SEMI_PRIVATE', 'PUBLIC', 'VTCA'));