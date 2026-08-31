package com.caa.platform.student;

import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;
import java.util.Optional;

public interface LectureCertificateRepository extends JpaRepository<LectureCertificate, Long> {

    /** Full history, newest first -- matches Michael's own real CertificationRun screenshot ordering. */
    List<LectureCertificate> findByStudentIdOrderByUploadedAtDesc(Long studentId);

    Optional<LectureCertificate> findByStudentIdAndSupersededFalse(Long studentId);
}
