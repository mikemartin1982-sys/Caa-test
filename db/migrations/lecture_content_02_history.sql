-- ============================================================================
-- Lecture content seed: History section, page 1 (Introduction)
-- Reference: Michael, 2026-09-28 -- real content capture from the live
-- history-introduction.php. The four timeline segments are built as real,
-- collapsible toggles (standing course preference).
--
-- Real, honest link decisions:
--   - The page image links to the live course's "History of Visible
--     Emissions" PDF; both files copied into public/images/lecture/
--     (history-of-visible-emissions.jpg / .pdf).
--   - The Donora, PA link (worldsciencefestival.com) is an external link on
--     the live page -- kept as-is.
--   - No glossary mentions are marked on this page in the live source.
--
-- Live-source bugs fixed: unclosed/stray <p> tags around the intro sentence
-- and the first toggle's paragraphs; "occured" -> "occurred".
-- ============================================================================

INSERT INTO lecture_pages (lecture_section_id, title, slug, content, order_index)
VALUES (
    (SELECT id FROM lecture_sections WHERE slug = 'history'),
    'Introduction',
    'history-introduction',
    '<div style="display:flex; gap:2rem; flex-wrap:wrap;">
    <div style="width:380px; flex-shrink:0;">
        <a href="/images/lecture/history-of-visible-emissions.pdf" target="_blank" rel="noopener">
            <img src="/images/lecture/history-of-visible-emissions.jpg" alt="History of visible emissions" style="width:100%; border-radius:0.375rem;">
        </a>
    </div>
    <div style="flex:1; min-width:320px;">
        <h2>Introduction/Summary</h2>
        <p>The history of visible emissions regulations in the United States is presented in the following segments.</p>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''hi-1800s'', ''hi-1800s-icon'')" class="lect-toggle-btn">
                <span>Late 1800s and Early 1900s</span>
                <span id="hi-1800s-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="hi-1800s" class="lect-toggle-body" style="display:none;">
                <p>The first U.S. visible emissions law was passed in New Orleans (<em>City of New Orleans v. Lambert</em>) as a nuisance ordinance.</p>
                <p>As the Industrial Revolution gained momentum, American cities became increasingly polluted and emissions were treated as a public nuisance. Smoke abatement leagues were formed to lobby public authorities to provide relief.</p>
                <p>The Ringelmann Smoke Scale was introduced in the U.S. in 1897. Opposition to smoke grew and local ordinances increased. By 1912, the majority of large cities had smoke ordinances on the books that referred to the Ringelmann scale.</p>
                <p>During World War I, the Great Depression, and World War II, air pollution took a back seat to war manufacturing and economic survival.</p>
            </div>
        </div>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''hi-mid1900s'', ''hi-mid1900s-icon'')" class="lect-toggle-btn">
                <span>Mid 1900s: 1940s and 1950s</span>
                <span id="hi-mid1900s-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="hi-mid1900s" class="lect-toggle-body" style="display:none;">
                <p>After World War II, air pollution garnered increased attention. Los Angeles air was notably dirty, and an event in <a href="https://www.worldsciencefestival.com/unnamed-14/" title="Donora, PA smog event" target="_blank" rel="noopener">Donora, PA</a>, caused deaths and widespread illness, resulting in a national concern and a congressional investigation. Air quality continued to worsen in the U.S. In 1948, the U.S. Surgeon General declared that air pollution was a health hazard, not just a public nuisance.</p>
                <p>California passes CA Rule 50-A to limit emissions, and other states adopt similar measures. The first federal action occurred in 1955 - the Federal Air Pollution Control Act of 1955.</p>
            </div>
        </div>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''hi-1960s'', ''hi-1960s-icon'')" class="lect-toggle-btn">
                <span>1960s - 1970s</span>
                <span id="hi-1960s-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="hi-1960s" class="lect-toggle-body" style="display:none;">
                <p>The initial Clean Air Act is passed, the EPA is formed, the Clean Air Act is updated, and EPA Method 9 is promulgated.</p>
            </div>
        </div>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''hi-1980s'', ''hi-1980s-icon'')" class="lect-toggle-btn">
                <span>1980s to Present</span>
                <span id="hi-1980s-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="hi-1980s" class="lect-toggle-body" style="display:none;">
                <p>Method 22 is promulgated. Title V permitting requirements are added to the Clean Air Act. State implementation plans are instituted, as well as new standards for air quality.</p>
            </div>
        </div>
    </div>
</div>',
    1
);
