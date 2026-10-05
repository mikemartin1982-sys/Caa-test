-- ============================================================================
-- Lecture content seed: Observation Methods section, page 4
-- (Method 9/22 Comparison)
-- Reference: Michael, 2026-10-05 -- real content capture from the live
-- methods-comparison.php. A single comparison table (Method 9 / Method 22 /
-- Quick Check) -- no image, toggles, or links on the live page.
--
-- Layout: the table sits in a horizontally scrollable wrapper so its four
-- columns stay readable on a phone instead of squeezing; header row in CAA
-- blue, row labels in CAA red (the live page's first header cell was red).
-- Quick Check cells that are blank on the live page show an em dash so the
-- empty cells read as "not applicable" rather than missing content.
--
-- Live-source bugs fixed: double spaces ("required.  The", "a  lecture");
-- missing period after "but not the opacity". The live page also printed
-- the Previous/Next buttons twice (above and below the table) -- our
-- layout's single Previous/Next pair covers it.
-- ============================================================================

INSERT INTO lecture_pages (lecture_section_id, title, slug, content, order_index)
VALUES (
    (SELECT id FROM lecture_sections WHERE slug = 'observation-methods'),
    'Method 9/22 Comparison',
    'methods-comparison',
    '<h2>Method 9 and Method 22 Comparison</h2>
<div style="overflow-x:auto;">
<table style="width:100%; min-width:720px; border-collapse:collapse; font-size:0.95rem;">
    <thead>
        <tr style="background:#005da0; color:#ffffff;">
            <th style="padding:0.75rem; border:1px solid #d1d5db; width:15%; background:#b82027;"></th>
            <th style="padding:0.75rem; border:1px solid #d1d5db; width:28%; text-align:left;">Method 9</th>
            <th style="padding:0.75rem; border:1px solid #d1d5db; width:28%; text-align:left;">Method 22</th>
            <th style="padding:0.75rem; border:1px solid #d1d5db; width:29%; text-align:left;">Quick Check</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <th scope="row" style="padding:0.75rem; border:1px solid #d1d5db; text-align:left; color:#b82027; vertical-align:top;">Applicability</th>
            <td style="padding:0.75rem; border:1px solid #d1d5db; vertical-align:top;">Any NSPS and SIP sources with an opacity standard, e.g., 20 percent opacity.</td>
            <td style="padding:0.75rem; border:1px solid #d1d5db; vertical-align:top;">NSPS and SIP fugitive and specified flare sources with a &ldquo;no visible emission&rdquo; standard. No opacity level is specified.</td>
            <td style="padding:0.75rem; border:1px solid #d1d5db; vertical-align:top;">Title V observation is required periodically, but no method has been specified in the permit. Typically, the air permit reads, &ldquo;Check to see if visible emissions are present.&rdquo;</td>
        </tr>
        <tr style="background:#f9fafb;">
            <th scope="row" style="padding:0.75rem; border:1px solid #d1d5db; text-align:left; color:#b82027; vertical-align:top;">Measurement</th>
            <td style="padding:0.75rem; border:1px solid #d1d5db; vertical-align:top;">Method 9 determines the value of the opacity measured in a percentage.</td>
            <td style="padding:0.75rem; border:1px solid #d1d5db; vertical-align:top;">Method 22 determines the existence of a plume but not the opacity.</td>
            <td style="padding:0.75rem; border:1px solid #d1d5db; vertical-align:top;">It provides only a momentary result on the presence or lack of emission, i.e., Is an emission present? Yes or no.</td>
        </tr>
        <tr>
            <th scope="row" style="padding:0.75rem; border:1px solid #d1d5db; text-align:left; color:#b82027; vertical-align:top;">Field Certification</th>
            <td style="padding:0.75rem; border:1px solid #d1d5db; vertical-align:top;">The observer must demonstrate the ability to measure plume opacity through field testing every six months.</td>
            <td style="padding:0.75rem; border:1px solid #d1d5db; vertical-align:top;">The observer is not required to participate in field certification.</td>
            <td style="padding:0.75rem; border:1px solid #d1d5db; vertical-align:top;">The observer is not required to participate in field certification but should be &ldquo;familiar&rdquo; with opacity concepts. Ensure the Quick Check observer understands the difference between water vapor and an emission.</td>
        </tr>
        <tr style="background:#f9fafb;">
            <th scope="row" style="padding:0.75rem; border:1px solid #d1d5db; text-align:left; color:#b82027; vertical-align:top;">Lecture Certification</th>
            <td style="padding:0.75rem; border:1px solid #d1d5db; vertical-align:top;">States differ on lecture certification requirements. Some states require lecture certifications; some do not.</td>
            <td style="padding:0.75rem; border:1px solid #d1d5db; vertical-align:top;">Actual certification is not required. The observer must be able to demonstrate knowledge of opacity principles. Completing a lecture course is advised, but reading about the principles is acceptable.</td>
            <td style="padding:0.75rem; border:1px solid #d1d5db; vertical-align:top; color:#9ca3af;">&mdash;</td>
        </tr>
        <tr>
            <th scope="row" style="padding:0.75rem; border:1px solid #d1d5db; text-align:left; color:#b82027; vertical-align:top;">Distance from Source</th>
            <td style="padding:0.75rem; border:1px solid #d1d5db; vertical-align:top;">No distance is specified, but the observer must have a clear view of the emissions at specified angles to the plume.</td>
            <td style="padding:0.75rem; border:1px solid #d1d5db; vertical-align:top;">Distance requirements are 15 feet to 0.25 miles.</td>
            <td style="padding:0.75rem; border:1px solid #d1d5db; vertical-align:top; color:#9ca3af;">&mdash;</td>
        </tr>
        <tr style="background:#f9fafb;">
            <th scope="row" style="padding:0.75rem; border:1px solid #d1d5db; text-align:left; color:#b82027; vertical-align:top;">Viewing Angle</th>
            <td style="padding:0.75rem; border:1px solid #d1d5db; vertical-align:top;">The observer views the plume from a position that minimizes the line of sight through the plume to minimize positive bias.</td>
            <td style="padding:0.75rem; border:1px solid #d1d5db; vertical-align:top;">The observer simply reports the existence or non-existence of the plume.</td>
            <td style="padding:0.75rem; border:1px solid #d1d5db; vertical-align:top; color:#9ca3af;">&mdash;</td>
        </tr>
        <tr>
            <th scope="row" style="padding:0.75rem; border:1px solid #d1d5db; text-align:left; color:#b82027; vertical-align:top;">Light Source</th>
            <td style="padding:0.75rem; border:1px solid #d1d5db; vertical-align:top;">The sun is the implied light source and must be at the observer&rsquo;s back.</td>
            <td style="padding:0.75rem; border:1px solid #d1d5db; vertical-align:top;">Light sources other than the sun are acceptable but must be documented. The light must be at least 100 lux but is not required to be at the observer&rsquo;s back.</td>
            <td style="padding:0.75rem; border:1px solid #d1d5db; vertical-align:top; color:#9ca3af;">&mdash;</td>
        </tr>
        <tr style="background:#f9fafb;">
            <th scope="row" style="padding:0.75rem; border:1px solid #d1d5db; text-align:left; color:#b82027; vertical-align:top;">Viewing Intervals</th>
            <td style="padding:0.75rem; border:1px solid #d1d5db; vertical-align:top;">Momentary observation every 15 seconds for a period determined by the standard. Each observation is recorded and used to determine the average opacity percentage.</td>
            <td style="padding:0.75rem; border:1px solid #d1d5db; vertical-align:top;">Continuous viewing that includes observer rest breaks every 15 to 20 minutes. The Method 22 observer times the emissions with a stopwatch and records the duration of the emissions.</td>
            <td style="padding:0.75rem; border:1px solid #d1d5db; vertical-align:top; color:#9ca3af;">&mdash;</td>
        </tr>
    </tbody>
</table>
</div>',
    4
);
