-- ============================================================================
-- Lecture content seed: Legal Issues section, page 5 (State Implementation
-- Plans)
-- Reference: Michael, 2026-09-28 -- real content capture from the live
-- legal-sips.php. Last page of the Legal Issues section; the section quiz
-- follows. No toggles on this page; the left column keeps the image, the
-- EPA resource links, and the Texas (TCEQ) note, as on the live page.
--
-- Real, honest link decisions:
--   - Image copied into public/images/lecture/state-implementation-plans.jpg.
--   - EPA SIP / Title V pages and the TCEQ SIP page all checked 200 on
--     2026-09-28 -- kept. eCFR (40 CFR parts 70 and 71) blocks scripted
--     checks but the URLs are the standard eCFR paths -- kept.
--   - "National Ambient Air Quality Standards" was a bold-red glossary.php
--     link -> /lecture/resources/glossary#naaqs.
--
-- Live-source bugs fixed: the "40 CFR part 71" link pointed at part 70 --
-- now points at part 71; "NAAQs" -> "NAAQS"; the resource links were bullet
-- characters in separate <p>s (one with a broken "</p" tag) -- now a real
-- <ul>; unclosed <p> before the Site Operating Permits heading.
--
-- Content left as-is but flagged to Michael for CAA review: the pollutant
-- list says "Nitrogen oxide pollution"; the NAAQS criteria pollutant is
-- nitrogen dioxide (NO2).
-- ============================================================================

INSERT INTO lecture_pages (lecture_section_id, title, slug, content, order_index)
VALUES (
    (SELECT id FROM lecture_sections WHERE slug = 'legal-issues'),
    'State Implementation Plans',
    'legal-sips',
    '<div style="display:flex; gap:2rem; flex-wrap:wrap;">
    <div style="width:380px; flex-shrink:0;">
        <img src="/images/lecture/state-implementation-plans.jpg" alt="State implementation plans air pollution control." style="width:100%; border-radius:0.375rem;">
        <h4>Resources:</h4>
        <ul>
            <li><a href="https://www.epa.gov/air-quality-implementation-plans/basic-information-about-air-quality-sips" target="_blank" rel="noopener" title="EPA webpage on SIPs">EPA&rsquo;s web page on SIPs.</a></li>
            <li><a href="https://www.epa.gov/title-v-operating-permits/basic-information-about-operating-permits" target="_blank" rel="noopener" title="EPA webpage on operating permits">EPA&rsquo;s web page on site operating permits.</a></li>
            <li><a href="https://www.epa.gov/title-v-operating-permits/who-has-obtain-title-v-permit" target="_blank" rel="noopener" title="EPA webpage on who needs to get a permit">Who is required to obtain a Title V permit?</a></li>
        </ul>
        <p style="font-size:0.95em;">It is highly recommended that <span style="color:#b82027;">Texas students</span> review the following page on the Texas Commission on Environmental Quality&rsquo;s (TCEQ) website. It explains the elements of an SIP and how revisions are made:</p>
        <ul>
            <li><a href="https://www.tceq.texas.gov/airquality/sip/sipintro.html" target="_blank" rel="noopener" title="TCEQ webpage on SIPs">TCEQ web page on SIP for Texas students.</a></li>
        </ul>
    </div>
    <div style="flex:1; min-width:320px;">
        <h2>State Implementation Plans</h2>
        <p>State Implementation Plans (SIPs) are a collection of regulations and documents that implement, maintain, and enforce the <a href="/lecture/resources/glossary#naaqs">National Ambient Air Quality Standards (NAAQS)</a> and other requirements of the Clean Air Act. State, territory, or local air districts implement SIPs.</p>
        <p>SIPs must meet national standards for the following pollutants that are harmful to human health:</p>
        <ul>
            <li>Carbon monoxide pollution</li>
            <li>Lead air pollution</li>
            <li>Nitrogen oxide pollution</li>
            <li>Ozone pollution</li>
            <li>Particulate matter pollution</li>
            <li>Sulfur dioxide pollution</li>
        </ul>
        <p>The EPA reviews and approves all SIPs to ensure compliance with the Clean Air Act. SIPs are typically enforced at the state level, but the EPA is authorized to enforce the SIP requirements that the EPA approved. The public can file citizen suits under the Clean Air Act to address violations of SIPs.</p>

        <h2>Site Operating Permits</h2>
        <p>Site operating permits (SOPs) are issued under Title V of the Clean Air Act. SOPs, also known as Title V permits, are legal documents to ensure compliance with clean air regulations. SOPs are typically issued by state or local agencies and govern all large (aka major) and a limited number of smaller (aka area, minor, or non-major) sources. State-issued permits are called part 70 permits because they are found in the <a href="https://www.ecfr.gov/current/title-40/chapter-I/subchapter-C/part-70" target="_blank" rel="noopener" title="Federal regulations Part 70">Code of Federal Regulations 40 CFR part 70</a>.</p>
        <p>The EPA issues Title V permits in Indian and tribal lands and other situations. EPA-issued operating permits are called part 71 because they are found in the <a href="https://www.ecfr.gov/current/title-40/chapter-I/subchapter-C/part-71" target="_blank" rel="noopener" title="Federal regulations Part 71">Code of Federal Regulations at 40 CFR part 71</a>.</p>
    </div>
</div>',
    5
);
