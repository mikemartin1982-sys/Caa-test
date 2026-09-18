-- ============================================================================
-- Lecture quiz seed: Introduction section quiz
-- Reference: Michael, 2026-09-07 -- real quiz content and correct answers
-- captured from the live course. Seeded now even though the real
-- quiz-taking UI doesn't exist yet (migration 044's own schema, no
-- interface built on top of it yet) -- so this data is ready and doesn't
-- need to be recaptured later.
--
-- Uses INSERT ... RETURNING via CTEs to link each question to its own
-- choices by real id, rather than re-matching on question_text (which
-- has no uniqueness guarantee at all and would be a real, fragile
-- pattern if a future section ever reuses the same question wording).
-- ============================================================================

WITH quiz AS (
    INSERT INTO lecture_quizzes (lecture_section_id, passing_score_percent)
    VALUES ((SELECT id FROM lecture_sections WHERE slug = 'introduction'), 70)
    RETURNING id
),
q1 AS (
    INSERT INTO lecture_quiz_questions (lecture_quiz_id, question_text, order_index)
    SELECT id, 'Method 22 is an opacity method:', 1 FROM quiz
    RETURNING id
),
q2 AS (
    INSERT INTO lecture_quiz_questions (lecture_quiz_id, question_text, order_index)
    SELECT id, 'In visible emissions observations, opacity is defined as:', 2 FROM quiz
    RETURNING id
),
q3 AS (
    INSERT INTO lecture_quiz_questions (lecture_quiz_id, question_text, order_index)
    SELECT id, 'Method 9:', 3 FROM quiz
    RETURNING id
),
q4 AS (
    INSERT INTO lecture_quiz_questions (lecture_quiz_id, question_text, order_index)
    SELECT id, 'A Method 9 certification is valid for:', 4 FROM quiz
    RETURNING id
)
INSERT INTO lecture_quiz_choices (lecture_quiz_question_id, choice_text, is_correct, order_index)
SELECT * FROM (
    -- Question 1: Method 22 is an opacity method: (False)
    SELECT (SELECT id FROM q1), 'True', false, 1
    UNION ALL
    SELECT (SELECT id FROM q1), 'False', true, 2
    -- Question 2: In visible emissions observations, opacity is defined as:
    UNION ALL
    SELECT (SELECT id FROM q2), 'The percentage of dust in the plume', false, 1
    UNION ALL
    SELECT (SELECT id FROM q2), 'A percentage of how black the plume is', false, 2
    UNION ALL
    SELECT (SELECT id FROM q2), 'The percentage of background that is obscured by a plume', true, 3
    -- Question 3: Method 9:
    UNION ALL
    SELECT (SELECT id FROM q3), 'Measures the duration of an emission', false, 1
    UNION ALL
    SELECT (SELECT id FROM q3), 'Quantifies the opacity level', true, 2
    UNION ALL
    SELECT (SELECT id FROM q3), 'Requires no certification', false, 3
    UNION ALL
    SELECT (SELECT id FROM q3), 'Measures fugitive emissions', false, 4
    -- Question 4: A Method 9 certification is valid for:
    UNION ALL
    SELECT (SELECT id FROM q4), '1 year', false, 1
    UNION ALL
    SELECT (SELECT id FROM q4), '1 month', false, 2
    UNION ALL
    SELECT (SELECT id FROM q4), 'Does not expire', false, 3
    UNION ALL
    SELECT (SELECT id FROM q4), '6 months', true, 4
) AS choices(question_id, choice_text, is_correct, order_index);
