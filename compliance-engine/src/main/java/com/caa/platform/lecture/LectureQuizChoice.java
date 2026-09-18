package com.caa.platform.lecture;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;

/**
 * Michael, 2026-09-07 -- real Java entity for lecture_quiz_choices
 * (schema built in migration 044, no Java entity existed until now).
 */
@Entity
@Table(name = "lecture_quiz_choices")
@Getter
@Setter
@NoArgsConstructor
public class LectureQuizChoice {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "lecture_quiz_question_id", nullable = false)
    private LectureQuizQuestion question;

    @Column(name = "choice_text", nullable = false, columnDefinition = "TEXT")
    private String choiceText;

    @Column(name = "is_correct", nullable = false)
    private boolean correct = false;

    @Column(name = "order_index", nullable = false)
    private Integer orderIndex;
}
