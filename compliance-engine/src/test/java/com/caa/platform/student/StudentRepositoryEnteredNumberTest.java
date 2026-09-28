package com.caa.platform.student;

import org.junit.jupiter.api.Test;

import java.util.Optional;

import static org.assertj.core.api.Assertions.assertThat;
import static org.mockito.ArgumentMatchers.anyString;
import static org.mockito.Mockito.mock;
import static org.mockito.Mockito.never;
import static org.mockito.Mockito.verify;
import static org.mockito.Mockito.when;

/**
 * Michael, 2026-09-28 -- student numbers are plain numbers (migration 051);
 * a typed "S23", "s23" or " 23 " must all find student 23.
 */
class StudentRepositoryEnteredNumberTest {

    private StudentRepository repoWith(Student student) {
        StudentRepository repo = mock(StudentRepository.class);
        when(repo.findByEnteredStudentNumber(anyString())).thenCallRealMethod();
        when(repo.findByStudentNumberIgnoreCase(anyString())).thenReturn(Optional.empty());
        when(repo.findByStudentNumberIgnoreCase("23")).thenReturn(Optional.of(student));
        return repo;
    }

    @Test
    void plainAndSPrefixedNumbersInAnyCaseFindTheSameStudent() {
        Student student = new Student();
        StudentRepository repo = repoWith(student);

        assertThat(repo.findByEnteredStudentNumber("23")).contains(student);
        assertThat(repo.findByEnteredStudentNumber("S23")).contains(student);
        assertThat(repo.findByEnteredStudentNumber("s23")).contains(student);
        assertThat(repo.findByEnteredStudentNumber("  23 ")).contains(student);
    }

    @Test
    void nonNumericSuffixIsNotStrippedAndBlankFindsNothing() {
        StudentRepository repo = repoWith(new Student());

        assertThat(repo.findByEnteredStudentNumber("Smith")).isEmpty();
        verify(repo).findByStudentNumberIgnoreCase("Smith");

        StudentRepository blankRepo = repoWith(new Student());
        assertThat(blankRepo.findByEnteredStudentNumber("   ")).isEmpty();
        verify(blankRepo, never()).findByStudentNumberIgnoreCase(anyString());
    }
}
