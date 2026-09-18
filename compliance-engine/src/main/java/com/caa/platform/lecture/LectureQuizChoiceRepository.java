package com.caa.platform.lecture;

import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;

public interface LectureQuizChoiceRepository extends JpaRepository<LectureQuizChoice, Long> {
    List<LectureQuizChoice> findByQuestionIdOrderByOrderIndexAsc(Long lectureQuizQuestionId);
}
