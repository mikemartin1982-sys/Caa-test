-- ============================================================================
-- Lecture content seed: Introduction section, page 2 (About Smoke Schools)
-- Reference: Michael, 2026-09-07 -- real content capture. Toggle sections
-- ("The EPA and the Importance of Compliance," "Readers Must be Qualified")
-- built as real, collapsible toggles from the start this time -- Michael
-- confirmed this is a standing design preference for the whole course, not
-- a one-off request for the Getting Started page.
--
-- Real, honest link decisions:
--   - glossary.php (three real mentions, all "bold red" in the original)
--     has no equivalent on our platform at all yet -- kept as plain text,
--     link removed, matching the established pattern for dead links.
--   - www.epa.gov/emc/method-9-visual-opacity is a real, live, external
--     EPA resource -- kept as-is.
--   - compliance-assurance.com/certs.php routed to our own, real, working
--     public.certs.lookup instead of linking out externally, matching the
--     same pattern already used on the FAQs page.
-- ============================================================================

INSERT INTO lecture_pages (lecture_section_id, title, slug, content, order_index)
VALUES (
    (SELECT id FROM lecture_sections WHERE slug = 'introduction'),
    'About Smoke Schools',
    'about-smoke-schools',
    '<div style="display:flex; gap:2rem; flex-wrap:wrap;">
    <div style="width:380px; flex-shrink:0;">
        <img src="/images/lecture/about-smoke-school.jpg" alt="Smoke school information" style="width:100%; border-radius:0.375rem;">
        <h4 style="color:#005da0; line-height:1.3;">Having reliable, accurate visible emissions observations protects the environment, identifies operating issues, avoids costly fines, and protects your organization through compliance with air quality requirements.</h4>
    </div>
    <div style="flex:1; min-width:320px;">
        <h2>What is Smoke School?</h2>
        <p>Smoke schools provide visible emissions observations (VEO) training and qualification testing to ensure compliance with the Environmental Protection Agency''s (EPA) clean air regulations for visible emissions. The promulgated EPA test method for opacity is <a href="https://www.epa.gov/emc/method-9-visual-opacity" target="_blank" rel="noopener">Method 9, specifically 40 CFR 60 Appendix A Reference Method 9</a>.</p>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''ass-epa'', ''ass-epa-icon'')" class="lect-toggle-btn">
                <span>The EPA and the Importance of Compliance</span>
                <span id="ass-epa-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="ass-epa" class="lect-toggle-body" style="display:none;">
                <p>EPA laws require facilities that produce (or can potentially produce) emissions perform visible emission observations and submit reports to the EPA periodically. States and local jurisdictions have specified opacity limits based on EPA methods. Smoke school training and qualification enables qualified readers to make visible emission observations to ensure facilities maintain compliance with EPA opacity requirements.</p>
                <p>Visible emissions observations are also referred to as <em>reading the opacity of smoke</em>, <em>opacity observations</em>, or <em>reading smoke</em>.</p>
                <p>Knowledgeable, reliable, and accurate visible emissions observers protect the environment, reduce compliance costs, avoid costly fines, and identify operational issues before serious consequences occur. A knowledgeable visible emissions observer provides an economic benefit to an organization as well as environmental compliance.</p>
            </div>
        </div>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''ass-qualified'', ''ass-qualified-icon'')" class="lect-toggle-btn">
                <span>Readers Must be Qualified (Certified via Method 9)</span>
                <span id="ass-qualified-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="ass-qualified" class="lect-toggle-body" style="display:none;">
                <p>The personnel who performs VEO readings to quantify the opacity level must be certified in accordance with Method 9. Hands-on field qualification is required every 6 months. Candidates are tested on reading 50 plumes of smoke - 25 white, 25 black.</p>
                <p>Visible emissions knowledge training (i.e. lecture training, this course) on visible emission concepts and principles is not required by every state. The federal EPA Method 9 documents recommend an initial lecture class followed by refresher classes every three (3) years. Some states require only initial lecture training, others add the three-year refresher requirement. <em>Regardless of state requirements, lecture training provides an important introduction to opacity and aids in understanding the principles behind visible emissions.</em></p>
                <p>Method 22 does not require certification but does require that a Method 22 observer understand the principles behind opacity and visible emissions. Method 22 observers should complete this course and pay special attention to the water vapor information.</p>
                <p>Compliance Assurance smoke schools and our online training course offer immediate field certification/training records; field certification records and lecture training records are available online 24/7 through our <a href="/certs">certification page</a>.</p>
            </div>
        </div>
    </div>
</div>',
    2
);
