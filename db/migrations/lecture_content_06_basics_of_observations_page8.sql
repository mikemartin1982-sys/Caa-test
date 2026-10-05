-- ============================================================================
-- Lecture content seed: Basics of Observations section, page 8 (Lighting and
-- Weather Conditions)
-- Reference: Michael, 2026-10-05 -- real content capture from the live
-- basics-lighting.php. Last page of the section; the section quiz follows.
-- No toggles. The left column keeps the image, its caption, and the cloud
-- cover table, as on the live page.
--
-- Real, honest link decisions:
--   - Image /images/lecture/red-sky-plume.jpg is already in the repo.
--   - "sun angle in summer affects visible emissions observations" linked to
--     the live veoreading-time-of-day.php -- a lecture page (Performing
--     Observations :: Sun Angle). Cross-page lecture links are kept as plain
--     text (page ids differ PC vs server), so it reads as plain text here.
--
-- Live-source bugs fixed: the caption sat in a <div> inside <em> inside an
-- unclosed <p> -- rebuilt as a plain caption paragraph; an unclosed <span
-- id="lighting"> inside the first list item dropped; the table used
-- border/cellpadding attributes -- now styled like our other tables;
-- double/trailing spaces; trailing empty paragraph dropped.
-- ============================================================================

INSERT INTO lecture_pages (lecture_section_id, title, slug, content, order_index)
VALUES (
    (SELECT id FROM lecture_sections WHERE slug = 'basics-of-observations'),
    'Lighting and Weather Conditions',
    'basics-lighting',
    '<div style="display:flex; gap:2rem; flex-wrap:wrap;">
    <div style="width:380px; flex-shrink:0;">
        <img src="/images/lecture/red-sky-plume.jpg" alt="Lighting conditions affect opacity readings" style="width:100%; border-radius:0.375rem;">
        <p style="font-size:0.85rem; color:#666666; margin-top:0.4rem;"><em>When the sun is in the foreground, steam plumes can appear dark due to the forward scattering of light. In this case, emission opacity would be overreported.</em></p>
        <table style="width:100%; border-collapse:collapse; font-size:0.95rem; margin-top:1rem;">
            <thead>
                <tr style="background:#005da0; color:#ffffff;">
                    <th style="padding:0.6rem; border:1px solid #d1d5db; width:35%; background:#b82027; text-align:left;">Term</th>
                    <th style="padding:0.6rem; border:1px solid #d1d5db; text-align:left;">Amount of Cloud Cover</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <th scope="row" style="padding:0.6rem; border:1px solid #d1d5db; text-align:left; color:#b82027;">Clear</th>
                    <td style="padding:0.6rem; border:1px solid #d1d5db;">Less than 10%</td>
                </tr>
                <tr style="background:#f9fafb;">
                    <th scope="row" style="padding:0.6rem; border:1px solid #d1d5db; text-align:left; color:#b82027;">Scattered</th>
                    <td style="padding:0.6rem; border:1px solid #d1d5db;">10% - 50%</td>
                </tr>
                <tr>
                    <th scope="row" style="padding:0.6rem; border:1px solid #d1d5db; text-align:left; color:#b82027;">Broken</th>
                    <td style="padding:0.6rem; border:1px solid #d1d5db;">50% - 90%</td>
                </tr>
                <tr style="background:#f9fafb;">
                    <th scope="row" style="padding:0.6rem; border:1px solid #d1d5db; text-align:left; color:#b82027;">Overcast</th>
                    <td style="padding:0.6rem; border:1px solid #d1d5db;">Greater than 90%</td>
                </tr>
            </tbody>
        </table>
    </div>
    <div style="flex:1; min-width:320px;">
        <h2>Lighting Conditions</h2>
        <p>Lighting conditions can impact the appearance of plume opacity in several ways:</p>
        <ul>
            <li>Red and orange skies affect VEO readings due to the properties of light (low frequency/high transmission of those colors on the spectrum). Observers should avoid readings at times close to sunrise or sunset because they lead to underreporting opacity.</li>
            <li>The summer months in the northern hemisphere create sun angle conditions that restrict VEO observation time windows. Learn more about how the sun angle in summer affects visible emissions observations.</li>
        </ul>
        <h2>Weather Conditions</h2>
        <p>Cloud cover affects VEO readings:</p>
        <ul>
            <li>Bright, direct sunlight in clear sky conditions is optimal for dark/black plumes. The observer may overstate the opacity of lighter-colored plumes due to the internal scattering within the plume.</li>
            <li>Scattered cloud cover or broken cloud cover requires adjustments for white smoke observations due to the varying light conditions.</li>
            <li>Overcast conditions can make dark plume observations difficult due to the changing cloud cover from lighter to darker. In this case, it helps to find a branch or structure that does not block the light from the sky and provides a steady, unchanging condition.</li>
        </ul>
        <p>During field certification, students are taught how to compensate for varying sky conditions when observing visible emissions. Readings during different cloud covers become easier with experience.</p>
    </div>
</div>',
    8
);
