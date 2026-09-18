-- ============================================================================
-- Lecture content fix: Introduction section, page 3 (About Visible
-- Emissions Observations)
-- Reference: Michael, 2026-09-07 -- re-linking glossary references now
-- that the real Glossary resource page exists (migration 045).
-- ============================================================================

UPDATE lecture_pages
SET content = REPLACE(
    REPLACE(
        REPLACE(
            content,
            'The term visible emissions observations (VEO) includes',
            'The term <a href="/lecture/resources/glossary#veo">visible emissions observations (VEO)</a> includes'
        ),
        'to quantify opacity of the emissions',
        'to quantify <a href="/lecture/resources/glossary#opacity">opacity</a> of the emissions'
    ),
    'occurs at a hands-on, in-person event called a smoke school',
    'occurs at a hands-on, in-person event called a <a href="/lecture/resources/glossary#ss">smoke school</a>'
)
WHERE lecture_section_id = (SELECT id FROM lecture_sections WHERE slug = 'introduction')
  AND slug = 'about-veo';
