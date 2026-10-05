-- ============================================================================
-- Lecture content seed: Principles of Opacity section, page 2 (Particulate
-- Matter)
-- Reference: Michael, 2026-10-05 -- real content capture from the live
-- principles-particulate-matter.php. "Particle Size and its Significance"
-- is a real, collapsible toggle.
--
-- Real, honest link decisions:
--   - Image /images/lecture/epa-particulate-matter-image.jpg is already in
--     the repo. It links to the full-size EPA PM2.5 scale graphic; the live
--     /sites/production/ URL redirects to /sites/default/, so the final URL
--     is used (checked 200 on 2026-10-05).
--   - EPA "Particulate Matter (PM) Basics" page checked 200 -- kept.
--
-- Live-source bugs fixed: double spaces; stray <hr> after the toggle
-- dropped; live span ids used as search anchors (pollution, emissions,
-- particledensity) dropped.
--
-- Content left as-is but flagged to Michael for CAA review: "PM10
-- particles are in a range from 0.5 to 10 microns". EPA defines PM10 as
-- inhalable particles 10 microns and smaller (no 0.5 lower bound; the
-- 2.5-10 micron band is "coarse" PM), and EPA calls PM10/PM2.5
-- "inhalable" rather than "respirable".
-- ============================================================================

INSERT INTO lecture_pages (lecture_section_id, title, slug, content, order_index)
VALUES (
    (SELECT id FROM lecture_sections WHERE slug = 'principles-of-opacity'),
    'Particulate Matter',
    'principles-particulate-matter',
    '<div style="display:flex; gap:2rem; flex-wrap:wrap;">
    <div style="width:380px; flex-shrink:0;">
        <a href="https://www.epa.gov/sites/default/files/2016-09/pm2.5_scale_graphic-color_2.jpg" target="_blank" rel="noopener" title="EPA particulate matter graphic">
            <img src="/images/lecture/epa-particulate-matter-image.jpg" alt="Particulate matter" style="width:100%; border-radius:0.375rem;">
        </a>
        <p style="font-size:0.85rem; color:#666666; margin-top:0.4rem;">Click to enlarge image (directs you to the EPA website)</p>
    </div>
    <div style="flex:1; min-width:320px;">
        <h2>Particulate Matter</h2>
        <p>Particulate matter (PM), known as particle pollution, is a mixture of solid particles and liquid droplets in the air. Examples of particulate matter include dust, dirt, soot, and fumes.</p>
        <p>A primary goal of air pollution standards is to minimize the amount of particulates in the air. When more particulates are in an emission, the emission has more density and a higher opacity reading.</p>

        <h3>Particle Size and Emission Density</h3>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''pm-size'', ''pm-size-icon'')" class="lect-toggle-btn">
                <span>Particle Size and its Significance</span>
                <span id="pm-size-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="pm-size" class="lect-toggle-body" style="display:none;">
                <p>Particles in emissions range from .1 micron (also known as micrometer) to 200 microns. Microns are also called micrometers, which are one-millionth of a meter and are designated with the &micro; symbol. For comparison, the width of a human hair is about 50-70 &micro;s in diameter. The EPA air pollution controls regulate particles up to 10 microns.</p>
                <p>Particles are notated using a subscript that reflects the particle&rsquo;s maximum size:</p>
                <ul>
                    <li><strong>PM<sub>10</sub> particles</strong> are in a range from 0.5 to 10 microns and are respirable.</li>
                    <li><strong>PM<sub>2.5</sub> particles</strong> are very small - 2.5 microns and smaller, are also respirable.</li>
                </ul>
            </div>
        </div>

        <p>For more information on particulate matter, visit the <a href="https://www.epa.gov/pm-pollution/particulate-matter-pm-basics" target="_blank" rel="noopener" title="EPA web page on particulate matter">EPA particulate matter web page &raquo;</a></p>
        <p>The emission density is the number of particles per volume in an emission. The greater the emission density, the more the light is absorbed or scattered, creating a higher emission opacity.</p>
    </div>
</div>',
    2
);
