-- ============================================================================
-- 051: Student numbers become plain numbers (no "S" prefix)
-- Reference: Michael, 2026-09-28 -- decided: student numbers are numeric
-- only, matching DIBs (the eventual DIBs import carries plain numbers such
-- as 14593, no "S"). The engine now generates the number as the row's own
-- id (no prefix), and sign-in / cert lookup accept "S123", "s123" or "123"
-- for the same student, so anyone still typing an old S-number works.
--
-- Existing rows: every current number is either "S" + id (e.g. S21) or an
-- old millisecond-timestamp placeholder (e.g. S1786823367399). Both are
-- rewritten to the row's id -- the same value the engine now generates, and
-- guaranteed unique because ids are. Only "S<digits>" rows are touched; any
-- other value (e.g. imported DIBs numbers later) is left alone.
--
-- DIBs import note (for later): imported students keep their DIBs numbers
-- (~1..30,000), which can collide with id-based numbers of students created
-- here first. At import time, bump the students id sequence past the
-- highest DIBs number (setval) and renumber any pre-import students.
-- ============================================================================

UPDATE students
SET student_number = id::text
WHERE student_number ~ '^S[0-9]+$';
