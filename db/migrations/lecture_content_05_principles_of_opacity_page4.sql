-- ============================================================================
-- Lecture content seed: Principles of Opacity section, page 4 (Properties of
-- Light)
-- Reference: Michael, 2026-10-05 -- real content capture from the live
-- principles-light.php. Last page of the section; the section quiz follows.
-- The four toggle sections are real, collapsible toggles; the left column
-- keeps the light-scattering video and its script link.
--
-- Real, honest link decisions:
--   - Video: the live Light-scattering-mechanics-video.mp4 is ~208 MB, over
--     GitHub's 100 MB file limit, so it is NOT in git. It is served from
--     /videos/ (public-portal/public/videos/ is gitignored) and copied to
--     the PC and the server by hand. preload="metadata" so the page does
--     not pull the whole file until the student presses play.
--   - Poster image copied into public/images/lecture/light-video-slide-cover.jpg.
--   - Video script PDF already in public/PDFs/ (12700403-CAA-Light-
--     Scattering-Script.pdf).
--   - visible-spectrum.jpg copied into public/images/lecture/.
--   - EPA Visible Emissions Field Manual PDF checked 200 on 2026-10-05 -- kept.
--
-- Live-source bugs fixed: the <video> had a second .ogg <source> pointing
-- at a file that does not exist -- dropped; fixed width="600px" video and
-- width="700" spectrum image (overflowed narrow screens) are now width:100%;
-- "spec(s) of dirt" -> "speck(s) of dirt"; "Mie Scattering </strong> occurs"
-- double space and other double/trailing spaces; stray <hr>s after the
-- toggles and an empty trailing paragraph dropped; live span ids used as
-- search anchors (whitesm, con, green) dropped. The opening paragraph that
-- the live page repeats at the top of both the "Plume Colors" and
-- "Background" toggles is kept in both, as on the live page.
--
-- Content left as-is but flagged to Michael for CAA review:
--   - "Refraction occurs when light hits a curved surface of particulate
--     matter, causing the light to bounce off in different directions" --
--     bouncing off is reflection; refraction is light bending as it passes
--     into/through the particle.
--   - Background toggle: "Green ... has a high frequency" and "low-frequency
--     light (i.e., reds and violets near sunset and sunrise)" -- violet is
--     the HIGHEST-frequency visible light (red is the lowest) and green is
--     mid-spectrum. Worth CAA rewording the frequency language.
-- ============================================================================

