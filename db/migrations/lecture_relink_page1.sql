-- ============================================================================
-- Lecture content fix: Introduction section, page 1 (Getting Started)
-- Reference: Michael, 2026-09-07 -- the note about bold red links
-- mentions "our glossary page" generically (not a specific term), so
-- linked to the real Glossary resource page itself, no anchor needed.
-- ============================================================================

UPDATE lecture_pages
SET content = REPLACE(
    content,
    'links that are bold red will take you to our glossary page',
    'links that are bold red will take you to our <a href="/lecture/resources/glossary">glossary page</a>'
)
WHERE lecture_section_id = (SELECT id FROM lecture_sections WHERE slug = 'introduction')
  AND slug = 'getting-started';
