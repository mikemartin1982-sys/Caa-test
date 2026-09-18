package com.caa.platform.lecture;

import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;

public interface LecturePageRepository extends JpaRepository<LecturePage, Long> {
    List<LecturePage> findByLectureSectionIdOrderByOrderIndexAsc(Long lectureSectionId);
}
