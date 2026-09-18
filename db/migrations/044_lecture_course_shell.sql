-- ============================================================================
-- Migration 044: Self-Paced Lecture Course (shell)
-- Reference: Michael, 2026-09-06 -- CAA Self-Paced Lecture rebuild.
--
-- Confirmed with Michael:
--   - Sign-in is student_number + last name against the real, existing
--     students table -- no new identity/auth system at all. Access also
--     requires students.self_paced_lecture_allowed = true and at least one
--     real enrollments row with enrollment_components = 'LECTURE_ONLY' for
--     that student (migration 031) -- no dedicated "lecture session" entity
--     exists or is needed; LECTURE_ONLY enrollments are tied to whichever
--     real session the student is actually enrolled in.
--   - Progress (pages read, quiz attempts/scores, current unlocked section)
--     is tracked here so a student doesn't lose their place if they step
--     away -- but this is deliberately NOT the permanent record. Only a
--     genuine final pass writes to students.lecture_complete /
--     lecture_completion_date / lecture_completion_source (migration 001,
--     already exist) -- no new column needed on students at all.
--   - Course structure (10 sections, ~60+ pages, one quiz per section,
--     70% passing threshold, strictly sequential unlock) matches the real,
--     live self-paced lecture site exactly (confirmed against its real,
--     current source and nav). Section names/order seeded below from that
--     real source; pages/quizzes are seeded empty -- Michael is capturing
--     that real content one section at a time from the live course.
--   - Deliberately out of scope for this shell: saving a quiz attempt
--     mid-attempt (unanswered/in-progress questions). The real, live
--     source itself has no such feature -- only completed attempts are
--     tracked here. If a student steps away mid-quiz, they retake that
--     quiz from the start; their page-read progress is still preserved.
-- ============================================================================

