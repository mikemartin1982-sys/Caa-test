-- ============================================================================
-- Lecture content seed: History section, page 4 (1960s and 1970s)
-- Reference: Michael, 2026-09-28 -- real content capture from the live
-- history-1960s-70s.php. Toggle sections built as real, collapsible toggles;
-- the four-image gallery uses the shared .lect-slides component (captions =
-- the live thumbnails' alt text).
--
-- Real, honest link decisions:
--   - Images copied into public/images/lecture/ (birmingham-photo-1.jpg,
--     birmingham-photo-2.jpg, Los-Angeles-1966.jpg, epa-formation.jpg).
--   - Bold-red glossary.php links -> our Glossary resource. The live links
--     used upper-case anchors (#NAAQS, #SIPs, #NSPS) that don't match the
--     glossary's own ids; pointed at the real ids instead: CAA -> #clean,
--     NAAQS -> #naaqs, SIPs (twice) -> #sip, NSPS -> #nsps,
--     promulgated -> #prom.
--   - NESHAPs has no Glossary entry (live or ours) -- kept as plain text,
--     matching the established pattern for dead links.
--
-- Live-source bugs fixed: unclosed <p> tags (the CAA paragraph and the
-- PSD sentence); the "(NAAQS" link text that swallowed its own opening
-- parenthesis; stray <br> at the top of three toggles. The live span id
-- "epa" (search anchor) is dropped.
--
-- Content left as-is but flagged to Michael: the live page says the PCV
-- valve was added "as a part of a CAFE standard in 1960"; CAFE (fuel
-- economy) standards date from 1975, so that sentence may need a content
-- review by CAA.
-- ============================================================================

INSERT INTO lecture_pages (lecture_section_id, title, slug, content, order_index)
VALUES (
    (SELECT id FROM lecture_sections WHERE slug = 'history'),
    '1960s and 1970s',
    'history-1960s-70s',
    '<div style="display:flex; gap:2rem; flex-wrap:wrap;">
    <div style="width:380px; flex-shrink:0;">
        <div class="lect-slides" id="h6070-slides">
            <figure class="lect-slide is-active">
                <img src="/images/lecture/birmingham-photo-1.jpg" alt="Birmingham, Alabama - 1966">
                <figcaption>Birmingham, Alabama - 1966</figcaption>
            </figure>
            <figure class="lect-slide">
                <img src="/images/lecture/birmingham-photo-2.jpg" alt="Birmingham, Alabama - 1966">
                <figcaption>Birmingham, Alabama - 1966</figcaption>
            </figure>
            <figure class="lect-slide">
                <img src="/images/lecture/Los-Angeles-1966.jpg" alt="Los Angeles in 1966">
                <figcaption>Los Angeles in 1966</figcaption>
            </figure>
            <figure class="lect-slide">
                <img src="/images/lecture/epa-formation.jpg" alt="William Ruckelshaus sworn in as first EPA Administrator.">
                <figcaption>William Ruckelshaus sworn in as first EPA Administrator.</figcaption>
            </figure>
            <button type="button" class="lect-slide-prev" onclick="lectureSlide(''h6070-slides'', -1)" aria-label="Previous image">&#10094;</button>
            <button type="button" class="lect-slide-next" onclick="lectureSlide(''h6070-slides'', 1)" aria-label="Next image">&#10095;</button>
            <div class="lect-slide-thumbs">
                <img class="is-active" src="/images/lecture/birmingham-photo-1.jpg" alt="Birmingham, Alabama - 1966" onclick="lectureSlideTo(''h6070-slides'', 0)">
                <img src="/images/lecture/birmingham-photo-2.jpg" alt="Birmingham, Alabama - 1966" onclick="lectureSlideTo(''h6070-slides'', 1)">
                <img src="/images/lecture/Los-Angeles-1966.jpg" alt="Los Angeles in 1966" onclick="lectureSlideTo(''h6070-slides'', 2)">
                <img src="/images/lecture/epa-formation.jpg" alt="William Ruckelshaus sworn in as first EPA Administrator" onclick="lectureSlideTo(''h6070-slides'', 3)">
            </div>
        </div>
    </div>
    <div style="flex:1; min-width:320px;">
        <h2>The 1960s and 1970s: The Clean Air Act and the EPA</h2>
        <p>The 1960s and the 1970s enacted legislation that promoted and enforced environmental regulations.</p>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''h6070-early60s'', ''h6070-early60s-icon'')" class="lect-toggle-btn">
                <span>Early 1960s</span>
                <span id="h6070-early60s-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="h6070-early60s" class="lect-toggle-body" style="display:none;">
                <p>The PCV Valve was added to all new automobiles as a part of a CAF&Eacute; standard in 1960. Several additional Federal bills regarding air pollution were passed in the 1960s, funding studies and defining jurisdictions.</p>
            </div>
        </div>

        <h4>The Clean Air Act of 1963</h4>
        <p>In 1963, the government instituted a national effort aimed at improving air quality in the U.S.</p>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''h6070-caa1963'', ''h6070-caa1963-icon'')" class="lect-toggle-btn">
                <span>The First Clean Air Act</span>
                <span id="h6070-caa1963-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="h6070-caa1963" class="lect-toggle-body" style="display:none;">
                <p>The first Clean Air Act (CAA) was passed in 1963. It focused on creating research methods for monitoring and controlling air pollution from stationary sources. It was the first modern environmental law. The primary goal of the program was to protect and enhance air quality in order to promote public health. It provided $95 million of funding to assist state and local governments in the prevention of pollution.</p>
            </div>
        </div>

        <h4>The Federal Air Quality Act and AP-30 (Air Pollution-30)</h4>
        <p>Air quality regions are established, and a joint government/industry study is completed.</p>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''h6070-faqa'', ''h6070-faqa-icon'')" class="lect-toggle-btn">
                <span>The 1967 Federal Air Quality Act (FAQA) and AP-30</span>
                <span id="h6070-faqa-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="h6070-faqa" class="lect-toggle-body" style="display:none;">
                <p>The 1967 Federal Air Quality Act required states to establish air quality regions and to adopt Ambient Air Quality standards. This served as a model for the State Implementation Plans (SIPs). The law also included the transfer of control of automobile emissions to the federal level.</p>
                <p>The U.S. government published AP-30 - "Optical Properties and Visual Effects of Smoke-Stack Plumes." The report was a result of a joint study between industry and the government on the study of opacity. The document detailed the accuracy of a human smoke reader compared to a transmissometer. Additionally, it stated the effect of readings when the sun is not in the correct position relative to the emission source. AP-30 established opacity as a federal regulatory tool.</p>
            </div>
        </div>

        <h4>1970 - The EPA is Formed and the Clean Air Act (CAA) is Updated</h4>
        <p>In 1970, the U.S. Environmental Protection Agency (EPA) was formed, and the CAA was revised.</p>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''h6070-epa'', ''h6070-epa-icon'')" class="lect-toggle-btn">
                <span>The EPA and 1970 CAA Answer Major Environmental Concerns</span>
                <span id="h6070-epa-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="h6070-epa" class="lect-toggle-body" style="display:none;">
                <p>The EPA was formed in December 1970. The EPA is an independent executive agency of the government whose mission is to protect human health and the environment. The EPA has several functions, including research, grants, and education. For the VEO industry, the primary function of the EPA is to develop and enforce clean air regulations.</p>
                <p>The <a href="/lecture/resources/glossary#clean">CAA</a> was updated shortly after the formation of the EPA. The 1970 CAA amendments signified a major change to air quality regulations. The CAA changes elevated the federal government&rsquo;s role and authority in clean air regulations. The legislation called for the development of federal and state air quality emissions standards for stationary and mobile sources. The EPA had the authority to make regulations based on science, not political expediency.</p>
                <p>For stationary sources, the CAA created four regulatory programs. Click on the links to learn details about each program:</p>
                <ul>
                    <li><a href="/lecture/resources/glossary#naaqs">National Ambient Air Quality Standards</a> (NAAQS - referred to as "knacks")</li>
                    <li><a href="/lecture/resources/glossary#sip">State Implementation Plans</a> (SIPs)</li>
                    <li><a href="/lecture/resources/glossary#nsps">New Source Performance Standards</a> (NSPS)</li>
                    <li>National Emission Standards for Hazardous Air Pollutants (NESHAPs)</li>
                </ul>
                <p>In the 1970s, Prevention of Significant Deterioration (PSD) permitting and new source review requirements were instituted.</p>
            </div>
        </div>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''h6070-sips'', ''h6070-sips-icon'')" class="lect-toggle-btn">
                <span>State Implementation Plans (SIPs)</span>
                <span id="h6070-sips-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="h6070-sips" class="lect-toggle-body" style="display:none;">
                <p><a href="/lecture/resources/glossary#sip">SIPs</a> were submitted to the federal government in the early 1970s and were approved of by the EPA, and entered into the Code of Federal Regulations (CFR).</p>
            </div>
        </div>

        <h4>Method 9 Introduced</h4>
        <p>In 1974, the EPA introduced and <a href="/lecture/resources/glossary#prom">promulgated (to make officially known)</a> Method 9 for reading the opacity of smoke plumes.</p>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''h6070-m9'', ''h6070-m9-icon'')" class="lect-toggle-btn">
                <span>Method 9</span>
                <span id="h6070-m9-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="h6070-m9" class="lect-toggle-body" style="display:none;">
                <p>On November 12, 1974, the EPA published &ldquo;Method 9 - Visual Determination of Opacity from Stationary Sources&rdquo; in the Federal Register. It was immediately challenged by a cement plant in Portland that was directed to maintain a 10% opacity limit for emissions. The Portland Cement Association sued the EPA on the grounds that Method 9 was not accurate enough to support the standard.</p>
                <p>As a result of the Portland case, the EPA spent a year doing field studies on Method 9. The studies resulted in the following:</p>
                <ul>
                    <li>The opacity limit for Portland cement plants was raised from 10% to 20% opacity.</li>
                    <li>The Method 9 data reduction scheme was revised to include averaging.</li>
                    <li>More specific observation and training requirements were established.</li>
                </ul>
                <p>The resulting Method 9 was the first modern version of the method. Since the second promulgation, only one minor change to Method 9 has occurred.</p>
                <p>With the inception of Method 9, the EPA stopped using the Ringelmann numbers as a reference for opacity measurements.</p>
            </div>
        </div>
    </div>
</div>',
    4
);
