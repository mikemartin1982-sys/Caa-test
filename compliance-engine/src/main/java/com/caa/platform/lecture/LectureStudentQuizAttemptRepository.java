package com.caa.platform.lecture;

import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;
import java.util.Optional;

public interface LectureStudentQuizAttemptRepository extends JpaRepository<LectureStudentQuizAttempt, Long> {
    List<LectureStudentQuizAttempt> findByStudentIdAndQuizId(Long studentId, Long lectureQuizId);

    /**
     * Michael, 2026-09-07 -- used to decide whether a section's quiz has
     * genuinely been passed at least once (for the real section-unlock
     * rule and the Quiz Summary Page's own "answers correct" display) --
     * a student may have a real, earlier failed attempt on record too,
     * so this specifically looks for any attempt with passed = true,
     * not just the most recent one.
     */
    Optional<LectureStudentQuizAttempt> findFirstByStudentIdAndQuizIdAndPassedTrue(Long studentId, Long lectureQuizId);
}
