package com.caa.platform.lecture;

import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;

public interface LectureStudentPageProgressRepository extends JpaRepository<LectureStudentPageProgress, Long> {
    List<LectureStudentPageProgress> findByStudentId(Long studentId);
    boolean existsByStudentIdAndLecturePageId(Long studentId, Long lecturePageId);
}
