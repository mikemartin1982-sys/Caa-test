package com.caa.platform.lecture;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;

import java.time.OffsetDateTime;

/**
 * Michael, 2026-09-07 -- real Java entity for the lecture_quizzes table
 * (schema built in migration 044, but no Java entity existed for it at
 * all until now -- only LectureSection/LecturePage/
 * LectureStudentPageProgress were needed for the earlier shell phase).
 */
@Entity
@Table(name = "lecture_quizzes")
@Getter
@Setter
@NoArgsConstructor
public class LectureQuiz {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "lecture_section_id", nullable = false, unique = true)
    private LectureSection section;

    @Column(name = "passing_score_percent", nullable = false)
    private Integer passingScorePercent = 70;

    @Column(name = "created_at", nullable = false, updatable = false)
    private OffsetDateTime createdAt = OffsetDateTime.now();

    @Column(name = "updated_at")
    private OffsetDateTime updatedAt = OffsetDateTime.now();
}
