package com.caa.platform.equipment;

import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;

public interface CalibrationPaneRepository extends JpaRepository<CalibrationPane, Long> {
    List<CalibrationPane> findByTrailerId(Long trailerId);

    /** Michael, 2026-08-31 -- Truck/Trailer Equipment feature. paneIdentifier is a unique DB column -- same reasoning as Truck/Trailer's existsByIdentifier(). */
    boolean existsByPaneIdentifier(String paneIdentifier);
    boolean existsByPaneIdentifierAndIdNot(String paneIdentifier, Long id);
}
