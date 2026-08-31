-- ============================================================================
-- Migration 029: Student active/inactive flag
-- Reference: Michael, 2026-08-23 -- Client Page's employee roster
-- (Layer 1 of the roster rebuild). DIBs treats every new employee as
-- Active by default, with staff able to mark someone Inactive later
-- (they left the company, etc.) -- editable inline on the roster
-- table, batch-saved. Defaulting new rows to true matches that.
-- ============================================================================

ALTER TABLE students ADD COLUMN active BOOLEAN NOT NULL DEFAULT true;
