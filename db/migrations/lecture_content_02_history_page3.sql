-- ============================================================================
-- Lecture content seed: History section, page 3 (Early-Mid 1900s)
-- Reference: Michael, 2026-09-28 -- real content capture from the live
-- history-mid-1900s.php. Toggle sections built as real, collapsible toggles;
-- the five-image gallery uses the shared .lect-slides component (captions =
-- the live thumbnails' alt text).
--
-- Real, honest link decisions:
--   - Images copied into public/images/lecture/ (World-war-1-workers.jpg,
--     Donora-1948.jpg, london-great-smog-1952.jpg, LA_smog_masks.jpg,
--     first-smoke-school.jpg).
--   - "New Source Performance Standards (NSPS)" was a bold-red glossary.php
--     link -> our Glossary resource, /lecture/resources/glossary#nsps.
--   - "The Donora smog" (worldsciencefestival.com) external link kept as-is,
--     same as on History :: Introduction.
--
-- Live-source bugs fixed: a stray "jpg" text node between two gallery
-- slides (rendered as visible text on the live page); unclosed <p> after
-- "(APCD)." and in the Great Smog of London paragraph; stray <br> at the top
-- of two toggles; missing period after "(CAAa)". The live span id "hist"
-- (search anchor) is dropped -- nothing on our platform links to it.
-- ============================================================================

INSERT INTO lecture_pages (lecture_section_id, title, slug, content, order_index)
VALUES (
    (SELECT id FROM lecture_sections WHERE slug = 'history'),
    'Early-Mid 1900s',
    'history-mid-1900s',
    '<div style="display:flex; gap:2rem; flex-wrap:wrap;">
    <div style="width:380px; flex-shrink:0;">
        <div class="lect-slides" id="hmid-slides">
            <figure class="lect-slide is-active">
                <img src="/images/lecture/World-war-1-workers.jpg" alt="World War I War workers">
                <figcaption>World War I War workers</figcaption>
            </figure>
            <figure class="lect-slide">
                <img src="/images/lecture/Donora-1948.jpg" alt="Donora PA Smog Event">
                <figcaption>Donora PA Smog Event</figcaption>
            </figure>
            <figure class="lect-slide">
                <img src="/images/lecture/london-great-smog-1952.jpg" alt="London Great Smog">
                <figcaption>London Great Smog</figcaption>
            </figure>
            <figure class="lect-slide">
                <img src="/images/lecture/LA_smog_masks.jpg" alt="Gas Masks at LA Gathering">
                <figcaption>Gas Masks at LA Gathering</figcaption>
            </figure>
            <figure class="lect-slide">
                <img src="/images/lecture/first-smoke-school.jpg" alt="First Smoke School is Held in Los Angeles">
                <figcaption>First Smoke School is Held in Los Angeles</figcaption>
            </figure>
            <button type="button" class="lect-slide-prev" onclick="lectureSlide(''hmid-slides'', -1)" aria-label="Previous image">&#10094;</button>
            <button type="button" class="lect-slide-next" onclick="lectureSlide(''hmid-slides'', 1)" aria-label="Next image">&#10095;</button>
            <div class="lect-slide-thumbs">
                <img class="is-active" src="/images/lecture/World-war-1-workers.jpg" alt="World War I War workers" onclick="lectureSlideTo(''hmid-slides'', 0)">
                <img src="/images/lecture/Donora-1948.jpg" alt="Donora PA Smog Event" onclick="lectureSlideTo(''hmid-slides'', 1)">
                <img src="/images/lecture/london-great-smog-1952.jpg" alt="London Great Smog" onclick="lectureSlideTo(''hmid-slides'', 2)">
                <img src="/images/lecture/LA_smog_masks.jpg" alt="Gas Masks at LA Gathering" onclick="lectureSlideTo(''hmid-slides'', 3)">
                <img src="/images/lecture/first-smoke-school.jpg" alt="First Smoke School is Held in Los Angeles" onclick="lectureSlideTo(''hmid-slides'', 4)">
            </div>
        </div>
    </div>
    <div style="flex:1; min-width:320px;">
        <h2>Growing Concern About Emissions</h2>

        <h4>Addressing Emissions after World War II</h4>
        <p>After World War II, the U.S. began to revisit air pollution.</p>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''hmid-la'', ''hmid-la-icon'')" class="lect-toggle-btn">
                <span>City of Los Angeles Leads the Way</span>
                <span id="hmid-la-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="hmid-la" class="lect-toggle-body" style="display:none;">
                <p>In 1944, the Los Angeles city council discussed moving the airport LAX to a higher elevation area to alleviate flight interruptions caused by increasing smog problems.</p>
                <p>Los Angeles already had a history of air pollution. In 1903, smog was so thick that residents thought it was an eclipse of the sun. In 1943, the city suffered a "gas attack" where the smoke and fumes covered downtown and cut visibility to three blocks. This phenomenon was almost unbearable to residents; it produced stinging eyes and severe sore throats.</p>
                <p>The City of Los Angeles developed the equivalent opacity concept that extended smoke density measurements to white smoke. Equivalent opacity was associated to the Ringelmann scale in terms of how much obscuring power the black or white plume had.</p>
                <p>In 1945, the council began debating how to address air pollution. In 1946, the Los Angeles Times hired an air pollution expert to evaluate the problem and developed 23 recommendations to reduce pollution.</p>
                <p>By 1947, the city established the first governmental agency to address air quality - the Los Angeles County Air Pollution Control Board, which later became the LA County Air Pollution Control District (APCD).</p>
                <p>In 1951, the legal case Kaiser Steel vs. APCD resulted in the use of an expert to read smoke opacity using the Ringelmann scale - an early development of what is now EPA Method 9.</p>
                <p>The Los Angeles Air Pollution Control Board developed the equivalent opacity concept in 1953. It extended the current smoke density/percent blackness measurements to all other colors of smoke. The equivalent opacity scale is associated with the Ringelmann scale in terms of how much the background is obscured.</p>
            </div>
        </div>

        <h4>Significant Air Pollution Events</h4>
        <p>Increasing pollution and significant air pollution events, including Donora, Pennsylvania, and London brought attention to the dangers associated with particulate emissions.</p>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''hmid-events'', ''hmid-events-icon'')" class="lect-toggle-btn">
                <span>Donora and London</span>
                <span id="hmid-events-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="hmid-events" class="lect-toggle-body" style="display:none;">
                <p>On October 27, 1948, a yellow fog enveloped the city of Donora and a nearby village, Webster. <a href="https://www.worldsciencefestival.com/unnamed-14/" title="Donora Smog" target="_blank" rel="noopener">The Donora smog</a> was caused by an air inversion, lasted four days, and was the worst air pollution event in U.S. history. The smog resulted in 20 deaths and 7,000 illnesses. The Donora smog is considered the catalyst for clean air programs in the United States. Following the deadly Donora smog, a congressional investigation was instituted, and President Truman convened the first national air pollution conference in 1950.</p>
                <p>The Great Smog of London occurred December 5 - December 9, 1952. It was a 30-mile-wide air mass that was created by a temperature inversion where a layer of warm air trapped the cold air at ground level. It was a noxious smog that smelled like rotten eggs and reduced visibility to a degree where people were unable to see their feet as they walked. Death estimates from the smog range from 8,000 to 12,000. The Great Smog led to the passing of the UK&rsquo;s Clean Air Act in 1956.</p>
            </div>
        </div>

        <h4>Air Pollution Declared a Health Hazard and Gains National Attention</h4>
        <p>In 1948, the U.S. Surgeon General declared that smoke and pollutants were health hazards.</p>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''hmid-federal'', ''hmid-federal-icon'')" class="lect-toggle-btn">
                <span>The Federal Government Begins Efforts on Air Pollution</span>
                <span id="hmid-federal-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="hmid-federal" class="lect-toggle-body" style="display:none;">
                <p>After the Donora disaster, pollution gained an audience at the federal level. In 1949, President Truman established a federal committee on air pollution that was coordinated by the Secretary of the Interior. The first conference on air pollution was held in May 1950 in Washington, D.C.</p>
                <p>The first significant legislation was the Air Pollution Control Act of 1955. The legislation provided federal funds for government research on air pollution. Many environmental historians consider this legislation as the precursor to the Clean Air Act (CAA) and Clean Air Act amendments (CAAa).</p>
            </div>
        </div>

        <h4>California - Rule 50A and the First Smoke School</h4>
        <p>California pioneered legislation and the development of smoke schools.</p>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''hmid-ca'', ''hmid-ca-icon'')" class="lect-toggle-btn">
                <span>California (CA) Rule 50A and Smoke Training</span>
                <span id="hmid-ca-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="hmid-ca" class="lect-toggle-body" style="display:none;">
                <p>In 1950, CA 50A was passed. It was designed to limit pollution by requiring the use of the Ringelmann system in reading smoke. Rule 50A was important because it provided a basis that would be used by all U.S. states, and was used in 1970 to develop the <a href="/lecture/resources/glossary#nsps">New Source Performance Standards (NSPS)</a>.</p>
                <p>In 1953, Los Angeles County began conducting smoke schools. Their smoke school program was used as a model for the standardization of visible emissions observation (VEO) programs throughout the U.S.</p>
            </div>
        </div>
    </div>
</div>',
    3
);
