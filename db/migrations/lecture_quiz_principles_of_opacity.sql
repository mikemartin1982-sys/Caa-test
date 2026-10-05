-- ============================================================================
-- Lecture quiz seed: Principles of Opacity section quiz
-- Reference: Michael, 2026-10-05 -- real quiz content and correct answers
-- captured from the live course's Principles of Opacity quiz page
-- (quiz_section_id=8), where each choice carries data-result="correct"/
-- "incorrect"; matches Michael's screenshot of the passed quiz (all 4
-- correct). Four questions, choices in the live display order, 70% to pass.
--
-- Live-source fixes: missing terminal period on question 4.
--
-- Same INSERT ... RETURNING CTE pattern as lecture_quiz_introduction.sql.
-- ============================================================================

WITH quiz AS (
    INSERT INTO lecture_quizzes (lecture_section_id, passing_score_percent)
    VALUES ((SELECT id FROM lecture_sections WHERE slug = 'principles-of-opacity'), 70)
    RETURNING id
),
q1 AS (
    INSERT INTO lecture_quiz_questions (lecture_quiz_id, question_text, order_index)
    SELECT id, 'Air pollution standards exist in order to minimize the following:', 1 FROM quiz
    RETURNING id
),
q2 AS (
    INSERT INTO lecture_quiz_questions (lecture_quiz_id, question_text, order_index)
    SELECT id, 'White smoke or light-colored emissions absorb most of the light from the background.', 2 FROM quiz
    RETURNING id
),
q3 AS (
    INSERT INTO lecture_quiz_questions (lecture_quiz_id, question_text, order_index)
    SELECT id, 'A greater number of particles per volume in an emission equals a higher opacity reading.', 3 FROM quiz
    RETURNING id
),
q4 AS (
    INSERT INTO lecture_quiz_questions (lecture_quiz_id, question_text, order_index)
    SELECT id, 'It is important to have high contrast between the plume and the background during opacity readings.', 4 FROM quiz
    RETURNING id
)
INSERT INTO lecture_quiz_choices (lecture_quiz_question_id, choice_text, is_correct, order_index)
SELECT * FROM (
    -- Question 1: Air pollution standards minimize... (Particulate matter)
    SELECT (SELECT id FROM q1), 'Water vapor', false, 1
    UNION ALL
    SELECT (SELECT id FROM q1), 'Particulate matter', true, 2
    -- Question 2: White smoke absorbs most of the light (False)
    UNION ALL
    SELECT (SELECT id FROM q2), 'True', false, 1
    UNION ALL
    SELECT (SELECT id FROM q2), 'False', true, 2
    -- Question 3: More particles per volume = higher opacity (True)
    UNION ALL
    SELECT (SELECT id FROM q3), 'True', true, 1
    UNION ALL
    SELECT (SELECT id FROM q3), 'False', false, 2
    -- Question 4: High contrast between plume and background (True)
    UNION ALL
    SELECT (SELECT id FROM q4), 'True', true, 1
    UNION ALL
    SELECT (SELECT id FROM q4), 'False', false, 2
) AS choices(question_id, choice_text, is_correct, order_index);
