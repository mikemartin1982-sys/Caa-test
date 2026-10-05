-- ============================================================================
-- Lecture content seed: Observation Methods section, page 3 (Method 22)
-- Reference: Michael, 2026-10-05 -- real content capture from the live
-- methods-method22.php. "Method 22 Basics" is a real, collapsible toggle;
-- the left column keeps the image and the EPA Method 22 document link.
--
-- Real, honest link decisions:
--   - Image copied into public/images/lecture/Baghouse-dust-collector.jpg.
--   - EPA Method 22 PDF (epa.gov, 2017-08) checked 200 on 2026-10-05 -- kept.
--   - Bold-red glossary.php links -> our Glossary resource: "fugitive
--     emissions" -> #fugitive, "baghouse equipment" -> #baghouse.
--
-- Live-source bugs fixed: "45 seconds in the 360 interval" -> "in the
-- 360-second interval" (missing unit); a double space; stray <hr> after the
-- toggle dropped. Live span ids used as search anchors (meth22about, meth22,
-- meth22opacity) dropped.
-- ============================================================================

INSERT INTO lecture_pages (lecture_section_id, title, slug, content, order_index)
VALUES (
    (SELECT id FROM lecture_sections WHERE slug = 'observation-methods'),
    'Method 22',
    'methods-method22',
    '<div style="display:flex; gap:2rem; flex-wrap:wrap;">
    <div style="width:380px; flex-shrink:0;">
        <img src="/images/lecture/Baghouse-dust-collector.jpg" alt="Baghouse filter system" style="width:100%; border-radius:0.375rem;">
        <p><a href="https://www.epa.gov/sites/default/files/2017-08/documents/method_22.pdf" target="_blank" rel="noopener" title="EPA Method 22">EPA Method 22 Document &raquo;</a></p>
    </div>
    <div style="flex:1; min-width:320px;">
        <h2>EPA Method 22</h2>
        <h3>Are Visible Emissions Present? Yes or No</h3>
        <p>The EPA defines Method 22 as <em>a simple procedure that uses the human eye to determine the total time an industrial activity causes visible emissions.</em> Method 22 observations look for the presence or absence of an emission and report the duration of an emission if present.</p>
        <p>Method 22 is used to detect <a href="/lecture/resources/glossary#fugitive">fugitive emissions</a>, usually caused by equipment failures, but can also be from activities such as grinding, startups, shutdowns, transfer of metals, or driving vehicles on a dusty road. It is typically used with emission standards where <em>no visible emissions</em> are the stated goal.</p>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''m22-basics'', ''m22-basics-icon'')" class="lect-toggle-btn">
                <span>Method 22 Basics</span>
                <span id="m22-basics-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="m22-basics" class="lect-toggle-body" style="display:none;">
                <p>Method 22 differs from Method 9 in that it is a qualitative method - either the visible emissions exist, or they do not. Method 22 observations are continuous over a specified period, unlike Method 9, where periodic observations are made by &ldquo;quick momentary glances.&rdquo; If an air permit does not specify a time, the Method requires a minimum of six (6) minutes or the time set in your compliance monitoring plan.</p>
                <p>Method 22 does not quantify the emission level in a percentage like Method 9; Method 22 provides a time of emission present during the total time observed, i.e., 45 seconds in the 360-second interval is documented as 45/360.</p>
                <p>Method 22 requires two (2) stopwatches &ndash; one that measures the time observed and the other that you activate when you see the emission and deactivate when the emission stops.</p>
                <p>The Method 22 form requires the observer to record each emission event&rsquo;s duration. Using the emission time, facility staff can pinpoint the process event that caused the emission and correct the problem.</p>
            </div>
        </div>

        <p>Unlike Method 9, Method 22 observers do not require official certification. However, the Method 22 observer should understand the underlying principles of opacity and complete a course, such as this one, to understand the science behind visible emissions.</p>
        <p>One use of Method 22 observations is to detect problems with <a href="/lecture/resources/glossary#baghouse">baghouse equipment</a>.</p>
    </div>
</div>',
    3
);
