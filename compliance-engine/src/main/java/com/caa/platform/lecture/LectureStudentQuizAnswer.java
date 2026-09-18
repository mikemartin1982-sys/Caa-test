package com.caa.platform.lecture;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;

/**
 * Michael, 2026-09-07 -- real Java entity for lecture_student_quiz_answers
 * (schema built in migration 044, no Java entity existed until now). The
 * real, permanent, per-question record of what a student actually
 * answered within one specific completed attempt -- distinct from
 * LectureStudentQuizInProgressAnswer (migration 046), which only tracks
 * the current, working answer before the attempt is complete.
 */
@Entity
@Table(name = "lecture_student_quiz_answers", uniqueConstraints = @UniqueConstraint(columnNames = {"lecture_student_quiz_attempt_id", "lecture_quiz_question_id"}))
@Getter
@Setter
@NoArgsConstructor
public class LectureStudentQuizAnswer {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "lecture_student_quiz_attempt_id", nullable = false)
    private LectureStudentQuizAttempt attempt;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "lecture_quiz_question_id", nullable = false)
    private LectureQuizQuestion question;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "lecture_quiz_choice_id", nullable = false)
    private LectureQuizChoice choice;
}
