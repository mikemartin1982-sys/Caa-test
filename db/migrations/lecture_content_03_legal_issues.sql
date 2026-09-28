-- ============================================================================
-- Lecture content seed: Legal Issues section, page 1 (Introduction)
-- Reference: Michael, 2026-09-28 -- real content capture from the live
-- legal-introduction.php. Short overview page: image plus a two-item list
-- of what the section covers. No toggles, glossary mentions, or links in
-- the live source.
--
-- Image copied into public/images/lecture/legal-veo.jpg.
-- ============================================================================

INSERT INTO lecture_pages (lecture_section_id, title, slug, content, order_index)
VALUES (
    (SELECT id FROM lecture_sections WHERE slug = 'legal-issues'),
    'Introduction',
    'legal-introduction',
    '<div style="display:flex; gap:2rem; flex-wrap:wrap;">
    <div style="width:380px; flex-shrink:0;">
        <img src="/images/lecture/legal-veo.jpg" alt="The role of law in visible emissions observations" style="width:100%; border-radius:0.375rem;">
    </div>
    <div style="flex:1; min-width:320px;">
        <h2>Introduction to Legal Issues</h2>
        <p>This course section focuses on legal issues related to visible emissions observations and compliance.</p>
        <p>The section will cover:</p>
        <ul>
            <li>History of important cases that formed the foundation for modern VEO law</li>
            <li>The visible emissions observer&rsquo;s role in legal compliance</li>
        </ul>
    </div>
</div>',
    1
);
