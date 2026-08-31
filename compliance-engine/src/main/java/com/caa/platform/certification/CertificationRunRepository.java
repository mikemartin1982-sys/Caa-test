package com.caa.platform.certification;

import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;

public interface CertificationRunRepository extends JpaRepository<CertificationRun, Long> {
    List<CertificationRun> findBySessionIdOrderByRunNumber(Long sessionId);
}
