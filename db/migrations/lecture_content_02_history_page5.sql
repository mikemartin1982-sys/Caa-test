-- ============================================================================
-- Lecture content seed: History section, page 5 (Recent History)
-- Reference: Michael, 2026-09-28 -- real content capture from the live
-- history-recent.php. Toggle sections built as real, collapsible toggles.
-- Last page of the History section; the section quiz follows.
--
-- Real, honest link decisions:
--   - Image copied into public/images/lecture/birmingham-alabama-today.jpg.
--   - "fugitive emissions" was a bold-red glossary.php link -> our Glossary
--     resource, /lecture/resources/glossary#fugitive.
--   - EPA Title V summary page (epa.gov) is a real external link -- kept.
--
-- Live-source bugs fixed: unclosed <p> tags (Title V self-monitoring
-- sentence, the EPA-website sentence) and an empty stray <p> at the end of
-- the 1990 CAAa toggle; stray <br> at the top of the Title V toggle;
-- "NAAQs" -> "NAAQS".
--
-- Content left as-is but flagged to Michael for CAA review: NPDES is the
-- "National Pollutant Discharge Elimination System" (the page says "Federal
-- National Pollution Elimination Discharge System"), and the 1990 CAA
-- Amendments had eleven titles (the page says "one of seven titles").
-- ============================================================================

INSERT INTO lecture_pages (lecture_section_id, title, slug, content, order_index)
VALUES (
    (SELECT id FROM lecture_sections WHERE slug = 'history'),
    'Recent History',
    'history-recent',
    '<div style="display:flex; gap:2rem; flex-wrap:wrap;">
    <div style="width:380px; flex-shrink:0;">
        <img src="/images/lecture/birmingham-alabama-today.jpg" alt="Birmingham, Alabama today" style="width:100%; border-radius:0.375rem;">
    </div>
    <div style="flex:1; min-width:320px;">
        <h2>Achieving Clean Air Through Compliance</h2>
        <p>The 1980s saw cleaner air in the U.S. through the national emissions standards and regulations; most jurisdictions met air quality standards. As a result, the EPA changed its emphasis from compliance enforcement to compliance maintenance. The updates to the CAA increased the scope of regulated toxins and enhanced environmental protections. By the late 1980s, much of those original criteria pollutant National Ambient Air Quality Standards (NAAQS) were met.</p>

        <h4>Method 22</h4>
        <p>In 1982, Method 22 was promulgated.</p>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''hrec-m22'', ''hrec-m22-icon'')" class="lect-toggle-btn">
                <span>Method 22</span>
                <span id="hrec-m22-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="hrec-m22" class="lect-toggle-body" style="display:none;">
                <p>Method 22 is a visible emission observation method that simply checks for the presence or absence of visible emissions. It is to regulate <a href="/lecture/resources/glossary#fugitive">fugitive emissions</a>. In a factory setting, fugitive emissions are typically caused by leaks or damage to machinery.</p>
            </div>
        </div>

        <h4>The Clean Air Act Amendment (CAAa) of 1990</h4>
        <p>The 1990 CAAa targeted four harmful threats to human health and the environment.</p>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''hrec-caaa'', ''hrec-caaa-icon'')" class="lect-toggle-btn">
                <span>The 1990 Clean Air Act Amendments</span>
                <span id="hrec-caaa-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="hrec-caaa" class="lect-toggle-body" style="display:none;">
                <p>The 1990 CAAa legislation contained many titles (sections), adding over 1,000 air toxics to the list of controlled substances, and dealt with issues needing improvement in utility, industrial, and mobile source emissions.</p>
                <p>The 1990 CAAa addressed four major threats: acid rain, urban smog, toxic air pollution, and the hole in the Earth&rsquo;s ozone layer.</p>
                <p>The 1990 CAAa amendments included establishing permit program requirements and expanded and updated the National Ambient Air Quality Standards (NAAQS). Additionally, the legislation updated and expanded enforcement authority, including requiring emission sources to certify their compliance and giving the EPA authority to issue administrative subpoenas.</p>
                <p>Penalties for non-compliance became steeper. The EPA was granted authority to issue administrative penalty orders up to $200,000 and field citations up to $5,000 for lesser offenses.</p>
            </div>
        </div>

        <h4>Title V Requirement</h4>
        <p>Title V operating permits become mandatory as part of the 1990 CAAa.</p>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''hrec-titlev'', ''hrec-titlev-icon'')" class="lect-toggle-btn">
                <span>Title V Permitting</span>
                <span id="hrec-titlev-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="hrec-titlev" class="lect-toggle-body" style="display:none;">
                <p>Title V was one of seven titles (sections) of the 1990 CAAa. The law introduced an operating permit program that was based on the design of the Federal National Pollution Elimination Discharge System (NPDES) law. Title V was introduced to ensure compliance with the CAAa and increased the enforcement authority of the EPA.</p>
                <p>Title V&rsquo;s permitting program required self-monitoring and reporting, where emission sources maintain continual compliance and provide notice and self-report any non-compliance.</p>
                <p>Title V required the following:</p>
                <ul>
                    <li>Emission sources must obtain an operating permit.</li>
                    <li>States must develop and implement the permit program.</li>
                    <li>The EPA issues permit program regulations, reviews state programs, and oversees state programs.</li>
                    <li>The EPA develops and implements a state program if a state fails to establish its own program.</li>
                </ul>
                <p>Detailed information can be found on the <a href="https://www.epa.gov/clean-air-act-overview/1990-clean-air-act-amendment-summary-title-v" target="_blank" rel="noopener" title="EPA Title V information">EPA website</a>.</p>
            </div>
        </div>
    </div>
</div>',
    5
);