INSERT INTO lecture_pages (lecture_section_id, title, slug, content, order_index)
VALUES (
    (SELECT id FROM lecture_sections WHERE slug = 'principles-of-opacity'),
    'Properties of Light',
    'principles-light',
    '<div style="display:flex; gap:2rem; flex-wrap:wrap;">
    <div style="width:380px; flex-shrink:0;">
        <video controls preload="metadata" poster="/images/lecture/light-video-slide-cover.jpg" style="width:100%; border-radius:0.375rem;">
            <source src="/videos/Light-scattering-mechanics-video.mp4" type="video/mp4">
            Your browser does not support the video tag.
        </video>
        <p style="font-size:0.85rem; color:#666666; margin-top:0.4rem;">View the video by clicking on the image.</p>
        <h4>Video Script</h4>
        <p><a href="/PDFs/12700403-CAA-Light-Scattering-Script.pdf" target="_blank" rel="noopener" title="Light scattering principles video script">Click here to read the video script &raquo;</a></p>
    </div>
    <div style="flex:1; min-width:320px;">
        <h2>Principles of Light</h2>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''pl-interaction'', ''pl-interaction-icon'')" class="lect-toggle-btn">
                <span>Interaction of Light With Plumes</span>
                <span id="pl-interaction-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="pl-interaction" class="lect-toggle-body" style="display:none;">
                <h4>Scattering of Light</h4>
                <p>When light encounters a plume, the plume scatters, absorbs, or transmits (passes through) the light. How the plume interacts with light is determined by the particulate&rsquo;s shape, color, and size and the direction and intensity of the light.</p>
                <p>When the particulate matter is LARGER than the wavelengths of light, the light is reflected or refracted:</p>
                <ul>
                    <li><strong>Reflection</strong> is when the light bounces off the particle. White particles reflect more light than black particles.</li>
                    <li><strong>Refraction</strong> occurs when light hits a curved surface of particulate matter, causing the light to bounce off in different directions.</li>
                </ul>
                <p>When particles are SMALLER than the waves of light, the light is scattered according to these principles:</p>
                <ul>
                    <li><strong>Rayleigh Scattering</strong> occurs when the particulate matter is significantly smaller than light waves. Light is scattered at large angles from the original direction of the light.</li>
                    <li><strong>Mie Scattering</strong> occurs when particles and light wavelengths are the same size, and the light waves are reflected from the particle.</li>
                </ul>
                <h4>Absorption of Light</h4>
                <p>Light is absorbed when particles are not transparent. The light the particle absorbs is converted to heat energy and emitted as infrared. Colored particles absorb only specific wavelengths of light. Black particles absorb all colors of light. White particles reflect all colors of light.</p>
                <h4>Transmission</h4>
                <p>The light that does not hit any particle in a plume is transmitted through the plume. NOTE: Additionally, light CAN be transmitted through particles in the plume. However, transmission through a particle occurs VERY infrequently because the light has to hit the particle at an EXACT angle.</p>
            </div>
        </div>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''pl-forward'', ''pl-forward-icon'')" class="lect-toggle-btn">
                <span>Forward Scattering of Light</span>
                <span id="pl-forward-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="pl-forward" class="lect-toggle-body" style="display:none;">
                <p>The forward scattering of light is the principle that dictates the sun be behind the observer. Sunlight scatters forward, enhancing/exaggerating an emission, commonly causing it to be overstated.</p>
                <p>From the <a href="https://www3.epa.gov/ttnemc01/methods/VEFieldManual.pdf" target="_blank" rel="noopener" title="EPA Visible Emissions Field Manual">EPA VEO Field Manual</a>: <em>Method 9 Observation Rules are Designed to Eliminate Positive Bias in Readings.</em></p>
                <p><em>Forward scattering enhances the plume visibility and creates a positive bias in measurement results.</em></p>
                <p>An everyday example of forward light scattering: If you are driving into the sunset, you can see every speck of dirt on the windshield. When you turn, and the sun is no longer in front of you, the specks are no longer seen.</p>
            </div>
        </div>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''pl-colors'', ''pl-colors-icon'')" class="lect-toggle-btn">
                <span>Plume Colors and Visibility</span>
                <span id="pl-colors-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="pl-colors" class="lect-toggle-body" style="display:none;">
                <p>In visible emissions observations, opacity is expressed as the percentage of the background obscured by the plume. The background light through the plume is used to quantify opacity.</p>
                <p>When reading white smoke or light-colored emissions, much of the light is reflected off the plume or refracted within the plume. This results in a highly luminous plume.</p>
                <p>Black smoke or dark-colored emissions absorb most of the light. The resulting minimal or absence of light makes these plumes less luminous.</p>
                <p>It is important to distinguish the <strong>plume&rsquo;s</strong> visibility from the <strong>background&rsquo;s</strong> visibility. For instance, a white smoke plume with 25% opacity will be more visible to the human eye than a black smoke plume with 25% opacity. <em>The obscuring power between each plume is the same.</em></p>
                <p>As an example, a window screen is made out of black wire. It is easy to see through, i.e., you can see the objects outside easily. Even though it blocks the same amount of light, the same screen made with white wire would be more difficult to see through.</p>
            </div>
        </div>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''pl-background'', ''pl-background-icon'')" class="lect-toggle-btn">
                <span>The Importance of the Background in Opacity Readings</span>
                <span id="pl-background-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="pl-background" class="lect-toggle-body" style="display:none;">
                <p>In visible emissions observations, opacity is expressed as the percentage of the background obscured by the plume. The background light through the plume is used to quantify opacity.</p>
                <p>It is desirable to have a highly contrasting background for visual comparison. The observer should attempt to position themselves so that the background is highly contrasted with the plume.</p>
                <p>Consider the visible spectrum of color:</p>
                <img src="/images/lecture/visible-spectrum.jpg" alt="Visible spectrum of color" style="width:100%; max-width:700px; border-radius:0.375rem;">
                <p>Green is the color most visible to the human eye; it has a high frequency and is high in contrast to white. It makes a good background choice for light-colored plumes. Sky blue is high in contrast and luminosity and provides an optimum background for black smoke.</p>
                <p>It is best to avoid backgrounds or lighting conditions where low-frequency light (i.e., reds and violets near sunset and sunrise) is produced. These can result in a negative bias in your observation.</p>
            </div>
        </div>
    </div>
</div>',
    4
);
