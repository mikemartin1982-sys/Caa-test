-- ============================================================================
-- Lecture quiz seed: Observation Methods section quiz
-- Reference: Michael, 2026-10-05 -- real quiz content and correct answers
-- captured from the live course's Observation Methods quiz page
-- (quiz_section_id=7), where each choice carries data-result="correct"/
-- "incorrect"; matches Michael's screenshot of the passed quiz (all 8
-- correct). Eight questions, choices in the live display order, 70% to pass.
--
-- Live-source fixes: missing terminal periods on questions 1 and 7.
--
-- Same INSERT ... RETURNING CTE pattern as lecture_quiz_introduction.sql.
-- ============================================================================

WITH quiz AS (
    INSERT INTO lecture_quizzes (lecture_section_id, passing_score_percent)
    VALUES ((SELECT id FROM lecture_sections WHERE slug = 'observation-methods'), 70)
    RETURNING id
),
q1 AS (
    INSERT INTO lecture_quiz_questions (lecture_quiz_id, question_text, order_index)
    SELECT id, 'Method 22 should be done for a minimum of six (6) minutes if not specified.', 1 FROM quiz
    RETURNING id
),
q2 AS (
    INSERT INTO lecture_quiz_questions (lecture_quiz_id, question_text, order_index)
    SELECT id, 'A single Method 9 reading is taken:', 2 FROM quiz
    RETURNING id
),
q3 AS (
    INSERT INTO lecture_quiz_questions (lecture_quiz_id, question_text, order_index)
    SELECT id, 'Quick checks are used to determine the presence or absence of an emission:', 3 FROM quiz
    RETURNING id
),
q4 AS (
    INSERT INTO lecture_quiz_questions (lecture_quiz_id, question_text, order_index)
    SELECT id, 'To pass Method 9 certification, no single test point observation can be off by more than:', 4 FROM quiz
    RETURNING id
),
q5 AS (
    INSERT INTO lecture_quiz_questions (lecture_quiz_id, question_text, order_index)
    SELECT id, 'A quick check is an opacity method.', 5 FROM quiz
    RETURNING id
),
q6 AS (
    INSERT INTO lecture_quiz_questions (lecture_quiz_id, question_text, order_index)
    SELECT id, 'Visible emissions observations are:', 6 FROM quiz
    RETURNING id
),
q7 AS (
    INSERT INTO lecture_quiz_questions (lecture_quiz_id, question_text, order_index)
    SELECT id, 'Method 22 evaluates the presence or absence of an emission and reports the duration of an emission if present.', 7 FROM quiz
    RETURNING id
),
q8 AS (
    INSERT INTO lecture_quiz_questions (lecture_quiz_id, question_text, order_index)
    SELECT id, 'In Method 22 observations, if an emission is present, the observer reports the opacity of the emission.', 8 FROM quiz
    RETURNING id
)
INSERT INTO lecture_quiz_choices (lecture_quiz_question_id, choice_text, is_correct, order_index)
SELECT * FROM (
    -- Question 1: Method 22 minimum of six minutes if not specified (True)
    SELECT (SELECT id FROM q1), 'True', true, 1
    UNION ALL
    SELECT (SELECT id FROM q1), 'False', false, 2
    -- Question 2: A single Method 9 reading is taken: (6 minutes / 24 observations)
    UNION ALL
    SELECT (SELECT id FROM q2), 'To detect the presence or absence of an emission', false, 1
    UNION ALL
    SELECT (SELECT id FROM q2), 'Over a 30-minute period at 15 second intervals.', false, 2
    UNION ALL
    SELECT (SELECT id FROM q2), 'Over a 6-minute period using 24 observations.', true, 3
    -- Question 3: Quick checks determine presence or absence (True)
    UNION ALL
    SELECT (SELECT id FROM q3), 'True', true, 1
    UNION ALL
    SELECT (SELECT id FROM q3), 'False', false, 2
    -- Question 4: Method 9 certification single-point limit (15% / 7.5%)
    UNION ALL
    SELECT (SELECT id FROM q4), '15% (and the average difference off by not more than 7.5%)', true, 1
    UNION ALL
    SELECT (SELECT id FROM q4), '10% (and the average difference off by not more than 5%)', false, 2
    UNION ALL
    SELECT (SELECT id FROM q4), '20% (and the average difference off by not more than 10%)', false, 3
    UNION ALL
    SELECT (SELECT id FROM q4), '25% (and the average difference off by not more than 12.5%)', false, 4
    -- Question 5: A quick check is an opacity method. (False)
    UNION ALL
    SELECT (SELECT id FROM q5), 'True', false, 1
    UNION ALL
    SELECT (SELECT id FROM q5), 'False', true, 2
    -- Question 6: Visible emissions observations are: (Subjective)
    UNION ALL
    SELECT (SELECT id FROM q6), 'Subjective', true, 1
    UNION ALL
    SELECT (SELECT id FROM q6), 'Objective', false, 2
    -- Question 7: Method 22 presence/absence and duration (True)
    UNION ALL
    SELECT (SELECT id FROM q7), 'True', true, 1
    UNION ALL
    SELECT (SELECT id FROM q7), 'False', false, 2
    -- Question 8: Method 22 observer reports opacity (False)
    UNION ALL
    SELECT (SELECT id FROM q8), 'True', false, 1
    UNION ALL
    SELECT (SELECT id FROM q8), 'False', true, 2
) AS choices(question_id, choice_text, is_correct, order_index);
