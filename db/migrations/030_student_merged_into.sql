-- ============================================================================
-- Migration 030: Student merged-into tracking
-- Reference: Michael, 2026-08-24 -- "Combine Employee" backend.
--
-- When two Student records turn out to be the same real person, one
-- is kept (the "survivor") and one is merged away (the "duplicate").
-- Matching this project's established preference for soft, audit-
-- friendly changes over destructive ones (the active flag, the block
-- on deleting a certified enrollment): the duplicate is NOT deleted.
-- It's marked inactive and this column records which surviving
-- student it was merged into, so anyone who looks up the old record
-- later can see exactly what happened and where the real history now
-- lives, rather than hitting a dead end or a silently vanished record.
-- ============================================================================

ALTER TABLE students ADD COLUMN merged_into_student_id BIGINT REFERENCES students(id);
