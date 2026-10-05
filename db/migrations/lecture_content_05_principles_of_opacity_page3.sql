-- ============================================================================
-- Lecture content seed: Principles of Opacity section, page 3 (Particle
-- Size)
-- Reference: Michael, 2026-10-05 -- real content capture from the live
-- principles-particle-density.php. No toggles or links on this page.
--
-- Title: the live left menu calls this page "Particle Density", but its own
-- page title and the previous page's Next button say "Particle Size", and
-- the content is about particle size -- so "Particle Size". Slug keeps the
-- live filename.
--
-- Real, honest link decisions:
--   - Reuses /images/lecture/smoke-plumes.jpg (same image as page 1 on the
--     live site).
--
-- Live-source bugs fixed: the image alt text was copied from a Legal page
-- ("The role of law in visible emissions observations") -- replaced with a
-- descriptive alt; "absorption to occur- making" -> "absorption to occur,
-- making"; double spaces / trailing spaces.
-- ============================================================================

INSERT INTO lecture_pages (lecture_section_id, title, slug, content, order_index)
VALUES (
    (SELECT id FROM lecture_sections WHERE slug = 'principles-of-opacity'),
    'Particle Size',
    'principles-particle-density',
    '<div style="display:flex; gap:2rem; flex-wrap:wrap;">
    <div style="width:380px; flex-shrink:0;">
        <img src="/images/lecture/smoke-plumes.jpg" alt="Smoke plumes" style="width:100%; border-radius:0.375rem;">
    </div>
    <div style="flex:1; min-width:320px;">
        <h2>Particle Size and How it Affects Opacity Readings</h2>
        <p>A plume can consist of many different types and sizes of particles. These particles interact with light via scattering or absorption. Only the light that does not hit a particle is transmitted directly through the plume.</p>
        <p>As average particle size decreases, the total surface area of the particles within the plume increases, resulting in more surface area for scattering, refraction, and absorption to occur, making the plume appear denser (i.e., more opaque).</p>
        <p>If you compare two visible plumes with the same emission rate (weight of particles per time period) with different particle sizes, you will have two different opacity values. You can visualize this concept by thinking of standing on the top of a building and dumping 10 pounds of rice and 10 pounds of flour off the roof. You have the same amount of weight of particles in the air - but the flour will have a higher opacity - blocking more of the background.</p>
    </div>
</div>',
    3
);
