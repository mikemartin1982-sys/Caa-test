-- ============================================================================
-- Migration 046: Real, in-progress quiz answers
-- Reference: Michael, 2026-09-07 -- Self-Paced Lecture quiz-taking flow.
--
-- Corrects a real, genuine gap in migration 044's own design: that
-- migration's comments explicitly called saving a quiz attempt
-- mid-attempt "deliberately out of scope," based on an incorrect
-- assumption that the real, live course didn't support it. Michael has
-- since confirmed the real, live course saves each answer immediately
-- as it's selected (per-question radio onChange triggers an immediate
-- form submit) -- not just at the end of a completed attempt. This
-- migration adds the real table that behavior needs.
--
-- Deliberately separate from lecture_student_quiz_attempts /
-- lecture_student_quiz_answers (migration 044) -- those remain the real,
-- permanent record of completed attempts (score, pass/fail, historical
-- answers). This new table is the working, in-progress state: a
-- student's current answer to each question in a quiz they haven't
-- finished yet. Once every question in a quiz has a real, in-progress
-- answer, the real quiz-taking endpoint computes the final score,
-- writes a real, permanent attempt record, and clears these in-progress
-- rows -- so a retake genuinely starts fresh.
-- ============================================================================

CREATE TABLE lecture_student_quiz_in_progress_answers (
    id                          BIGSERIAL PRIMARY KEY,
    student_id                  BIGINT NOT NULL REFERENCES students(id) ON DELETE CASCADE,
    lecture_quiz_question_id    BIGINT NOT NULL REFERENCES lecture_quiz_questions(id) ON DELETE CASCADE,
    lecture_quiz_choice_id      BIGINT NOT NULL REFERENCES lecture_quiz_choices(id),
    answered_at                 TIMESTAMPTZ NOT NULL DEFAULT now(),
    UNIQUE (student_id, lecture_quiz_question_id)
);

CREATE INDEX idx_lecture_student_quiz_in_progress_answers_student ON lecture_student_quiz_in_progress_answers (student_id);
