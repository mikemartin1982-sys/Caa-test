-- ============================================================================
-- Lecture content seed: Observation Methods section, page 1 (Introduction)
-- Reference: Michael, 2026-10-05 -- real content capture from the live
-- methods-introduction.php. The two toggle sections are real, collapsible
-- toggles.
--
-- Real, honest link decisions:
--   - Image copied into public/images/lecture/veo-word-cloud.jpg.
--   - Bold-red glossary.php links -> our Glossary resource: "Title V
--     operating permits" -> #titlev, "quick checks" -> #quick, "fugitive
--     emissions" -> #fugitive.
--   - The live EPA link is a bit.ly short link (https://bit.ly/3axsLQI); it
--     resolves (checked 2026-10-05) to the EPA "Air Emissions Monitoring
--     Permits" page, so the real EPA address is linked directly instead of
--     the shortener -- same destination, no third-party redirect. The link
--     text shows the EPA page name rather than the bit.ly address.
--
-- Live-source bugs fixed: unclosed <p> after the opening Title V sentence;
-- stray <hr> dividers after the toggles dropped.
-- ============================================================================

INSERT INTO lecture_pages (lecture_section_id, title, slug, content, order_index)
VALUES (
    (SELECT id FROM lecture_sections WHERE slug = 'observation-methods'),
    'Introduction',
    'methods-introduction',
    '<div style="display:flex; gap:2rem; flex-wrap:wrap;">
    <div style="width:380px; flex-shrink:0;">
        <img src="/images/lecture/veo-word-cloud.jpg" alt="EPA Method 9 and Method 22" style="width:100%; border-radius:0.375rem;">
    </div>
    <div style="flex:1; min-width:320px;">
        <h2>Observation Methods Introduction</h2>
        <p>Federal regulations require major sources of air pollutant emissions to obtain operating permits, referred to as <a href="/lecture/resources/glossary#titlev">Title V operating permits</a>.</p>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''om-titlev'', ''om-titlev-icon'')" class="lect-toggle-btn">
                <span>Title V Permits</span>
                <span id="om-titlev-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="om-titlev" class="lect-toggle-body" style="display:none;">
                <p>Title V permits are typically issued at the state level but may be issued at the federal level for tribal lands and other situations. (EPA: <a href="https://www.epa.gov/air-emissions-monitoring-knowledge-base/air-emissions-monitoring-permits" target="_blank" rel="noopener" title="Title V information on the EPA website">Air Emissions Monitoring Permits</a>).</p>
                <p>Title V permits provide monitoring, recordkeeping, and reporting requirements for a facility to comply with air quality standards. Title V requirements are used to create compliance assurance monitoring (CAM) procedures. The CAM processes must produce reliable, accurate, documented, and traceable results.</p>
                <p>Title V permits may specify measurement methods, including Method 9 and Method 22 (Methods). If Methods are not specified in the permit, the facility can utilize <a href="/lecture/resources/glossary#quick">quick checks</a> to remain compliant.</p>
            </div>
        </div>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''om-methods'', ''om-methods-icon'')" class="lect-toggle-btn">
                <span>Observation Methods :: EPA Method 9 and Method 22</span>
                <span id="om-methods-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="om-methods" class="lect-toggle-body" style="display:none;">
                <p>Method 9 and Method 22 are at the core of local, state, and federal air pollution enforcement. Promulgated by the EPA in 1974 and 1982, the Methods are the most actionable tools for enforcing opacity regulations.</p>
                <p>Method 9 defines how to monitor emissions from air pollution sources. Method 22 can also be used to observe emissions but is a qualitative technique that checks only the presence or absence of an emission and how long it occurred.</p>
                <p>Method 22 is most commonly used on unconfined sources for <a href="/lecture/resources/glossary#fugitive">fugitive emissions</a> or for sources where &ldquo;no emission&rdquo; is the stated goal.</p>
            </div>
        </div>
    </div>
</div>',
    1
);
