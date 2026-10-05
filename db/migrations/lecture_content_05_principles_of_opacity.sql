-- ============================================================================
-- Lecture content seed: Principles of Opacity section, page 1 (Introduction)
-- Reference: Michael, 2026-10-05 -- real content capture from the live
-- principles-introduction.php. No toggles or links on this page.
--
-- Real, honest link decisions:
--   - Image /images/lecture/smoke-plumes.jpg is already in the repo (live
--     source: lecture_certification/images/smoke-plumes.jpg).
--
-- Live-source bugs fixed: none of substance (the list's inline
-- display:grid style dropped; our layout styles lists).
-- ============================================================================

INSERT INTO lecture_pages (lecture_section_id, title, slug, content, order_index)
VALUES (
    (SELECT id FROM lecture_sections WHERE slug = 'principles-of-opacity'),
    'Introduction',
    'principles-introduction',
    '<div style="display:flex; gap:2rem; flex-wrap:wrap;">
    <div style="width:380px; flex-shrink:0;">
        <img src="/images/lecture/smoke-plumes.jpg" alt="Principles of opacity readings" style="width:100%; border-radius:0.375rem;">
    </div>
    <div style="flex:1; min-width:320px;">
        <h2>Introduction to Opacity Principles</h2>
        <p>This course section focuses on the scientific principles behind opacity and the measurement of opacity. The following subjects will be covered:</p>
        <ul>
            <li>Principles of opacity defined.</li>
            <li>Factors that affect opacity observations.</li>
        </ul>
        <p>Recall the definition of opacity: For the visible emission industry, opacity is defined as the percentage of the background obscured by visible emissions. An emission that lets more light through (one can see more, rather than less, of the background) has a lower opacity.</p>
    </div>
</div>',
    1
);
