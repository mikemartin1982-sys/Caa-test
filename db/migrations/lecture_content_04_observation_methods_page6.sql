-- ============================================================================
-- Lecture content seed: Observation Methods section, page 6 (Other EPA
-- Methods)
-- Reference: Michael, 2026-10-05 -- real content capture from the live
-- methods-other.php. Last page of the Observation Methods section; the
-- section quiz follows. The three method toggles (203A, 203B, 203C) are
-- real, collapsible toggles.
--
-- Real, honest link decisions:
--   - Image copied into public/images/lecture/Method203.jpg.
--   - EPA EMC pages for Methods 203A, 203B, 203C all checked 200 on
--     2026-10-05 -- kept.
--
-- Live-source bugs fixed: missing period (and trailing double space) at
-- the end of the second intro paragraph ("...outlined in Method 9");
-- hard line breaks inside the 203B paragraph; stray <hr> dividers after the
-- toggles dropped.
-- ============================================================================

INSERT INTO lecture_pages (lecture_section_id, title, slug, content, order_index)
VALUES (
    (SELECT id FROM lecture_sections WHERE slug = 'observation-methods'),
    'Other EPA Methods',
    'methods-other',
    '<div style="display:flex; gap:2rem; flex-wrap:wrap;">
    <div style="width:380px; flex-shrink:0;">
        <img src="/images/lecture/Method203.jpg" alt="EPA Method 203a, 203b, 203c" style="width:100%; border-radius:0.375rem;">
    </div>
    <div style="flex:1; min-width:320px;">
        <h2>EPA Methods 203A, 203B, 203C</h2>
        <p>Alternative test methods were developed to augment Method 9 to address cases where averaging times are different than 6 minutes, there are instantaneous emission standards, or the tested stack does not conform to standard emission points.</p>
        <p>Alternate methods are typically used where air pollution regulations differ from federal ones. These alternative methods are test methods suitable for State Implementation Plans (SIP) and are applicable under the scope and scientific principles outlined in Method 9.</p>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''mo-203a'', ''mo-203a-icon'')" class="lect-toggle-btn">
                <span>Method 203A - Time Averaged</span>
                <span id="mo-203a-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="mo-203a" class="lect-toggle-body" style="display:none;">
                <p>Method 203A allows for data reduction - averaging times can be less than six minutes (the standard observation period for Method 9). It is identical to Method 9, except readings are taken every 15 seconds, ranging from two (2) to six (6) minutes - as specified in the applicable regulation. Note: If a 203A is performed for 6 minutes, the data reduction is the same as Method 9.</p>
                <p>Method 203A also includes procedures for fugitive emissions. It specifies that the observer should stand at least 5 meters from the source while following all other Method 9 observation rules concerning sun angle, wind direction, and path length.</p>
                <p><a href="https://www.epa.gov/emc/method-203a-opacity-determination-time-averaged-regulations" target="_blank" rel="noopener" title="Information about EPA Method 203A">EPA Document &raquo;</a></p>
            </div>
        </div>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''mo-203b'', ''mo-203b-icon'')" class="lect-toggle-btn">
                <span>Method 203B - Time Exception</span>
                <span id="mo-203b-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="mo-203b" class="lect-toggle-body" style="display:none;">
                <p>Method 203B accommodates source emission regulations that have time exceptions. A time exception regulation allows predefined periods of opacity above the otherwise applicable opacity limit (e.g., allowing exceedances of 20% opacity for 3 minutes in 1 hour.)</p>
                <p><a href="https://www.epa.gov/emc/method-203b-opacity-determination-time-exception-regulations" target="_blank" rel="noopener" title="Information about EPA Method 203B">EPA Document &raquo;</a></p>
            </div>
        </div>

        <div class="lect-toggle">
            <button type="button" onclick="lectureToggle(''mo-203c'', ''mo-203c-icon'')" class="lect-toggle-btn">
                <span>Method 203C - Instantaneous Limitation</span>
                <span id="mo-203c-icon" class="lect-toggle-icon">+</span>
            </button>
            <div id="mo-203c" class="lect-toggle-body" style="display:none;">
                <p>Method 203C is for instantaneous limitation regulations, i.e., a defined opacity limit that should NEVER be exceeded.</p>
                <p>Method 203C observations are made at the point of greatest opacity in that portion of the plume where condensed water vapor is not present. The observer does not look continuously at the plume; instead, they observe it momentarily at 5-second intervals.</p>
                <p><a href="https://www.epa.gov/emc/method-203c-opacity-determination-instantaneous-regulations" target="_blank" rel="noopener" title="Information about EPA Method 203C">EPA Document &raquo;</a></p>
            </div>
        </div>
    </div>
</div>',
    6
);
