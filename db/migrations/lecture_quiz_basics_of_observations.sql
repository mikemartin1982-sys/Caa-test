-- ============================================================================
-- Lecture quiz seed: Basics of Observations section quiz
-- Reference: Michael, 2026-10-05 -- real quiz content and correct answers
-- captured from the live course's Basics of Observations quiz page
-- (quiz_section_id=9), where each choice carries data-result="correct"/
-- "incorrect"; matches Michael's screenshot of the passed quiz (all 4
-- correct). Four questions, choices in the live display order, 70% to pass.
--
-- Live-source fixes: "A Method 9 observations requires" -> "A Method 9
-- observation requires"; missing terminal period on question 4.
--
-- Same INSERT ... RETURNING CTE pattern as lecture_quiz_introduction.sql.
-- ============================================================================

WITH quiz AS (
    INSERT INTO lecture_quizzes (lecture_section_id, passing_score_percent)
    VALUES ((SELECT id FROM lecture_sections WHERE slug = 'basics-of-observations'), 70)
    RETURNING id
),
q1 AS (
    INSERT INTO lecture_quiz_questions (lecture_quiz_id, question_text, order_index)
    SELECT id, 'A Method 9 observation requires the sun''s position to be:', 1 FROM quiz
    RETURNING id
),
q2 AS (
    INSERT INTO lecture_quiz_questions (lecture_quiz_id, question_text, order_index)
    SELECT id, 'At what distance should a Method 9 observer perform a reading?', 2 FROM quiz
    RETURNING id
),
q3 AS (
    INSERT INTO lecture_quiz_questions (lecture_quiz_id, question_text, order_index)
    SELECT id, 'Opacity readings should be performed when there is red in the sky.', 3 FROM quiz
    RETURNING id
),
q4 AS (
    INSERT INTO lecture_quiz_questions (lecture_quiz_id, question_text, order_index)
    SELECT id, 'The observer''s line of sight should be approximately perpendicular to the track of the plume.', 4 FROM quiz
    RETURNING id
)
INSERT INTO lecture_quiz_choices (lecture_quiz_question_id, choice_text, is_correct, order_index)
SELECT * FROM (
    -- Question 1: Sun position (Behind the observer)
    SELECT (SELECT id FROM q1), 'Behind the observer', true, 1
    UNION ALL
    SELECT (SELECT id FROM q1), 'Behind the plume', false, 2
    UNION ALL
    SELECT (SELECT id FROM q1), 'At the highest point in the sky (i.e., noon)', false, 3
    -- Question 2: Observer distance (Approximately three stack heights)
    UNION ALL
    SELECT (SELECT id FROM q2), 'One quarter (1/4) of a mile', false, 1
    UNION ALL
    SELECT (SELECT id FROM q2), 'As close as possible', false, 2
    UNION ALL
    SELECT (SELECT id FROM q2), 'Approximately three (3) stack heights', true, 3
    UNION ALL
    SELECT (SELECT id FROM q2), 'Observer distance doesn''t matter in visible emission readings', false, 4
    -- Question 3: Read when there is red in the sky (False)
    UNION ALL
    SELECT (SELECT id FROM q3), 'True', false, 1
    UNION ALL
    SELECT (SELECT id FROM q3), 'False', true, 2
    -- Question 4: Line of sight perpendicular to the plume (True)
    UNION ALL
    SELECT (SELECT id FROM q4), 'True', true, 1
    UNION ALL
    SELECT (SELECT id FROM q4), 'False', false, 2
) AS choices(question_id, choice_text, is_correct, order_index);
