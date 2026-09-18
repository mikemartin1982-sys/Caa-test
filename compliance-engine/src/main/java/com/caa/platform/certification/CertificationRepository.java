package com.caa.platform.certification;

import org.springframework.data.jpa.repository.JpaRepository;

import java.util.Optional;

public interface CertificationRepository extends JpaRepository<Certification, Long> {
    Optional<Certification> findByEnrollmentId(Long enrollmentId);

    /**
     * Michael, 2026-09-04 -- Public Certificate Lookup. Certification
     * has no direct Student association at all (only via Enrollment),
     * so this traverses Certification -> Enrollment -> Student. Every
     * real Certification row only ever exists for a genuine pass
     * (CertificationDeterminationService only creates one "if so"), so
     * no separate pass/fail filter is needed -- the most recent by
     * issueDate is, by definition, the most recent real pass.
     */
    Optional<Certification> findTopByEnrollment_Student_IdOrderByIssueDateDesc(Long studentId);
}
