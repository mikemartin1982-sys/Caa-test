package com.caa.platform.lecture;

import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;

public interface LectureSectionRepository extends JpaRepository<LectureSection, Long> {
    List<LectureSection> findAllByOrderByOrderIndexAsc();
}
