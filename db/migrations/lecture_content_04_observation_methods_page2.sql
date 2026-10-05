-- ============================================================================
-- Lecture content seed: Observation Methods section, page 2 (Method 9)
-- Reference: Michael, 2026-10-05 -- real content capture from the live
-- methods-method9.php. The five toggle sections are real, collapsible
-- toggles; the left column keeps the image, its caption, and the
-- "Terminology Reminder" box, as on the live page.
--
-- Real, honest link decisions:
--   - Image copied into public/images/lecture/epa-method-9.jpg.
--   - EPA QA Handbook Vol. III section 3.12 PDF ("Source: EPA Method 9
--     Document") checked 200 on 2026-10-05 -- kept.
--   - "New Source Performance Testing (NSPT)" was a bold-red glossary.php
--     link to #NSPT, but there is no NSPT entry in the Glossary (live or
--     ours) -- kept as plain text, matching the established pattern for
--     dead links.
--
-- Live-source bugs fixed: the deviation-scoring list had a nested <ul>
-- placed directly inside the outer <ul> and two <p>s inside the list, with
-- an unclosed <p> before it -- rebuilt as a proper nested list followed by
-- paragraphs; stray "see  the" / "the  observation" double spaces. Live
-- span ids used as search anchors (subj, meth9time, readingerror) dropped.
--
-- Content left as-is but flagged to Michael for CAA review: the pass rule
-- says "37 deviations or less" across all 50 observations (25 white + 25
-- black). Method 9's certification criteria are an average error of no
-- more than 7.5% opacity for EACH set of 25 (white and black scored
-- separately) and no single error over 15%; 7.5% x 25 / 5% per deviation
-- point = 37.5, i.e. 37 points per color, not per combined 50. Worth CAA
-- confirming the wording matches how CAA actually scores runs.
-- ============================================================================

