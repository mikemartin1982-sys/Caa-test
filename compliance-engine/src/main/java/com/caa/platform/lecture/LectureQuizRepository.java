package com.caa.platform.lecture;

import org.springframework.data.jpa.repository.JpaRepository;

import java.util.Optional;

public interface LectureQuizRepository extends JpaRepository<LectureQuiz, Long> {
    Optional<LectureQuiz> findBySectionId(Long lectureSectionId);
}
