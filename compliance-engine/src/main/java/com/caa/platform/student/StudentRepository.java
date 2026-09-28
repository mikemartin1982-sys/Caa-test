package com.caa.platform.student;

import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;
import java.util.Optional;

public interface StudentRepository extends JpaRepository<Student, Long> {
    Optional<Student> findByStudentNumber(String studentNumber);
    Optional<Student> findByStudentNumberIgnoreCase(String studentNumber);

    /**
     * Michael, 2026-09-28 -- lookup for a number a person typed in (lecture
     * sign-in, public cert lookup). Student numbers are plain numbers now
     * (migration 051), but older printouts show "S123", so a leading S/s
     * followed by digits is dropped; the match itself ignores case.
     */
    default Optional<Student> findByEnteredStudentNumber(String entered) {
        if (entered == null || entered.isBlank()) {
            return Optional.empty();
        }
        String number = entered.trim();
        if (number.matches("[Ss]\\d+")) {
            number = number.substring(1);
        }
        return findByStudentNumberIgnoreCase(number);
    }
    List<Student> findByEmployerClientId(Long clientId);
}
