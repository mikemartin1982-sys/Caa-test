package com.caa.platform.certification;

import org.springframework.data.jpa.repository.JpaRepository;

import java.util.Optional;

public interface CertificationRepository extends JpaRepository<Certification, Long> {
    Optional<Certification> findByEnrollmentId(Long enrollmentId);
}
