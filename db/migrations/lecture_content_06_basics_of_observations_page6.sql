-- ============================================================================
-- Lecture content seed: Basics of Observations section, page 6 (Sun
-- Position)
-- Reference: Michael, 2026-10-05 -- real content capture from the live
-- basics-sun-position.php. "Forward Scattering of Light" is a real,
-- collapsible toggle (similar to, but not the same text as, the toggle on
-- Principles of Opacity :: Properties of Light).
--
-- Real, honest link decisions:
--   - Image /images/lecture/obs-line-of-sight-2.jpg is already in the repo.
--   - "high bias" was a bold-red glossary.php#bias link ->
--     /lecture/resources/glossary#bias.
--   - EPA Visible Emissions Field Manual PDF (checked 200 on 2026-10-05,
--     Properties of Light page) -- kept.
--
-- Live-source bugs fixed: the paragraph before the toggle was never closed
-- (the toggle sat inside it) -- closed; "spec(s) of dirt" -> "speck(s)"
-- (same fix as Properties of Light); trailing spaces; stray <hr> after the
-- toggle dropped; live span id used as a search anchor (position) dropped.
-- ============================================================================

INSERT INTO lecture_pages (lecture_section_id, title, slug, content, order_index)
VALUES (
    (SELECT id FROM lecture_sections WHERE slug = 'basics-of-observations'),
    'Sun Position',
    'basics-sun-position',
    '<div style="display:flex; gap:2rem; flex-wrap:wrap;">
    <div style="width:380px; flex-shrink:0;">
        <img src="/images/lecture/obs-line-of-sight-2.jpg" alt="Sun position during opacity readings" style="width:100%; border-radius:0.375rem;">
    </div>
    <div style="flex:1; min-width:320px;">
        <h2>Sun Position</h2>
        <p>Having the sun at your back is important when performing visible emission observations. Method 9 specifies that the sun must be oriented to the observer&rsquo;s back in a 140&deg; sector. The requirements include the vertical and horizontal sectors, resulting in a 140&deg; area shaped like a cone where the sun can reside.</p>
        <p>If the sun is behind the plume (i.e., in front of you), the plume can become more luminous than the background, creating a <a href="/lecture/resources/glossary#bias">high bias</a> or positive error in your opacity reading. The high bias is due to the effect of the forward scattering of light.</p>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''sp-forward'', ''sp-forward-icon'')" class="lect-toggle-btn">
                <span>Forward Scattering of Light</span>
                <span id="sp-forward-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="sp-forward" class="lect-toggle-body" style="display:none;">
                <p>From the <a href="https://www3.epa.gov/ttnemc01/methods/VEFieldManual.pdf" target="_blank" rel="noopener" title="EPA Visible Emissions Field Manual">EPA VEO Field Manual</a>: <em>Method 9 Observation Rules are Designed to Eliminate Positive Bias in Readings.</em></p>
                <p><em>Forward scattering enhances the plume visibility and creates a positive bias in measurement results.</em></p>
                <p>An everyday example of forward light scattering: If you are driving into the sunset, you can see every speck of dirt on the windshield. When you turn, and the sun is no longer in front of you, the specks are no longer seen.</p>
                <p>Sunlight will scatter forward, enhancing/exaggerating a white emission so that it is overstated.</p>
            </div>
        </div>
    </div>
</div>',
    6
);
