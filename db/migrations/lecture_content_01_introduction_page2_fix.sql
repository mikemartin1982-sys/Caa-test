-- ============================================================================
-- Lecture content fix: Introduction section, page 2 (About Smoke Schools)
-- Reference: Michael, 2026-09-07 -- the image caption ("Having reliable,
-- accurate...") was rendered as a real <h4>, which browsers default to a
-- larger, bolder size than body text -- Michael wants this to match the
-- rest of the lecture's standard text size. Changed to a plain <p>,
-- keeping the intentional blue color and bold weight, which weren't just
-- side effects of the h4 tag. UPDATE, not a new INSERT -- the row already
-- exists.
-- ============================================================================

UPDATE lecture_pages
SET content = REPLACE(
    content,
    '<h4 style="color:#005da0; line-height:1.3;">Having reliable, accurate visible emissions observations protects the environment, identifies operating issues, avoids costly fines, and protects your organization through compliance with air quality requirements.</h4>',
    '<p style="color:#005da0; font-weight:700; line-height:1.5;">Having reliable, accurate visible emissions observations protects the environment, identifies operating issues, avoids costly fines, and protects your organization through compliance with air quality requirements.</p>'
)
WHERE lecture_section_id = (SELECT id FROM lecture_sections WHERE slug = 'introduction')
  AND slug = 'about-smoke-schools';
