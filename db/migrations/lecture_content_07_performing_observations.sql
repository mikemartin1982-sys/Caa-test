-- ============================================================================
-- Lecture content seed: Performing Observations section, page 1
-- (Introduction)
-- Reference: Michael, 2026-10-05 -- real content capture from the live
-- veoreading-introduction.php. No toggles or links on this page.
--
-- Real, honest link decisions:
--   - Image /images/lecture/veo-checklist.jpg is already in the repo.
--
-- Live-source bugs fixed: none of substance (the list's inline
-- display:grid style dropped; our layout styles lists).
-- ============================================================================

INSERT INTO lecture_pages (lecture_section_id, title, slug, content, order_index)
VALUES (
    (SELECT id FROM lecture_sections WHERE slug = 'performing-observations'),
    'Introduction',
    'veoreading-introduction',
    '<div style="display:flex; gap:2rem; flex-wrap:wrap;">
    <div style="width:380px; flex-shrink:0;">
        <img src="/images/lecture/veo-checklist.jpg" alt="VEO Checklist" style="width:100%; border-radius:0.375rem;">
    </div>
    <div style="flex:1; min-width:320px;">
        <h2>Performing a Method 9 Visible Emissions Observation</h2>
        <p>This section will guide you through the five steps in opacity reading. Each step will be detailed on subsequent pages.</p>
        <ul>
            <li>Review of regulations and facility records</li>
            <li>Gather field equipment</li>
            <li>Complete field tasks</li>
            <li>Conduct observations</li>
            <li>Calculate opacity</li>
        </ul>
    </div>
</div>',
    1
);
