-- ============================================================================
-- Lecture content seed: Basics of Observations section, page 5 (Plume
-- Shapes)
-- Reference: Michael, 2026-10-05 -- real content capture from the live
-- basics-plume-shapes.php. The three toggle sections are real, collapsible
-- toggles.
--
-- Real, honest link decisions:
--   - Image /images/lecture/plume-types.jpg is already in the repo.
--   - The source line was a bare, unlinked URL on the live page; it checked
--     200 on 2026-10-05, so it is now a real link with the same text.
--
-- Live-source bugs fixed: a stray closing </a> after the image (no opening
-- link); trailing spaces; spacer <br/>s between the toggles dropped (the
-- toggle styling spaces them).
-- ============================================================================

INSERT INTO lecture_pages (lecture_section_id, title, slug, content, order_index)
VALUES (
    (SELECT id FROM lecture_sections WHERE slug = 'basics-of-observations'),
    'Plume Shapes',
    'basics-plume-shapes',
    '<div style="display:flex; gap:2rem; flex-wrap:wrap;">
    <div style="width:380px; flex-shrink:0;">
        <img src="/images/lecture/plume-types.jpg" alt="Smoke plume shapes" style="width:100%; border-radius:0.375rem;">
    </div>
    <div style="flex:1; min-width:320px;">
        <h2>Plume Shapes and What They Tell Us</h2>
        <p>The shape of a plume is an indicator of atmospheric moisture and stability - conditions that can affect visible emission observations.</p>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''ps-shapes'', ''ps-shapes-icon'')" class="lect-toggle-btn">
                <span>Plume Shapes</span>
                <span id="ps-shapes-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="ps-shapes" class="lect-toggle-body" style="display:none;">
                <h3>Coning Plume</h3>
                <p>Plumes that spread out evenly - up, down, and sideways. The smoke shows no preference for moving up or moving down.</p>
                <h3>Lofting Plume</h3>
                <p>A lofting plume expands upward. It indicates that the atmospheric conditions allow the plume to rise and expand.</p>
                <h3>Fanning Plume</h3>
                <p>A fanning plume spreads out horizontally and less vertically. This plume indicates that the local atmosphere is resisting upward or downward motion.</p>
                <h3>Fumigating Plume</h3>
                <p>A fumigating plume expands downward, causing the area to become fumigated with smoke or steam. This type of plume indicates that the air prevents the plume from rising.</p>
                <h3>Looping Plume</h3>
                <p>Looping plumes look like up-and-down curves, i.e., loops. A looping plume indicates that there is turbulence and vertical movement. The smoke alternately is affected by upward and downward air rushes with lulls in between.</p>
            </div>
        </div>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''ps-wind'', ''ps-wind-icon'')" class="lect-toggle-btn">
                <span>How Wind Affects Plume Shapes</span>
                <span id="ps-wind-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="ps-wind" class="lect-toggle-body" style="display:none;">
                <p>Wind speed affects a plume&rsquo;s appearance. A strong wind would favor a fanning plume as the wind pushes it downwind faster than it can spread out. In contrast, a light wind would accentuate any looping characteristics of a plume. The plume characteristics represent the atmosphere conditions at the top of the smokestack. Conditions higher in the atmosphere might be different.</p>
            </div>
        </div>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''ps-tell'', ''ps-tell-icon'')" class="lect-toggle-btn">
                <span>What Plume Shapes Tell Us</span>
                <span id="ps-tell-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="ps-tell" class="lect-toggle-body" style="display:none;">
                <p>The form of a plume provides clues about whether the atmospheric conditions favor the rising of the air (an unstable atmosphere), sinking of the air (a very stable atmosphere), or something in between.</p>
            </div>
        </div>

        <p style="font-size:0.8em;">Source: <a href="https://geography.name/what-do-smoke-plumes-tell-us-about-atmospheric-conditions/" target="_blank" rel="noopener">https://geography.name/what-do-smoke-plumes-tell-us-about-atmospheric-conditions/</a></p>
    </div>
</div>',
    5
);
