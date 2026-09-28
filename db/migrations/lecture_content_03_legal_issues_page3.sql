-- ============================================================================
-- Lecture content seed: Legal Issues section, page 3 (Legal Compliance)
-- Reference: Michael, 2026-09-28 -- real content capture from the live
-- legal-compliance.php. The two toggle sections ("How Compliance is
-- Enforced", "Opacity Reader Liability") are real, collapsible toggles; the
-- left column keeps the image plus the "From the EPA" quote, as on the live
-- page.
--
-- Real, honest link decisions:
--   - Image copied into public/images/lecture/legal-compliance.jpg.
--   - "Notice of violation (NOV)" was a bold-red glossary.php link -> our
--     Glossary resource, /lecture/resources/glossary#nov.
--   - EPA Visible Emissions Field Manual PDF and EPA enforcement page are
--     real external links (both checked 200 on 2026-09-28) -- kept.
--
-- Live-source bugs fixed: "incompliance" -> "noncompliance"; stray <hr>
-- dividers after the toggles dropped. Live span ids used as search anchors
-- (why, notice, liability, falsedoc) are dropped.
--
-- Content left as-is but flagged to Michael for CAA review: "fines of up to
-- $37,500 per source, per standard, per day" -- the Clean Air Act civil
-- penalty maximum is inflation-adjusted each year and is now well above
-- that figure.
-- ============================================================================

INSERT INTO lecture_pages (lecture_section_id, title, slug, content, order_index)
VALUES (
    (SELECT id FROM lecture_sections WHERE slug = 'legal-issues'),
    'Legal Compliance',
    'legal-compliance',
    '<div style="display:flex; gap:2rem; flex-wrap:wrap;">
    <div style="width:380px; flex-shrink:0;">
        <img src="/images/lecture/legal-compliance.jpg" alt="The role of law in visible emissions observations" style="width:100%; border-radius:0.375rem;">
        <h3>From the EPA</h3>
        <p><em>...visible emission determinations for compliance demonstration or enforcement purposes must be made accurately and must be sufficiently well documented to withstand rigorous examination in potential enforcement proceedings, administrative or legal hearings, or eventual court litigation.</em><br>
        <span style="font-size:0.75em;">Source: <a href="https://www3.epa.gov/ttnemc01/methods/VEFieldManual.pdf" target="_blank" rel="noopener" title="EPA Method 9 and 22 manual">Visible Emissions Field Manual - EPA Methods 9 and 22</a> Page 2</span></p>
    </div>
    <div style="flex:1; min-width:320px;">
        <h2>The Importance of Compliance</h2>
        <p>As a visible emissions observer, it is essential to realize that your observations must comply with air pollution laws and regulations. Your organization can face huge fines if it is not compliant with environmental laws or EPA Method 9. To protect your organization, your opacity readings must be legally defensible, i.e., accurate and thoroughly documented.</p>
        <p>There are several legal requirements related to opacity readings:</p>
        <ul>
            <li>Method 9 certifications (field and lecture) must be kept current.</li>
            <li>Visible emissions observations must be performed correctly and accurately.</li>
            <li>The VEO form must be completed correctly, including recording conditions during the reading.</li>
            <li>VEO records must be maintained and available for audits.</li>
        </ul>
        <p>Procedural errors or omissions on the VEO evaluation form or data sheet can invalidate your reading and result in a violation. It is important to carefully follow Method 9 procedures and adequately complete the VEO form.</p>
        <p>It is important to note that violations of opacity limits, failure to follow proper procedures, or failure to fill out a VEO form accurately can result in fines of up to $37,500 per source, per standard, per day.</p>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''lc-enforced'', ''lc-enforced-icon'')" class="lect-toggle-btn">
                <span>How Compliance is Enforced</span>
                <span id="lc-enforced-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="lc-enforced" class="lect-toggle-body" style="display:none;">
                <p>The EPA&rsquo;s regional offices are responsible for compliance operations. Federal opacity standards and SIP regulations are independently enforceable (per source, per violation, per day). Enforcement mechanisms include:</p>
                <ul>
                    <li><a href="/lecture/resources/glossary#nov">Notice of violation (NOV)</a> - this is the most common method of enforcement. It informs the organization that it has violated a district rule, state law, or a permit condition. NOVs may result in monetary penalties.</li>
                    <li>An administrative order may be associated with penalties.</li>
                    <li>Civil judicial actions - formal lawsuits that arise when organizations fail to comply with NOVs, administrative orders, or pay fines.</li>
                    <li>Criminal actions - for serious violations that are willful or knowingly committed.</li>
                </ul>
            </div>
        </div>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''lc-liability'', ''lc-liability-icon'')" class="lect-toggle-btn">
                <span>Opacity Reader Liability</span>
                <span id="lc-liability-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="lc-liability" class="lect-toggle-body" style="display:none;">
                <p>As stated above, your organization&rsquo;s liability for noncompliance with federal and local regulations can be very costly. As a visible emissions observer, what are your liabilities?</p>
                <ul>
                    <li>Individuals who perform opacity readings are not criminally charged for incorrect/invalid readings. NOV charges are pursued with the facility/organization in civil court.</li>
                    <li>You can be charged with fraud if you falsely document a visible emission reading when one has not been completed.</li>
                </ul>
                <p>Observers are not legally liable if they do not willingly violate Method 9 standards or fraudulently document readings. However, as an employee, you are representing your organization and are responsible for completing VEO forms, which are legal documents.</p>
            </div>
        </div>

        <p>More information about EPA enforcement can be found on the <a href="https://www.epa.gov/enforcement/basic-information-enforcement" target="_blank" rel="noopener" title="EPA enforcement methods">EPA website</a>.</p>
    </div>
</div>',
    3
);