INSERT INTO lecture_pages (lecture_section_id, title, slug, content, order_index)
VALUES (
    (SELECT id FROM lecture_sections WHERE slug = 'observation-methods'),
    'Method 9',
    'methods-method9',
    '<div style="display:flex; gap:2rem; flex-wrap:wrap;">
    <div style="width:380px; flex-shrink:0;">
        <img src="/images/lecture/epa-method-9.jpg" alt="EPA Method 9 and Method 22" style="width:100%; border-radius:0.375rem;">
        <p style="font-size:0.85rem; color:#666666; margin-top:0.4rem;">Method 9 was promulgated in 1974 and has had only one update since its promulgation - it required a sketch that includes the relative positions of the observer, the sun, and the emission source.</p>

        <h2>Terminology Reminder</h2>
        <p>In Method 9, a momentary glance is an <strong>observation</strong>.*</p>
        <p>Twenty-four (24) observations at 15-second intervals over 6 minutes is one Method 9 <strong>reading</strong>.*</p>
        <p><strong>Field certification test runs</strong> consist of 50 observations: 25 white smoke and 25 black smoke.</p>
        <p style="font-size:0.95em;">*These terms are sometimes used interchangeably. For this course, we use the terms described above for consistency.</p>
    </div>
    <div style="flex:1; min-width:320px;">
        <h2>EPA Method 9</h2>
        <h3>Method 9 Quantifies the Level of Opacity</h3>
        <p>Method 9 (the Method) is defined as the visual determination of the opacity of emissions from stationary sources. Method 9 is a statistically-based methodology for quantifying the level of emissions. The measurement is stated in a percentage of opacity, i.e., a percentage of the emission blocks the background.</p>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''m9-basics'', ''m9-basics-icon'')" class="lect-toggle-btn">
                <span>Method 9 Basics</span>
                <span id="m9-basics-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="m9-basics" class="lect-toggle-body" style="display:none;">
                <p>If Method 9 is required in a Title V permit, the person conducting the visible emission observations (VEO) is called the observer. The observer must be trained and certified according to the Method, i.e., attending a hands-on smoke school and periodically passing qualification testing.</p>
                <p>Because the Method is subjective, two observers may simultaneously report different opacity values on the same plume. If both observers have current Method 9 certifications, either observation is valid, even in a deposition or court.</p>
                <p>The frequency of Method 9 readings is governed by the facility&rsquo;s air permit/compliance plan.</p>
            </div>
        </div>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''m9-how'', ''m9-how-icon'')" class="lect-toggle-btn">
                <span>How are Method 9 Observations Performed?</span>
                <span id="m9-how-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="m9-how" class="lect-toggle-body" style="display:none;">
                <p>A single Method 9 reading requires 24 visible emissions observations taken at 15-second intervals for six (6) minutes. Those observations are averaged to calculate the opacity value.</p>
                <p>Permits may require more than 24 observations &ndash; they may specify 12, 18, and 30 minutes, yielding more observations and requiring custom VEO forms. VEO forms must contain required Method 9 fields but can be customized to fit a facility&rsquo;s requirements.</p>
                <p>New Source Performance Testing (NSPT) typically requires three (3) 1-hour observations for each source.</p>
                <p>Opacity values are expressed as a percent and are calculated using a running average.</p>
            </div>
        </div>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''m9-best'', ''m9-best-icon'')" class="lect-toggle-btn">
                <span>Is Method 9 the Best Way to Estimate Opacity?</span>
                <span id="m9-best-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="m9-best" class="lect-toggle-body" style="display:none;">
                <p>All subjective methods have associated errors, and Method 9 is no exception. The efficacy of the Method has been proven in field tests. The results of an EPA study that involved 769 sets of 25 observations for each type of smoke (black and white) yielded:</p>
                <ul>
                    <li>Black plumes: 100% of the sets were estimated with a positive error of less than 7.5% opacity. 99% were estimated with a positive error of less than 5% opacity.</li>
                    <li>White plumes: 99% of the sets were estimated with a positive error (higher values) of less than 7.5% opacity. 95% were estimated with a positive error of less than 5% opacity.</li>
                </ul>
                <p style="font-size:0.75em;"><a href="https://www3.epa.gov/ttnemc01/qahandbook3/qaiii%201977/qa%20vol%20iii%20-%20aug%201977%20-%20sec%203-12.pdf" target="_blank" rel="noopener" title="EPA Method 9 document">Source: EPA Method 9 Document</a></p>
            </div>
        </div>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''m9-coms'', ''m9-coms-icon'')" class="lect-toggle-btn">
                <span>What About Using Continuous Opacity Monitors?</span>
                <span id="m9-coms-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="m9-coms" class="lect-toggle-body" style="display:none;">
                <p>Continuous opacity monitors (COMs) are available to track opacity measurements on stacks. So why aren&rsquo;t COMs used instead of Method 9? That is a complex question - the following is a broad brushstroke of the issues related to COMs:</p>
                <p>COMs are widely used but are prone to drift offsets and downtime - their reliability results in many facilities owning a backup COM to cover mechanical failures. COMs can be more accurate, but case law considers a visible emissions reader more reliable. Additionally, COMs operate on stacks only. Therefore, operations such as rock crushing, material storage piles, handling areas, and transfer points require manual methods.</p>
            </div>
        </div>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''m9-accuracy'', ''m9-accuracy-icon'')" class="lect-toggle-btn">
                <span>How Accurate Do My Observations Need to Be?</span>
                <span id="m9-accuracy-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="m9-accuracy" class="lect-toggle-body" style="display:none;">
                <p>Method 9 allows for a percentage of error. When the Method was promulgated, field trials determined acceptable error levels (see the toggle field above). A pass/fail on a field test run is determined using error percentages and standard deviations.</p>
                <p>To pass a field test run, you must score 37 deviations or less. One <strong>field run</strong> consists of 25 black smoke <strong>point observations</strong> and 25 white smoke <strong>point observations</strong>. The number 37 is calculated as follows:</p>
                <ul>
                    <li>Each point observation can be 15% on either side of the correct opacity level. For example, if a point plume with 25% opacity is shown, an observer can answer as low as 10% or as high as 40% and be within the 15% allowance.</li>
                    <li>Each point observation is assigned a value called a deviation:
                        <ul>
                            <li>Zero (0) for exact observations.</li>
                            <li>One (1) for observations within 5% accuracy.</li>
                            <li>Two (2) for observations within 10% accuracy.</li>
                            <li>Three (3) for observations within 15% accuracy.</li>
                            <li>If an observation is outside the 15% accuracy, the observation is considered a failed observation. One failed observation constitutes a failed run.</li>
                        </ul>
                    </li>
                </ul>
                <p>The scores are added together at the end of the 50 test point observations (25 white, 25 black). If you score 37 deviations or below, you pass.</p>
                <p>If you are 38 or above OR have failed one test point observation, you do not pass.</p>
                <p>Note: You can certify if you have two contiguous passed runs. For example, runs consist of 25 white followed by 25 black - you can pass if you fail white on Run #1, pass black on Run #1, then pass white on Run #2. This example equates to 50 contiguous observations, and if your deviations are within the passing parameters, you certify for Method 9.</p>
            </div>
        </div>
    </div>
</div>',
    2
);
