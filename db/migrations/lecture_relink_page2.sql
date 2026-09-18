-- ============================================================================
-- Lecture content fix: Introduction section, page 2 (About Smoke Schools)
-- Reference: Michael, 2026-09-07 -- re-linking glossary references now
-- that the real Glossary resource page exists (migration 045).
--
-- Real, genuine bug fixed while re-linking: the live source''s own anchor
-- for "promulgated" points to #ss (Smoke School) instead of #prom
-- (Promulgate) -- an apparent copy-paste mistake in the original. Linked
-- to the correct #prom anchor here instead of carrying the mismatch
-- forward, matching the same principle already applied to other real
-- bugs found in this course (Luminous''s malformed HTML, Fugitive
-- Emissions'' unclosed list).
-- ============================================================================

UPDATE lecture_pages
SET content = REPLACE(
    REPLACE(
        content,
        'visible emissions observations (VEO) training',
        '<a href="/lecture/resources/glossary#veo">visible emissions observations (VEO)</a> training'
    ),
    'The promulgated EPA test method',
    'The <a href="/lecture/resources/glossary#prom">promulgated</a> EPA test method'
)
WHERE lecture_section_id = (SELECT id FROM lecture_sections WHERE slug = 'introduction')
  AND slug = 'about-smoke-schools';
