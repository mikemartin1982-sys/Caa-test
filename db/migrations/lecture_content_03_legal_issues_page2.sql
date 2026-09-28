-- ============================================================================
-- Lecture content seed: Legal Issues section, page 2 (Historic Legal Cases)
-- Reference: Michael, 2026-09-28 -- real content capture from the live
-- legal-history.php. The seven court cases are built as real, collapsible
-- toggles; the <hr> dividers between them are dropped (the toggles have
-- their own spacing).
--
-- Real, honest link decisions:
--   - Image copied into public/images/lecture/veo-court-cases.jpg.
--   - "Ringelmann method" linked to history-1800s.php (History :: Early
--     History). Our lecture pages are addressed by numeric id, which
--     differs between the PC and server databases, so a hard-coded link
--     isn't possible yet -- kept as plain text, matching the established
--     pattern for links we can't honor. If cross-page links become common
--     in later sections, add a slug-based page route and relink these via
--     a _relink file, like the glossary relinks.
--
-- Live-source bugs fixed: an <i> opened in one paragraph and closed in the
-- next (Crump v. Lambert quote) -- each quoted paragraph now has its own
-- <em>; malformed HTML comments ("<! ----") that leaked into the markup;
-- stray <br> at the top of four toggles. "affrmed (sic)" is a quotation
-- and kept exactly.
--
-- Content left as-is but flagged to Michael for CAA review: "By 1912, 23 of
-- the 28 U.S. cities with populations over 20,000" -- History :: Early
-- History says 80% of cities over 200,000, so "20,000" may be a typo.
-- ============================================================================

INSERT INTO lecture_pages (lecture_section_id, title, slug, content, order_index)
VALUES (
    (SELECT id FROM lecture_sections WHERE slug = 'legal-issues'),
    'Historic Legal Cases',
    'legal-history',
    '<div style="display:flex; gap:2rem; flex-wrap:wrap;">
    <div style="width:380px; flex-shrink:0;">
        <img src="/images/lecture/veo-court-cases.jpg" alt="Visible emissions court cases" style="width:100%; border-radius:0.375rem;">
    </div>
    <div style="flex:1; min-width:320px;">
        <h2>Early U.S. Court Cases</h2>
        <p>The following is a list of court cases that shaped today&rsquo;s visible emissions standards.</p>

        <h4>Early History of Emissions Court Cases</h4>
        <p>The Industrial Revolution was in full swing in the late 1800s and early 1900s. Factories became prevalent in cities, and air pollution began affecting cities.</p>
        <p>Initial court cases regarded emissions as a public nuisance, but there was no quantification of how much smoke was too much. By the second decade of the twentieth century, many cities adopted the Ringelmann method to measure the density of smoke.</p>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''lh-1859'', ''lh-1859-icon'')" class="lect-toggle-btn">
                <span>1859 - City of New Orleans v. Lambert</span>
                <span id="lh-1859-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="lh-1859" class="lect-toggle-body" style="display:none;">
                <p>The first legal case on record that resulted in a local smoke ordinance. A blacksmith emitted excessive smoke and fumes every morning from 7:00 AM - 9:00 AM. The court deemed the smoke and fumes as a public nuisance.</p>
            </div>
        </div>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''lh-1867'', ''lh-1867-icon'')" class="lect-toggle-btn">
                <span>1867 - Crump v. Lambert (United Kingdom)</span>
                <span id="lh-1867-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="lh-1867" class="lect-toggle-body" style="display:none;">
                <p>Note: This is a different Lambert from the previous case, a court case in England. This case was noteworthy because Lord Romilly raised the following question:</p>
                <p><em>The question we have to deal with is not as to the authority to regulate the emission of dense smoke in a sparsely inhabited locality, where in the act could only result in the creation of a private nuisance, but of the right to prevent the emission of dense black or gray smoke (for so we construe the ordinance) within the corporate limits of a populous city, wherein, if there be no regulations upon the subject, the smoke from scores of steam plants must, in the nature of things, often cover the city as with a pall, thereby impairing the health and comfort of thousands, and casting grime upon every exposed object.</em></p>
                <p><em>If there is anything in the principle of the greatest good to the greatest number, or in the declared authority of government reasonably to regulate the use of property for the common good, it must be affrmed (sic) that power exists to deal with a condition which renders life in a great manufacturing city little short of impossible.</em></p>
            </div>
        </div>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''lh-1881'', ''lh-1881-icon'')" class="lect-toggle-btn">
                <span>1881 - Chicago and Cincinnati</span>
                <span id="lh-1881-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="lh-1881" class="lect-toggle-body" style="display:none;">
                <p>Chicago and Cincinnati were the first major U.S. cities to adopt smoke control ordinances in 1881. By 1912, 23 of the 28 U.S. cities with populations over 20,000 adopted similar ordinances.</p>
            </div>
        </div>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''lh-1904'', ''lh-1904-icon'')" class="lect-toggle-btn">
                <span>1904 - State v. Tower - Missouri</span>
                <span id="lh-1904-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="lh-1904" class="lect-toggle-body" style="display:none;">
                <p>This court case upheld that dense smoke was a public nuisance as it "produced a tangible injury to property as by the discoloration of buildings, injury to vegetation, the discoloration of furniture..."</p>
                <p><em>We have no hesitancy in holding that it was entirely competent for the Legislature to declare the emission of dense smoke in the open air in a city of 100,000 habitants a nuisance.</em></p>
            </div>
        </div>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''lh-1905'', ''lh-1905-icon'')" class="lect-toggle-btn">
                <span>1905 - Glucose Refining Company v. City of Chicago</span>
                <span id="lh-1905-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="lh-1905" class="lect-toggle-body" style="display:none;">
                <p>This court case declared that dense smoke in urban areas was a public nuisance, and it mentioned health in its argument:</p>
                <p><em>...that smoke emitted from a tall chimney is carried over a wide territory, and that when dense, it deposits soot to such an extent as to injure property and health wherever it spreads.</em></p>
            </div>
        </div>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''lh-1916'', ''lh-1916-icon'')" class="lect-toggle-btn">
                <span>1916 - Northwestern Laundry v. City of Des Moines</span>
                <span id="lh-1916-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="lh-1916" class="lect-toggle-body" style="display:none;">
                <p>The 1916 case was vital in shaping U.S. pollution laws because it upheld the authority of states to have ordinances that prohibit the emission of dense smoke and to have ordinances that specify a scientific method (i.e., Ringelmann) to quantify the density of the emissions.</p>
            </div>
        </div>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''lh-1949'', ''lh-1949-icon'')" class="lect-toggle-btn">
                <span>1949 - Penn Dixie Cement Corp. v. City of Kingsport, Tennessee</span>
                <span id="lh-1949-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="lh-1949" class="lect-toggle-body" style="display:none;">
                <p>This case was pivotal because it established that public health is the government&rsquo;s responsibility. It confirmed that dense smoke WAS a public nuisance and did not have to be proven so. Earlier court considerations required that the community prove that the smoke was dense, a nuisance, and affected a substantial number of people.</p>
                <p>Note: This case occurred the year after the Donora tragedy and was the same year the Surgeon General declared that air pollution was a health risk.</p>
            </div>
        </div>
    </div>
</div>',
    2
);
