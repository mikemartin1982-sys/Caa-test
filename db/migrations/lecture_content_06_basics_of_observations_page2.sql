-- ============================================================================
-- Lecture content seed: Basics of Observations section, page 2 (Factors that
-- Affect Readings)
-- Reference: Michael, 2026-10-05 -- real content capture from the live
-- basics-factors.php. No toggles or links on this page.
--
-- Real, honest link decisions:
--   - Image /images/lecture/white-smoke-plume.jpg is already in the repo.
--
-- Live-source bugs fixed: none of substance (the list's inline
-- display:grid style dropped; our layout styles lists).
-- ============================================================================

INSERT INTO lecture_pages (lecture_section_id, title, slug, content, order_index)
VALUES (
    (SELECT id FROM lecture_sections WHERE slug = 'basics-of-observations'),
    'Factors that Affect Readings',
    'basics-factors',
    '<div style="display:flex; gap:2rem; flex-wrap:wrap;">
    <div style="width:380px; flex-shrink:0;">
        <img src="/images/lecture/white-smoke-plume.jpg" alt="Variables that affect opacity readings" style="width:100%; border-radius:0.375rem;">
    </div>
    <div style="flex:1; min-width:320px;">
        <h2>Variables that Affect Opacity Observations</h2>
        <p>Several variables can affect an opacity reading. Some are under the reader&rsquo;s control, and some are not. The variables include:</p>
        <ul>
            <li>Emission characteristics</li>
            <li>Plume background</li>
            <li>Path length</li>
            <li>Distance to and the relative elevation to the stack plume exit</li>
            <li>Sun angle</li>
            <li>Lighting conditions</li>
        </ul>
        <p>We will define the factors and explain their role in observations.</p>
    </div>
</div>',
    2
);
