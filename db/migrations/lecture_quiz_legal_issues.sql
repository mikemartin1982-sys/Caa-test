-- ============================================================================
-- Lecture quiz seed: Legal Issues section quiz
-- Reference: Michael, 2026-09-28 -- real quiz content and correct answers
-- captured from the live course's Legal Issues quiz page
-- (quiz_section_id=6), where each choice carries data-result="correct"/
-- "incorrect"; matches Michael's screenshot of the passed quiz. Four
-- questions, choices in the live display order, 70% to pass.
--
-- Live-source typos fixed: "Court subpeona" -> "Court subpoena"; a double
-- space in question 3.
--
-- Same INSERT ... RETURNING CTE pattern as lecture_quiz_introduction.sql.
-- ============================================================================

WITH quiz AS (
    INSERT INTO lecture_quizzes (lecture_section_id, passing_score_percent)
    VALUES ((SELECT id FROM lecture_sections WHERE slug = 'legal-issues'), 70)
    RETURNING id
),
q1 AS (
    INSERT INTO lecture_quiz_questions (lecture_quiz_id, question_text, order_index)
    SELECT id, 'You are personally liable for inaccurate opacity readings.', 1 FROM quiz
    RETURNING id
),
q2 AS (
    INSERT INTO lecture_quiz_questions (lecture_quiz_id, question_text, order_index)
    SELECT id, 'The most common method of enforcement of air quality regulations is:', 2 FROM quiz
    RETURNING id
),
q3 AS (
    INSERT INTO lecture_quiz_questions (lecture_quiz_id, question_text, order_index)
    SELECT id, 'If you intentionally falsely document an opacity reading, you can be liable for fraud.', 3 FROM quiz
    RETURNING id
),
q4 AS (
    INSERT INTO lecture_quiz_questions (lecture_quiz_id, question_text, order_index)
    SELECT id, 'Why is it important to complete the VEO form correctly?', 4 FROM quiz
    RETURNING id
)
INSERT INTO lecture_quiz_choices (lecture_quiz_question_id, choice_text, is_correct, order_index)
SELECT * FROM (
    -- Question 1: You are personally liable for inaccurate opacity readings. (False)
    SELECT (SELECT id FROM q1), 'True', false, 1
    UNION ALL
    SELECT (SELECT id FROM q1), 'False', true, 2
    -- Question 2: The most common method of enforcement... (Notice of violation)
    UNION ALL
    SELECT (SELECT id FROM q2), 'Notice of violation', true, 1
    UNION ALL
    SELECT (SELECT id FROM q2), 'License revocation', false, 2
    UNION ALL
    SELECT (SELECT id FROM q2), 'Court subpoena', false, 3
    -- Question 3: If you intentionally falsely document... (True)
    UNION ALL
    SELECT (SELECT id FROM q3), 'True', true, 1
    UNION ALL
    SELECT (SELECT id FROM q3), 'False', false, 2
    -- Question 4: Why is it important to complete the VEO form correctly? (All of the above)
    UNION ALL
    SELECT (SELECT id FROM q4), 'To be in compliance with Method 9.', false, 1
    UNION ALL
    SELECT (SELECT id FROM q4), 'To avoid fines for your company.', false, 2
    UNION ALL
    SELECT (SELECT id FROM q4), 'The VEO form is a legal form.', false, 3
    UNION ALL
    SELECT (SELECT id FROM q4), 'All of the above', true, 4
) AS choices(question_id, choice_text, is_correct, order_index);
