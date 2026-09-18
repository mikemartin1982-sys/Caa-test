package com.caa.platform.lecture;

import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;
import java.util.Optional;

public interface LectureStudentQuizInProgressAnswerRepository extends JpaRepository<LectureStudentQuizInProgressAnswer, Long> {
    List<LectureStudentQuizInProgressAnswer> findByStudentIdAndQuestion_QuizId(Long studentId, Long lectureQuizId);
    Optional<LectureStudentQuizInProgressAnswer> findByStudentIdAndQuestionId(Long studentId, Long lectureQuizQuestionId);
    void deleteByStudentIdAndQuestion_QuizId(Long studentId, Long lectureQuizId);
}
