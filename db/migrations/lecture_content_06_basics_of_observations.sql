-- ============================================================================
-- Lecture content seed: Basics of Observations section, page 1
-- (Introduction)
-- Reference: Michael, 2026-10-05 -- real content capture from the live
-- basics-introduction.php. No toggles or links on this page.
--
-- Real, honest link decisions:
--   - Image /images/lecture/basic-image-of-veo.jpg is already in the repo.
--
-- Live-source bugs fixed: trailing spaces only.
-- ============================================================================

INSERT INTO lecture_pages (lecture_section_id, title, slug, content, order_index)
VALUES (
    (SELECT id FROM lecture_sections WHERE slug = 'basics-of-observations'),
    'Introduction',
    'basics-introduction',
    '<div style="display:flex; gap:2rem; flex-wrap:wrap;">
    <div style="width:380px; flex-shrink:0;">
        <img src="/images/lecture/basic-image-of-veo.jpg" alt="The basics of opacity readings" style="width:100%; border-radius:0.375rem;">
    </div>
    <div style="flex:1; min-width:320px;">
        <h2>Know the Basics of Visible Emissions Observations</h2>
        <p>This section will outline the essential elements of observations you need to know before learning to perform a visible emissions observation. We&rsquo;ll cover the basic concepts and present how variables can affect an opacity reading.</p>
        <p>The basic elements will be explained in detail in the <em>Performing Observations</em> section.</p>
        <p>First, we will summarize the factors that can affect the accuracy of visible emission observations.</p>
    </div>
</div>',
    1
);
