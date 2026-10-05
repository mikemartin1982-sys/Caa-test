-- ============================================================================
-- Lecture content seed: Basics of Observations section, page 3 (Emission
-- Characteristics)
-- Reference: Michael, 2026-10-05 -- real content capture from the live
-- basics-emission-characteristics.php. No toggles or links on this page.
--
-- Real, honest link decisions:
--   - Image /images/lecture/stack-cloudy-day.jpg is already in the repo.
--
-- Live-source bugs fixed: none of substance (the list's inline
-- display:grid style dropped; our layout styles lists).
-- ============================================================================

INSERT INTO lecture_pages (lecture_section_id, title, slug, content, order_index)
VALUES (
    (SELECT id FROM lecture_sections WHERE slug = 'basics-of-observations'),
    'Emission Characteristics',
    'basics-emission-characteristics',
    '<div style="display:flex; gap:2rem; flex-wrap:wrap;">
    <div style="width:380px; flex-shrink:0;">
        <img src="/images/lecture/stack-cloudy-day.jpg" alt="Smoke stack on cloudy day" style="width:100%; border-radius:0.375rem;">
    </div>
    <div style="flex:1; min-width:320px;">
        <h2>Emission Characteristics</h2>
        <p>Smoke plumes appear differently depending on the type of emission. The following attributes of a visible emission can affect observations:</p>
        <ul>
            <li>Particle density</li>
            <li>Particle refractive index</li>
            <li>Particle size distribution</li>
            <li>Particle color</li>
        </ul>
    </div>
</div>',
    3
);
