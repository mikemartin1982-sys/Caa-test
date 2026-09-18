-- ============================================================================
-- Lecture content seed: Introduction section, page 4 (Definition ::
-- Visible Emissions)
-- Reference: Michael, 2026-09-07 -- real content capture. Both toggle
-- sections ("Point Source Emissions," "Fugitive Emissions") built as
-- real, collapsible toggles, matching the standing pattern for this
-- course.
--
-- Real, honest link decisions: seven separate glossary.php mentions
-- (#veo, #ss used twice, #point, #fug, #method9, #method22, #quick) --
-- glossary.php has no equivalent on our platform at all yet, so all kept
-- as plain text, links removed, matching the established pattern.
--
-- Real, genuine bug found and fixed: the "Fugitive Emissions" toggle in
-- the real, live source has a malformed <ul> -- its last <li> and the
-- <ul> itself are never actually closed before the surrounding divs end.
-- Closed properly here rather than carried forward.
-- ============================================================================

INSERT INTO lecture_pages (lecture_section_id, title, slug, content, order_index)
VALUES (
    (SELECT id FROM lecture_sections WHERE slug = 'introduction'),
    'Definition :: Visible Emissions',
    'veo-definition-veo',
    '<div style="display:flex; gap:2rem; flex-wrap:wrap;">
    <div style="width:380px; flex-shrink:0;">
        <img src="/images/lecture/visible-emissions.jpg" alt="Type of visible emissions" style="width:100%; border-radius:0.375rem;">
    </div>
    <div style="flex:1; min-width:320px;">
        <h2>Definition of Visible Emissions</h2>
        <p>Visible emissions are emissions that can be seen by the human eye. Heat distortions and condensed water vapor are NOT considered visible emissions.</p>
        <p>Visible emissions include gas, vapor, and particulate matter. Visible emissions are regulated requirements are promulgated at the federal level through the Environmental Protection Agency (EPA), and at state and local jurisdictions.</p>
        <p>There are two types of visible emissions: point source emissions and fugitive emissions.</p>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''veo-point'', ''veo-point-icon'')" class="lect-toggle-btn">
                <span>Point Source Emissions</span>
                <span id="veo-point-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="veo-point" class="lect-toggle-body" style="display:none;">
                <ul>
                    <li>Point source emissions are typically released by stationary sources such as smokestacks and chimneys. A point source is defined by a longitude, latitude, and elevation and is defined in your organization''s air permit. EPA methods are used for evaluation of all sources of visible emissions, including transfer points, piles of dusty materials, unpaved roads, and fence line monitoring.</li>
                    <li>EPA Method 9 evaluates and quantifies point source emissions.</li>
                    <li>EPA Method 9 requires certification, i.e., successful qualification at a smoke school.</li>
                </ul>
            </div>
        </div>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''veo-fugitive'', ''veo-fugitive-icon'')" class="lect-toggle-btn">
                <span>Fugitive Emissions</span>
                <span id="veo-fugitive-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="veo-fugitive" class="lect-toggle-body" style="display:none;">
                <ul>
                    <li>Fugitive emissions are unintended releases. They may be a result of sanding, driving vehicles on a dirt road, mechanical failures that release material from ductwork, or from building openings, etc.</li>
                    <li>EPA Method 22, known as the fugitive emissions method, evaluates the presence of emissions.</li>
                    <li>EPA Method 22 does not require certification, but observers should be knowledgeable about the principles of Method 9 readings. Attendance at a field training smoke school is helpful for Method 22 observers, but not required.</li>
                    <li>Method 22 observers should be knowledgeable about the principles of Method 9 readings. From a practical standpoint, the Method 22 observer needs to tell the difference between heat distortion, condensed water vapor, and a visible emission.</li>
                </ul>
            </div>
        </div>

        <p>A Quick-Check observation is what Compliance Assurance defines a "look and see" observation to determine if there are any emissions from sources. If emissions are seen, a visible emission observation by a specific EPA method is triggered.</p>
    </div>
</div>',
    4
);
