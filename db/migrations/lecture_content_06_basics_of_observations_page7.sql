-- ============================================================================
-- Lecture content seed: Basics of Observations section, page 7 (Observer
-- Position)
-- Reference: Michael, 2026-10-05 -- real content capture from the live
-- basics-observer-location.php. No toggles or links on this page.
--
-- Real, honest link decisions:
--   - Image /images/lecture/obs-distance-slant-angle.jpg is already in the
--     repo.
--
-- Live-source bugs fixed: the <em> around "Performing Observations"
-- wrapped the period and a line break -- now just the section name; double
-- / trailing spaces; live span ids used as search anchors (Distance,
-- perpendicular) dropped. (Content check: 3x stack height gives a look-up
-- angle of arctan(1/3) = about 18 degrees, consistent with the text.)
-- ============================================================================

INSERT INTO lecture_pages (lecture_section_id, title, slug, content, order_index)
VALUES (
    (SELECT id FROM lecture_sections WHERE slug = 'basics-of-observations'),
    'Observer Position',
    'basics-observer-location',
    '<div style="display:flex; gap:2rem; flex-wrap:wrap;">
    <div style="width:380px; flex-shrink:0;">
        <img src="/images/lecture/obs-distance-slant-angle.jpg" alt="Distance and angle during opacity readings" style="width:100%; border-radius:0.375rem;">
    </div>
    <div style="flex:1; min-width:320px;">
        <h2>Observer Position</h2>
        <p>The opacity reader should observe the following when performing observations:</p>
        <ul>
            <li>The observer should stand at a distance from the stack that is about three times the height of the stack.</li>
            <li>The observer&rsquo;s line of sight (LOS) shall be approximately perpendicular to the track of the plume. This places the observer in a position that causes them to look up from the horizon by about 18&deg;.</li>
        </ul>
        <p>There are several things to consider when determining the observer position; details are covered in the <em>Performing Observations</em> section.</p>
    </div>
</div>',
    7
);
