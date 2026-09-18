-- ============================================================================
-- Lecture content seed: Introduction section, page 5 (Definition ::
-- Opacity)
-- Reference: Michael, 2026-09-07 -- real content capture, the last real
-- page in the Introduction section (confirmed from the live source
-- itself: its own "next" control is a real "Go to quiz" link, not
-- another page, and History is genuinely disabled until the quiz is
-- passed -- matching this course's own, already-built nextIsQuiz
-- behavior exactly, no code changes needed for this page).
--
-- No toggle sections on this page at all in the real, original source --
-- just plain paragraphs.
--
-- Real, honest link decision: the one real glossary.php#opacity
-- reference linked directly to our own, real Glossary page''s #opacity
-- anchor, since that target now exists (migration 045 + the glossary
-- content already built).
-- ============================================================================

INSERT INTO lecture_pages (lecture_section_id, title, slug, content, order_index)
VALUES (
    (SELECT id FROM lecture_sections WHERE slug = 'introduction'),
    'Definition :: Opacity',
    'veo-definition-opacity',
    '<div style="display:flex; gap:2rem; flex-wrap:wrap;">
    <div style="width:380px; flex-shrink:0;">
        <img src="/images/lecture/opacity-image.jpg" alt="Smoke plumes with varying amounts of opacity" style="width:100%; border-radius:0.375rem;">
    </div>
    <div style="flex:1; min-width:320px;">
        <h2>Definition of Opacity</h2>
        <p>In the visible emissions industry, <a href="/lecture/resources/glossary#opacity">opacity</a> is defined as the percentage of the background that is obscured (i.e., blocked) by visible emissions, i.e., the plume''s ability to obscure the background. A higher opacity value in a visible emission observation means there is more particulate matter in the emission, and less of the background is seen.</p>
        <p>When reading the opacity of a plume, the background should have a high degree of contrast (both in color and luminescence) against the smoke to facilitate the reading, and the sun should MUST be at the back of the observer in the 140-degree sector behind the observer.</p>
        <p>An opacity observer looks at the background, not the emission, not how much smoke is there.</p>
    </div>
</div>',
    5
);
