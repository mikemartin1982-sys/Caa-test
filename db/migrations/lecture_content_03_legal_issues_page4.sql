-- ============================================================================
-- Lecture content seed: Legal Issues section, page 4 (New Source
-- Performance Standards)
-- Reference: Michael, 2026-09-28 -- real content capture from the live
-- legal-nsps.php. No toggles on this page; the left column keeps the image,
-- the EPA quote, and the two NSR resource links, as on the live page.
--
-- Real, honest link decisions:
--   - Image copied into public/images/lecture/epa-office.jpg.
--   - External EPA / govinfo.gov links (NSPS page, NSR fact sheet PDF, NSR
--     page, 40 CFR Part 60) all checked 200 on 2026-09-28 -- kept.
--   - "National Ambient Air Quality Standards (NAAQS)" was a bold-red
--     glossary.php link -> /lecture/resources/glossary#naaqs.
--   - "State Implementation Plan (SIP)" linked to legal-sips.php (the next
--     lecture page). Kept as plain text -- lecture pages are addressed by
--     numeric id, so a stable page-to-page link isn't possible yet (same
--     decision as legal-history's Ringelmann link).
--
-- Live-source bugs fixed: the two resource links were bullet characters in
-- separate <p>s with an unclosed <p> -- now a real <ul>; an unclosed <p>
-- before the NSR heading and a stray extra </p> after it; the negative
-- top margins on the two sub-headings (they overlapped the <h2> in our
-- layout) are dropped.
--
-- Content left as-is but flagged to Michael for CAA review: "NSPS are
-- contained in Section 110 of the Clean Air Act" -- NSPS are CAA Section
-- 111; Section 110 covers State Implementation Plans.
-- ============================================================================

INSERT INTO lecture_pages (lecture_section_id, title, slug, content, order_index)
VALUES (
    (SELECT id FROM lecture_sections WHERE slug = 'legal-issues'),
    'New Source Performance Standards',
    'legal-nsps',
    '<div style="display:flex; gap:2rem; flex-wrap:wrap;">
    <div style="width:380px; flex-shrink:0;">
        <img src="/images/lecture/epa-office.jpg" alt="The Environmental Protection Agency" style="width:100%; border-radius:0.375rem;">
        <h3>The EPA</h3>
        <p><em>New Source Performance Standards (NSPS) are developed and controlled by the EPA as part of the Clean Air Act.</em><br>
        <span style="font-size:0.75em;">EPA webpage: <a href="https://www.epa.gov/compliance/demonstrating-compliance-new-source-performance-standards-and-state-implementation-plans" target="_blank" rel="noopener" title="EPA New Source Performance Standards">EPA NSPS Page</a></span></p>
        <h4>Resources:</h4>
        <ul>
            <li><a href="https://www.epa.gov/sites/default/files/2015-12/documents/nsrbasicsfactsheet103106.pdf" target="_blank" rel="noopener" title="EPA fact sheet on NSRs">EPA&rsquo;s fact sheet on NSR permits.</a></li>
            <li><a href="https://www.epa.gov/nsr" target="_blank" rel="noopener" title="EPA webpage on NSRs">EPA web page on NSR permits.</a></li>
        </ul>
    </div>
    <div style="flex:1; min-width:320px;">
        <h2>New Source Performance Standards</h2>
        <h4>Federal Regulations</h4>
        <p>New Source Performance Standards originated as part of the 1970 Clean Air Act Extension amendments. The NSPS defines the level of pollution that stationary sources can emit. NSPS were developed to focus on new operations or modifications at existing facilities. The federal standards apply to replacing air pollution control devices and when a facility spends more than 50% of the initial investment on the source to improve or extend its operational life. The NSPS have emission controls on new and existing sources significantly contributing to air pollution.</p>
        <p>NSPS are contained in Section 110 of the Clean Air Act, <a href="https://www.govinfo.gov/content/pkg/CFR-2011-title40-vol6/xml/CFR-2011-title40-vol6-part60.xml" target="_blank" rel="noopener" title="New Source Performance Standards">40 CFR Part 60</a>. There are over 90 NSPS; the standards are tailored to the type of emission source, e.g., lead-acid battery manufacturing plants, lime manufacturing plants, sewage treatment plants, petroleum refineries, and many more.</p>
        <p>NSPS duties are delegated to individual states; however, the EPA is the final authority to implement and enforce the NSPS.</p>

        <h2>New Source Review Permits</h2>
        <h4>State Regulations</h4>
        <p>New Source Review (NSR) permits are legal documents that define air quality requirements that facility owners and operators must follow. NSR originated as part of the 1977 Clean Air Act Extension amendments. NSR permits are required when building new facilities and when existing facilities are upgrading or adding to their emission sources.</p>
        <p>The NSR permits specify what type of construction is allowed, emission limits, and the required frequency of operation. NSR permits must comply with the <a href="/lecture/resources/glossary#naaqs">National Ambient Air Quality Standards (NAAQS)</a> and require approval by the federal EPA. States may develop unique NSR requirements and procedures tailored to their air quality needs if the program is at least as stringent as EPA&rsquo;s requirements. EPA must approve these programs in the State Implementation Plan (SIP).</p>
    </div>
</div>',
    4
);
