package com.caa.platform.lecture;

import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;

public interface LectureQuizQuestionRepository extends JpaRepository<LectureQuizQuestion, Long> {
    List<LectureQuizQuestion> findByQuizIdOrderByOrderIndexAsc(Long lectureQuizId);
}