-- ---------------------------------------------------------------------------
-- lecture_sections
-- The 10 top-level sections. order_index drives both display order and the
-- real, sequential unlock rule (a section is locked until the previous
-- section's quiz has been passed).
-- ---------------------------------------------------------------------------
CREATE TABLE lecture_sections (
    id              BIGSERIAL PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,
    slug            VARCHAR(100) NOT NULL UNIQUE,   -- e.g. 'introduction', 'legal-issues'
    order_index     INTEGER NOT NULL UNIQUE,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- Real section names and order, matching the live course's own nav exactly.
INSERT INTO lecture_sections (name, slug, order_index) VALUES
    ('Introduction',              'introduction',           1),
    ('History',                   'history',                2),
    ('Legal Issues',              'legal-issues',            3),
    ('Observation Methods',       'observation-methods',    4),
    ('Principles of Opacity',     'principles-of-opacity',  5),
    ('Basics of Observations',    'basics-of-observations', 6),
    ('Performing Observations',   'performing-observations',7),
    ('VEO Form Instructions',     'veo-form-instructions',  8),
    ('Field Testing',             'field-testing',          9),
    ('Method 22',                 'method-22',              10);

-- ---------------------------------------------------------------------------
-- lecture_pages
-- Individual content pages within a section. order_index drives both
-- display order and the real, sequential unlock rule (a page is locked
-- until the previous page in the same section has been read). Seeded empty
-- -- Michael is capturing real page content one section at a time.
-- ---------------------------------------------------------------------------
CREATE TABLE lecture_pages (
    id                  BIGSERIAL PRIMARY KEY,
    lecture_section_id  BIGINT NOT NULL REFERENCES lecture_sections(id) ON DELETE CASCADE,
    title               VARCHAR(255) NOT NULL,
    slug                VARCHAR(100) NOT NULL,      -- e.g. 'getting-started'
    content             TEXT,                        -- real page body (HTML), filled in as captured
    order_index         INTEGER NOT NULL,
    created_at          TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at          TIMESTAMPTZ NOT NULL DEFAULT now(),
    UNIQUE (lecture_section_id, order_index),
    UNIQUE (lecture_section_id, slug)
);

CREATE INDEX idx_lecture_pages_section ON lecture_pages (lecture_section_id);

-- ---------------------------------------------------------------------------
-- lecture_quizzes
-- One quiz per section. passing_score_percent defaults to 70, matching the
-- real, live course's own stated threshold ("at least 70% correct").
-- ---------------------------------------------------------------------------
CREATE TABLE lecture_quizzes (
    id                      BIGSERIAL PRIMARY KEY,
    lecture_section_id      BIGINT NOT NULL UNIQUE REFERENCES lecture_sections(id) ON DELETE CASCADE,
    passing_score_percent   INTEGER NOT NULL DEFAULT 70,
    created_at              TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at              TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- ---------------------------------------------------------------------------
-- lecture_quiz_questions / lecture_quiz_choices
-- Multiple-choice questions per quiz. Seeded empty -- Michael is capturing
-- real questions/answers one section at a time.
-- ---------------------------------------------------------------------------
CREATE TABLE lecture_quiz_questions (
    id                  BIGSERIAL PRIMARY KEY,
    lecture_quiz_id     BIGINT NOT NULL REFERENCES lecture_quizzes(id) ON DELETE CASCADE,
    question_text       TEXT NOT NULL,
    order_index         INTEGER NOT NULL,
    created_at          TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at          TIMESTAMPTZ NOT NULL DEFAULT now(),
    UNIQUE (lecture_quiz_id, order_index)
);

CREATE INDEX idx_lecture_quiz_questions_quiz ON lecture_quiz_questions (lecture_quiz_id);

CREATE TABLE lecture_quiz_choices (
    id                          BIGSERIAL PRIMARY KEY,
    lecture_quiz_question_id    BIGINT NOT NULL REFERENCES lecture_quiz_questions(id) ON DELETE CASCADE,
    choice_text                 TEXT NOT NULL,
    is_correct                  BOOLEAN NOT NULL DEFAULT false,
    order_index                 INTEGER NOT NULL,
    UNIQUE (lecture_quiz_question_id, order_index)
);

CREATE INDEX idx_lecture_quiz_choices_question ON lecture_quiz_choices (lecture_quiz_question_id);

-- ---------------------------------------------------------------------------
-- lecture_student_page_progress
-- The real "don't lose progress if they step away" table for page reads.
-- One row per student per page, written the moment a page is marked read.
-- ---------------------------------------------------------------------------
CREATE TABLE lecture_student_page_progress (
    id              BIGSERIAL PRIMARY KEY,
    student_id      BIGINT NOT NULL REFERENCES students(id) ON DELETE CASCADE,
    lecture_page_id BIGINT NOT NULL REFERENCES lecture_pages(id) ON DELETE CASCADE,
    read_at         TIMESTAMPTZ NOT NULL DEFAULT now(),
    UNIQUE (student_id, lecture_page_id)
);

CREATE INDEX idx_lecture_student_page_progress_student ON lecture_student_page_progress (student_id);

-- ---------------------------------------------------------------------------
-- lecture_student_quiz_attempts
-- One row per completed quiz attempt (a student may retry a failed quiz).
-- Deliberately does not track in-progress/unsubmitted answers -- see note
-- at top of file.
-- ---------------------------------------------------------------------------
CREATE TABLE lecture_student_quiz_attempts (
    id                  BIGSERIAL PRIMARY KEY,
    student_id          BIGINT NOT NULL REFERENCES students(id) ON DELETE CASCADE,
    lecture_quiz_id     BIGINT NOT NULL REFERENCES lecture_quizzes(id) ON DELETE CASCADE,
    score_percent       INTEGER NOT NULL,
    passed              BOOLEAN NOT NULL,
    attempted_at        TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX idx_lecture_student_quiz_attempts_student ON lecture_student_quiz_attempts (student_id);
CREATE INDEX idx_lecture_student_quiz_attempts_quiz ON lecture_student_quiz_attempts (lecture_quiz_id);

-- Per-question answers within a completed attempt -- real, auditable detail
-- of what the student actually answered on their passing (or failing) try.
CREATE TABLE lecture_student_quiz_answers (
    id                          BIGSERIAL PRIMARY KEY,
    lecture_student_quiz_attempt_id BIGINT NOT NULL REFERENCES lecture_student_quiz_attempts(id) ON DELETE CASCADE,
    lecture_quiz_question_id    BIGINT NOT NULL REFERENCES lecture_quiz_questions(id),
    lecture_quiz_choice_id      BIGINT NOT NULL REFERENCES lecture_quiz_choices(id),
    UNIQUE (lecture_student_quiz_attempt_id, lecture_quiz_question_id)
);
