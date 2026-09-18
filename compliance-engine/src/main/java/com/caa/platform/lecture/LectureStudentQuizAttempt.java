package com.caa.platform.lecture;

import com.caa.platform.student.Student;
import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;

import java.time.OffsetDateTime;

/**
 * Michael, 2026-09-07 -- real Java entity for lecture_student_quiz_attempts
 * (schema built in migration 044, no Java entity existed until now). The
 * real, permanent record of a completed attempt -- written once every
 * question in a quiz has a real, in-progress answer
 * (LectureStudentQuizInProgressAnswer, migration 046) and the final
 * score/pass-fail has been computed.
 */
@Entity
@Table(name = "lecture_student_quiz_attempts")
@Getter
@Setter
@NoArgsConstructor
public class LectureStudentQuizAttempt {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "student_id", nullable = false)
    private Student student;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "lecture_quiz_id", nullable = false)
    private LectureQuiz quiz;

    @Column(name = "score_percent", nullable = false)
    private Integer scorePercent;

    @Column(nullable = false)
    private boolean passed;

    @Column(name = "attempted_at", nullable = false)
    private OffsetDateTime attemptedAt = OffsetDateTime.now();
}
