-- ============================================================================
-- Lecture content fix: Introduction section, page 4 (Definition ::
-- Visible Emissions)
-- Reference: Michael, 2026-09-07 -- re-linking glossary references now
-- that the real Glossary resource page exists (migration 045). Eight
-- real references found (not seven as roughly estimated earlier).
--
-- Two real bugs fixed while re-linking, matching the same principle
-- already applied elsewhere in this course:
--   - "promulgated" in the live source links to #ss (Smoke School)
--     instead of #prom (Promulgate) -- same apparent copy-paste mistake
--     as page 2. Linked to the correct #prom anchor.
--   - "fugitive emissions" in the live source links to #fug, but this
--     course''s own real Glossary page anchor is #fugitive (matching its
--     own <h4>Fugitive Emissions</h4> heading). Linked to the real,
--     working anchor rather than the broken one.
-- ============================================================================

UPDATE lecture_pages
SET content = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
    content,
    'Visible emissions are emissions that can be seen',
    '<a href="/lecture/resources/glossary#visible-emissions">Visible emissions</a> are emissions that can be seen'
),
    'are regulated requirements are promulgated at the federal level',
    'are regulated requirements are <a href="/lecture/resources/glossary#prom">promulgated</a> at the federal level'
),
    'two types of visible emissions: point source emissions and fugitive emissions',
    'two types of visible emissions: <a href="/lecture/resources/glossary#point">point source emissions</a> and <a href="/lecture/resources/glossary#fugitive">fugitive emissions</a>'
),
    'EPA Method 9 evaluates and quantifies point source emissions',
    'EPA <a href="/lecture/resources/glossary#method9">Method 9</a> evaluates and quantifies point source emissions'
),
    'successful qualification at a smoke school',
    'successful qualification at a <a href="/lecture/resources/glossary#ss">smoke school</a>'
),
    'EPA Method 22, known as the fugitive emissions method',
    'EPA <a href="/lecture/resources/glossary#method22">Method 22</a>, known as the fugitive emissions method'
),
    'A Quick-Check observation is what Compliance Assurance',
    'A <a href="/lecture/resources/glossary#quick">Quick-Check</a> observation is what Compliance Assurance'
)
WHERE lecture_section_id = (SELECT id FROM lecture_sections WHERE slug = 'introduction')
  AND slug = 'veo-definition-veo';
