package com.caa.platform.lecture;

import com.caa.platform.student.Student;
import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;

import java.time.OffsetDateTime;

/**
 * Michael, 2026-09-06 -- Self-Paced Lecture course shell, migration 044.
 * The real "don't lose progress if they step away" record for page reads
 * -- one row per student per page, written the moment a page is marked
 * read. Deliberately NOT the permanent record; see Student's own
 * lectureComplete/lectureCompletionDate/lectureCompletionSource for that.
 */
@Entity
@Table(name = "lecture_student_page_progress", uniqueConstraints = @UniqueConstraint(columnNames = {"student_id", "lecture_page_id"}))
@Getter
@Setter
@NoArgsConstructor
public class LectureStudentPageProgress {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "student_id", nullable = false)
    private Student student;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "lecture_page_id", nullable = false)
    private LecturePage lecturePage;

    @Column(name = "read_at", nullable = false)
    private OffsetDateTime readAt = OffsetDateTime.now();
}
