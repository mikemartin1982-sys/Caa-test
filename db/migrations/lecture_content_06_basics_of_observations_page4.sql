-- ============================================================================
-- Lecture content seed: Basics of Observations section, page 4 (Smoke Plumes
-- and Backgrounds)
-- Reference: Michael, 2026-10-05 -- real content capture from the live
-- basics-smoke-plumes.php. No toggles or links on this page.
--
-- Real, honest link decisions:
--   - Image /images/lecture/types-of-smoke.jpg is already in the repo.
--
-- Live-source bugs fixed: a stray closing </a> after the image (no opening
-- link); "high contrast </strong>with" spacing; trailing spaces.
-- ============================================================================

INSERT INTO lecture_pages (lecture_section_id, title, slug, content, order_index)
VALUES (
    (SELECT id FROM lecture_sections WHERE slug = 'basics-of-observations'),
    'Smoke Plumes and Backgrounds',
    'basics-smoke-plumes',
    '<div style="display:flex; gap:2rem; flex-wrap:wrap;">
    <div style="width:380px; flex-shrink:0;">
        <img src="/images/lecture/types-of-smoke.jpg" alt="Types of smoke plumes" style="width:100%; border-radius:0.375rem;">
    </div>
    <div style="flex:1; min-width:320px;">
        <h2>Smoke Plumes and Backgrounds</h2>
        <p>Visible emissions occur in many color shades depending on the particles that make up the smoke. For opacity reading purposes, there are two categories of smoke: black and white. Black smoke absorbs light, and white smoke refracts (scatters) and reflects light.</p>
        <p>During a smoke school field certification, students read 25 white and 25 black plumes created by a smoke generator.</p>
        <p>Choosing a background for the reading with a <strong>high contrast</strong> with the plume in both luminosity (brightness) and color contrast is essential. In the images provided:</p>
        <ul>
            <li>The black smoke is read against a light blue sky (a high luminous contrast).</li>
            <li>The white smoke is being read against a green background. Green is the color most visible to the human eye, so it is an ideal background for white smoke.</li>
        </ul>
        <p>NOTE: Water vapor plumes are not considered emissions and should be excluded from observation data when performing a reading. Usually, they appear very white and billowy, transitioning to thin and wispy at the point of dissipation. Water vapor plumes will be discussed further in upcoming sections.</p>
    </div>
</div>',
    4
);
