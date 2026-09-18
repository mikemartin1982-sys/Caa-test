-- ============================================================================
-- Migration 045: Self-Paced Lecture Resources (Glossary, FAQs, etc.)
-- Reference: Michael, 2026-09-07.
--
-- Confirmed with Michael:
--   - Resources content (Glossary, FAQs, Abbreviations, VEO Resources,
--     Bibliography -- matching the real, live course's own "Resources"
--     nav submenu) is deliberately its own, separate, flat reference
--     content type -- NOT part of the ten numbered sections at all. No
--     quiz, no sequential unlock, no "read" progress tracking -- the
--     real, live source itself confirms this directly in its own
--     comment: "the resources submenu has no indicators for webpages
--     that have been touched."
--   - Kept genuinely separate from lecture_pages (migration 044) rather
--     than folding in with a nullable lecture_section_id -- these pages
--     don't belong to a section at all, don't unlock sequentially, and
--     don't need student progress tracked against them, so forcing them
--     into the same table would mean carrying columns that are
--     permanently meaningless for every row of this type.
-- ============================================================================

CREATE TABLE lecture_resource_pages (
    id              BIGSERIAL PRIMARY KEY,
    title           VARCHAR(255) NOT NULL,
    slug            VARCHAR(100) NOT NULL UNIQUE,
    content         TEXT,
    order_index     INTEGER NOT NULL UNIQUE,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- Real, known Resources items and order, matching the live course's own
-- nav exactly. Seeded with empty content for the four not yet captured --
-- Michael is capturing this content next, same real workflow as the
-- numbered sections. "Submit a Question" and the external Survey link
-- are real, separate nav items, not reference content pages, so they're
-- not included here at all.
INSERT INTO lecture_resource_pages (title, slug, order_index) VALUES
    ('FAQs',            'faqs',              1),
    ('Glossary',        'glossary',          2),
    ('Abbreviations',   'veo-abbreviations', 3),
    ('VEO Resources',   'veo-resources',     4),
    ('Bibliography',    'bibliography',      5);
