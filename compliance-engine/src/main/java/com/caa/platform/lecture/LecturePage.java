package com.caa.platform.lecture;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;

import java.time.OffsetDateTime;

/**
 * Michael, 2026-09-06 -- Self-Paced Lecture course shell, migration 044.
 * Individual content pages within a section; orderIndex drives both
 * display order and the real, sequential unlock rule (locked until the
 * previous page in the same section has been read).
 */
@Entity
@Table(name = "lecture_pages")
@Getter
@Setter
@NoArgsConstructor
public class LecturePage {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "lecture_section_id", nullable = false)
    private LectureSection lectureSection;

    @Column(nullable = false)
    private String title;

    @Column(nullable = false)
    private String slug;

    @Column(columnDefinition = "TEXT")
    private String content;

    @Column(name = "order_index", nullable = false)
    private Integer orderIndex;

    @Column(name = "created_at", nullable = false, updatable = false)
    private OffsetDateTime createdAt = OffsetDateTime.now();

    @Column(name = "updated_at")
    private OffsetDateTime updatedAt = OffsetDateTime.now();
}
