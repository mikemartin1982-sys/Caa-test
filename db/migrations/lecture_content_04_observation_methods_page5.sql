-- ============================================================================
-- Lecture content seed: Observation Methods section, page 5 (Quick Checks)
-- Reference: Michael, 2026-10-05 -- real content capture from the live
-- methods-quick-check.php. No toggles on this page.
--
-- Real, honest link decisions:
--   - Both images are already in the repo, so nothing new to download:
--     the live page's ../images/resource-images/emissions-quick-check.jpg is
--     byte-identical (398,665 bytes) to our
--     /images/lecture/glossary/emissions-quick-check.jpg (Glossary resource),
--     and Baghouse-dust-collector.jpg was added with Method 22 (page 3).
--   - "baghouse" was a bold-red glossary.php link ->
--     /lecture/resources/glossary#baghouse.
--
-- Live-source bugs fixed: trailing double spaces; the second image's fixed
-- width="500px" (overflowed narrow screens) is now width:100% capped at
-- 500px. Live span ids used as search anchors (quickop, quickchk) dropped.
-- ============================================================================

INSERT INTO lecture_pages (lecture_section_id, title, slug, content, order_index)
VALUES (
    (SELECT id FROM lecture_sections WHERE slug = 'observation-methods'),
    'Quick Checks',
    'methods-quick-check',
    '<div style="display:flex; gap:2rem; flex-wrap:wrap;">
    <div style="width:380px; flex-shrink:0;">
        <img src="/images/lecture/glossary/emissions-quick-check.jpg" alt="Quick check opacity checks" style="width:100%; border-radius:0.375rem;">
        <p>A quick check is not an opacity method. It does not measure opacity.</p>
    </div>
    <div style="flex:1; min-width:320px;">
        <h2>Quick Checks</h2>
        <h3>Is There a Visible Emission Present?</h3>
        <p>The quick check method is used to observe facility emission sources to detect if a visible emission is present. Quick checks are commonly used when an observation Method is not specified in the air quality permit. No training is required for quick checks. However, personnel must know the difference between water vapor and visible emission and understand the fundamentals of visible emission observations.</p>
        <p>Documentation of quick checks is typically a checklist of sources with a date, a yes/no result for each source, and the observer&rsquo;s signature. If a quick check identifies an emission from a source, it typically triggers a requirement to utilize Method 22 or Method 9 to make additional observations.</p>
        <p>Quick checks are instrumental in detecting <a href="/lecture/resources/glossary#baghouse">baghouse</a> malfunctions.</p>
        <img src="/images/lecture/Baghouse-dust-collector.jpg" alt="Baghouse filter system" style="width:100%; max-width:500px; border-radius:0.375rem;">
    </div>
</div>',
    5
);
