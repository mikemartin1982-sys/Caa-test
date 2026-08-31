package com.caa.platform.certification;

import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;
import java.util.Optional;

public interface ObservationRepository extends JpaRepository<Observation, Long> {
    List<Observation> findByCertificationRunIdAndColorOrderByPointNumber(Long certificationRunId, PlumeColor color);
    List<Observation> findByCertificationRunIdOrderByPointNumber(Long certificationRunId);
    Optional<Observation> findByCertificationRunIdAndPointNumber(Long certificationRunId, Short pointNumber);
    boolean existsByCertificationRunIdAndPointNumber(Long certificationRunId, Short pointNumber);
}
