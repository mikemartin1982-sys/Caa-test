-- ============================================================================
-- Lecture content seed: History section, page 2 (Early History)
-- Reference: Michael, 2026-09-28 -- real content capture from the live
-- history-1800s.php. Toggle sections built as real, collapsible toggles.
--
-- The live page's image gallery (three images, prev/next arrows, captions,
-- thumbnail row) uses the new shared .lect-slides component added to the
-- lecture layout on 2026-09-28 (lectureSlide / lectureSlideTo). Captions
-- are the live thumbnails' alt text, which the live gallery shows as its
-- caption.
--
-- Real, honest link decisions:
--   - Images copied into public/images/lecture/ (blacksmith-shop.jpg,
--     Pittsburgh-1857.jpg, Ringelmann-smoke-charts.jpg).
--   - "Ringelmann Smoke Chart" resource PDF copied into public/PDFs/
--     (Ringelmann-Smoke-Chart.pdf), same path as the live site.
--   - No glossary mentions are marked on this page in the live source.
--
-- Live-source bugs fixed: "Ringlemann" misspelled five times ->
-- "Ringelmann"; "no criterion defined" -> "no criteria defined"; stray <br/>
-- at the top of the "Cities Take On Air Pollution" toggle; "New Orleans
-- -1859" spacing. The live span ids used as search anchors (Ringel2,
-- ringel3, ringstd, ringel) are dropped -- nothing on our platform links
-- to them.
-- ============================================================================

