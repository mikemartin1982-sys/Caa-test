-- ============================================================================
-- Lecture quiz seed: History section quiz
-- Reference: Michael, 2026-09-28 -- real quiz content and correct answers
-- captured from the live course's History quiz page (quiz_section_id=5),
-- where each choice carries data-result="correct"/"incorrect". Five
-- questions, choices in the live display order, 70% to pass (same as the
-- Introduction quiz).
--
-- Same INSERT ... RETURNING CTE pattern as lecture_quiz_introduction.sql,
-- linking each question to its own choices by real id.
-- ============================================================================

WITH quiz AS (
    INSERT INTO lecture_quizzes (lecture_section_id, passing_score_percent)
    VALUES ((SELECT id FROM lecture_sections WHERE slug = 'history'), 70)
    RETURNING id
),
q1 AS (
    INSERT INTO lecture_quiz_questions (lecture_quiz_id, question_text, order_index)
    SELECT id, 'Why was the Ringelmann Scale important in controlling air pollution?', 1 FROM quiz
    RETURNING id
),
q2 AS (
    INSERT INTO lecture_quiz_questions (lecture_quiz_id, question_text, order_index)
    SELECT id, 'A Ringelmann number of one (1) equates to:', 2 FROM quiz
    RETURNING id
),
q3 AS (
    INSERT INTO lecture_quiz_questions (lecture_quiz_id, question_text, order_index)
    SELECT id, 'The Ringelmann smoke charts work with these smoke colors:', 3 FROM quiz
    RETURNING id
),
q4 AS (
    INSERT INTO lecture_quiz_questions (lecture_quiz_id, question_text, order_index)
    SELECT id, 'The first smoke school was held in what state?', 4 FROM quiz
    RETURNING id
),
q5 AS (
    INSERT INTO lecture_quiz_questions (lecture_quiz_id, question_text, order_index)
    SELECT id, 'What federal agency develops and enforces clean air regulation?', 5 FROM quiz
    RETURNING id
)
INSERT INTO lecture_quiz_choices (lecture_quiz_question_id, choice_text, is_correct, order_index)
SELECT * FROM (
    -- Question 1: Why was the Ringelmann Scale important? (All of the above)
    SELECT (SELECT id FROM q1), 'It created a standard for measuring the amount of air pollution', false, 1
    UNION ALL
    SELECT (SELECT id FROM q1), 'It provided a mechanism that could be used throughout the USA', false, 2
    UNION ALL
    SELECT (SELECT id FROM q1), 'It was more effective and enforceable than using terms like excessive and nuisance', false, 3
    UNION ALL
    SELECT (SELECT id FROM q1), 'All of the above', true, 4
    -- Question 2: A Ringelmann number of one (1) equates to: (20%)
    UNION ALL
    SELECT (SELECT id FROM q2), '20%', true, 1
    UNION ALL
    SELECT (SELECT id FROM q2), '10%', false, 2
    UNION ALL
    SELECT (SELECT id FROM q2), '80%', false, 3
    UNION ALL
    SELECT (SELECT id FROM q2), '90%', false, 4
    -- Question 3: The Ringelmann smoke charts work with these smoke colors: (Black)
    UNION ALL
    SELECT (SELECT id FROM q3), 'Both', false, 1
    UNION ALL
    SELECT (SELECT id FROM q3), 'Black', true, 2
    UNION ALL
    SELECT (SELECT id FROM q3), 'White', false, 3
    -- Question 4: The first smoke school was held in what state? (California)
    UNION ALL
    SELECT (SELECT id FROM q4), 'New York', false, 1
    UNION ALL
    SELECT (SELECT id FROM q4), 'California', true, 2
    UNION ALL
    SELECT (SELECT id FROM q4), 'Pennsylvania', false, 3
    UNION ALL
    SELECT (SELECT id FROM q4), 'Minnesota', false, 4
    -- Question 5: What federal agency develops and enforces clean air regulation? (EPA)
    UNION ALL
    SELECT (SELECT id FROM q5), 'Department of National Resources', false, 1
    UNION ALL
    SELECT (SELECT id FROM q5), 'Environmental Protection Agency (EPA)', true, 2
) AS choices(question_id, choice_text, is_correct, order_index);
