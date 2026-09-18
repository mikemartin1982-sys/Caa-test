-- ============================================================================
-- Lecture content seed: Introduction section, page 3 (About Visible
-- Emissions Observations)
-- Reference: Michael, 2026-09-07 -- real content capture. Both toggle
-- sections ("Methods and Definitions," "A well-trained and certified
-- Method 9 observer should know") built as real, collapsible toggles,
-- matching the now-standing pattern for this course.
--
-- Real, honest link decisions: four separate glossary.php mentions
-- (#veo, #opacity, #ss, and the plain closing-note link) -- glossary.php
-- has no equivalent on our platform at all yet, so all four kept as
-- plain text, links removed, matching the established pattern for dead
-- links used on the previous two pages.
--
-- Dropped the original's `style="display:grid"` on the toggle list
-- items -- an unusual, likely unintentional styling choice inconsistent
-- with plain <ul> lists used everywhere else in this course.
-- ============================================================================

INSERT INTO lecture_pages (lecture_section_id, title, slug, content, order_index)
VALUES (
    (SELECT id FROM lecture_sections WHERE slug = 'introduction'),
    'About Visible Emissions Observations',
    'about-veo',
    '<div style="display:flex; gap:2rem; flex-wrap:wrap;">
    <div style="width:380px; flex-shrink:0;">
        <img src="/images/lecture/visible-emissions-observations.jpg" alt="What are visible emissions observations" style="width:100%; border-radius:0.375rem;">
    </div>
    <div style="flex:1; min-width:320px;">
        <h2>What Are Visible Emissions Observations?</h2>
        <p>The term visible emissions observations (VEO) includes several methods that are defined by the EPA, including Method 9, Method 22, and more.</p>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''veo-methods'', ''veo-methods-icon'')" class="lect-toggle-btn">
                <span>Methods and Definitions</span>
                <span id="veo-methods-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="veo-methods" class="lect-toggle-body" style="display:none;">
                <p>EPA Method 9 visible emission observations are taken of visible emissions from emission sources to quantify opacity of the emissions. Method 9 readings must be taken on a periodic basis by qualified observers - individuals that have been trained and qualified in accordance with Method 9. Method 9 qualification testing occurs at a hands-on, in-person event called a smoke school. Smoke schools must use equipment and procedures that meet Method 9 requirements to document the statistical accuracy and reliability of the observer.</p>
                <p>Visible emissions observations have two subsets: Opacity methods (i.e., Method 9) and non-opacity methods (i.e., Method 22 or a permit condition requiring an observation without specifying a method). If your organization''s permit requires an opacity observation or opacity reading, Method 9 is implied.</p>
                <p>Visible emissions observations include:</p>
                <ul>
                    <li>Method 9 - which <strong>quantifies</strong> the opacity level.</li>
                    <li>Method 22 - documents the <strong>duration of emissions</strong> during a specified observation time.</li>
                    <li>Quick Check - a momentary glance without specific criteria or requirement.</li>
                </ul>
            </div>
        </div>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''veo-knows'', ''veo-knows-icon'')" class="lect-toggle-btn">
                <span>A well-trained and certified Method 9 observer should know:</span>
                <span id="veo-knows-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="veo-knows" class="lect-toggle-body" style="display:none;">
                <ul>
                    <li>The terms, underlying concepts, and principles related to visible emissions observations.</li>
                    <li>A brief history of the evolution of VEO that illustrates the importance of compliance.</li>
                    <li>What steps need to be taken before performing an opacity reading.</li>
                    <li>How to read the opacity of emissions and perform specialized readings. Opacity readers need to understand and avoid interferences that may bias or invalidate observations.</li>
                    <li>How to complete the VEO form.</li>
                </ul>
            </div>
        </div>

        <p>This course covers the above points and prepares you for field qualification testing.</p>
        <p>NOTE: Throughout the course, links that are bold red will take you to our glossary page for a definition of the selected text.</p>
    </div>
</div>',
    3
);
