package com.caa.platform.lecture;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;

import java.time.OffsetDateTime;

/**
 * Michael, 2026-09-07 -- Self-Paced Lecture Resources (migration 045).
 * Deliberately separate from LecturePage -- Resources content (Glossary,
 * FAQs, Abbreviations, VEO Resources, Bibliography) has no section, no
 * quiz, no sequential unlock, and no per-student "read" progress at all,
 * matching the real, live course's own confirmed behavior.
 */
@Entity
@Table(name = "lecture_resource_pages")
@Getter
@Setter
@NoArgsConstructor
public class LectureResourcePage {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Column(nullable = false)
    private String title;

    @Column(nullable = false, unique = true)
    private String slug;

    @Column(columnDefinition = "TEXT")
    private String content;

    @Column(name = "order_index", nullable = false, unique = true)
    private Integer orderIndex;

    @Column(name = "created_at", nullable = false, updatable = false)
    private OffsetDateTime createdAt = OffsetDateTime.now();

    @Column(name = "updated_at")
    private OffsetDateTime updatedAt = OffsetDateTime.now();
}