INSERT INTO lecture_pages (lecture_section_id, title, slug, content, order_index)
VALUES (
    (SELECT id FROM lecture_sections WHERE slug = 'history'),
    'Early History',
    'history-1800s',
    '<div style="display:flex; gap:2rem; flex-wrap:wrap;">
    <div style="width:380px; flex-shrink:0;">
        <div class="lect-slides" id="h1800-slides">
            <figure class="lect-slide is-active">
                <img src="/images/lecture/blacksmith-shop.jpg" alt="City of New Orleans v. Lambert - Emissions Declared a Public Nuisance">
                <figcaption>City of New Orleans v. Lambert - Emissions Declared a Public Nuisance</figcaption>
            </figure>
            <figure class="lect-slide">
                <img src="/images/lecture/Pittsburgh-1857.jpg" alt="Rise of the Industrial Revolution">
                <figcaption>Rise of the Industrial Revolution</figcaption>
            </figure>
            <figure class="lect-slide">
                <img src="/images/lecture/Ringelmann-smoke-charts.jpg" alt="Ringelmann Opacity Charts">
                <figcaption>Ringelmann Opacity Charts</figcaption>
            </figure>
            <button type="button" class="lect-slide-prev" onclick="lectureSlide(''h1800-slides'', -1)" aria-label="Previous image">&#10094;</button>
            <button type="button" class="lect-slide-next" onclick="lectureSlide(''h1800-slides'', 1)" aria-label="Next image">&#10095;</button>
            <div class="lect-slide-thumbs">
                <img class="is-active" src="/images/lecture/blacksmith-shop.jpg" alt="City of New Orleans v. Lambert" onclick="lectureSlideTo(''h1800-slides'', 0)">
                <img src="/images/lecture/Pittsburgh-1857.jpg" alt="Rise of the Industrial Revolution" onclick="lectureSlideTo(''h1800-slides'', 1)">
                <img src="/images/lecture/Ringelmann-smoke-charts.jpg" alt="Ringelmann Opacity Charts" onclick="lectureSlideTo(''h1800-slides'', 2)">
            </div>
        </div>

        <h4>Resource:</h4>
        <p><a href="/PDFs/Ringelmann-Smoke-Chart.pdf" target="_blank" rel="noopener" title="Ringelmann Smoke Chart">Ringelmann Smoke Chart</a> <span style="font-size:0.85rem; color:#666666;">&nbsp;By Bureau of Mines</span></p>
    </div>
    <div style="flex:1; min-width:320px;">
        <h2>Early History of Visible Emissions Regulation in the United States</h2>

        <h4>Late 1800s and Early 1900s</h4>
        <p>In early industrial history, visible emissions were considered a public nuisance and dealt with at a local level.</p>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''h1800-court'', ''h1800-court-icon'')" class="lect-toggle-btn">
                <span>First Court Case: New Orleans - 1859</span>
                <span id="h1800-court-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="h1800-court" class="lect-toggle-body" style="display:none;">
                <p>The first visible emission law originated in New Orleans in 1859. A blacksmith&rsquo;s forge in the heart of the city started fires every morning that resulted in quite a bit of smoke and odor (a significant public nuisance). When mixed with a heavy fog, common in New Orleans, the result was soot rain in the area.</p>
                <p>A court case (City of New Orleans v. Lambert) resulted in an ordinance declaring the operation a public nuisance and passed an ordinance prohibiting an excessive amount of black smoke between 7:00 AM - 9:00 AM. However, the ordinance did not designate how much smoke was "excessive" - there were no criteria defined. Similar laws were passed in Cincinnati and Chicago in the 1880&rsquo;s. These laws were the beginning of the regulation of visible emissions in the United States.</p>
            </div>
        </div>

        <h4>1897 - Ringelmann Scale Comes to the U.S.</h4>
        <p>French professor Ringelmann develops system for determining smoke density (percent blackness).</p>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''h1800-ringelmann'', ''h1800-ringelmann-icon'')" class="lect-toggle-btn">
                <span>The Ringelmann Scale</span>
                <span id="h1800-ringelmann-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="h1800-ringelmann" class="lect-toggle-body" style="display:none;">
                <p>In the 1870&rsquo;s, Maximilien Ringelmann, a professor of agricultural engineering at the University of Paris, was working on tuning coal-fired boilers to optimize combustion. He created the Ringelmann Scale by developing a series of cards with black grids on white cards such that the ink covered a specific percentage of each card. Each card was given a Ringelmann number. The Ringelmann scale defines opacity in four levels that correlate to Ringelmann grid cards:</p>
                <ul>
                    <li>Ringelmann 1 card: 20%</li>
                    <li>Ringelmann 2 card: 40%</li>
                    <li>Ringelmann 3 card: 60%</li>
                    <li>Ringelmann 4 card: 80%</li>
                    <li>Ringelmann 5 card: 100% (all black, not shown)</li>
                </ul>
                <img src="/images/lecture/Ringelmann-smoke-charts.jpg" alt="Ringelmann smoke charts" style="width:100%; border-radius:0.375rem; margin-bottom:1rem;">
                <p>Before the Ringelmann Scale was used, there was no method to measure the opacity of smoke plumes, i.e., the level of the nuisance to the public. It was difficult to enforce laws that used "excessive" as a standard. When the Ringelmann Scale was introduced in the U.S. in 1897, the demand, need, and political will facilitated a rapid adoption of the method of measurement. By 1912 more than 80% of cities with a population greater than 200,000 had a specific Ringelmann number standard to control local emission levels.</p>
                <p>During this time, the federal government published a booklet, "How To Tune Your Coal-Fired Burner," which included a Ringelmann chart. The booklet encouraged industrial organizations using coal to evaluate operations and helped them comply with local ordinances. The use of the Ringelmann Scale resulted in reduced coal use, thereby improving economic results and also created positive environmental results.</p>
                <p>The Ringelmann system&rsquo;s limitation is that it applies to only black smoke; it is a percent-based blackness scale. Attempts were made to create colored charts for other emission colors, but exact shades of emissions could seldom be matched.</p>
            </div>
        </div>

        <h4>Early 1900s - Increasing Response to Emissions</h4>
        <p>Ringelmann Scale widely adopted with widespread implementation.</p>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''h1800-cities'', ''h1800-cities-icon'')" class="lect-toggle-btn">
                <span>Cities Take On Air Pollution</span>
                <span id="h1800-cities-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="h1800-cities" class="lect-toggle-body" style="display:none;">
                <p>The U.S. Bureau of Mines was formed in 1910. It adopted and promoted the Ringelmann Scale.</p>
                <p>In the early 1900s, many cities adopted the Ringelmann method to control emissions. By 1912, 80% of cities with populations greater than 200,000 had health and safety regulations on the books referring to the Ringelmann Scale.</p>
                <p>The city of Rochester, NY, upholds the Ringelmann system and creates smoke ordinances.</p>
                <p>The Northwestern Laundry v. Des Moines case in 1916 was an important case because it upheld the right of the city to declare dense smoke a public nuisance.</p>
                <p>Enforcement continued at the local level.</p>
            </div>
        </div>
    </div>
</div>',
    2
);
