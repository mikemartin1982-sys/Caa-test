package com.caa.platform.lecture;

import com.caa.platform.student.Student;
import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;

import java.time.OffsetDateTime;

/**
 * Michael, 2026-09-07 -- the real, in-progress quiz answer (migration
 * 046). Corrects a real gap in migration 044's own design, which
 * incorrectly assumed mid-quiz saving was out of scope. One row per
 * student per question currently being worked on -- cleared once the
 * quiz is completed and a real, permanent LectureStudentQuizAttempt is
 * written instead.
 */
@Entity
@Table(name = "lecture_student_quiz_in_progress_answers", uniqueConstraints = @UniqueConstraint(columnNames = {"student_id", "lecture_quiz_question_id"}))
@Getter
@Setter
@NoArgsConstructor
public class LectureStudentQuizInProgressAnswer {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "student_id", nullable = false)
    private Student student;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "lecture_quiz_question_id", nullable = false)
    private LectureQuizQuestion question;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "lecture_quiz_choice_id", nullable = false)
    private LectureQuizChoice choice;

    @Column(name = "answered_at", nullable = false)
    private OffsetDateTime answeredAt = OffsetDateTime.now();
}
