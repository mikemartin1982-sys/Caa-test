package com.caa.platform.equipment;

import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;

public interface CalibrationPaneRepository extends JpaRepository<CalibrationPane, Long> {
    List<CalibrationPane> findByTrailerId(Long trailerId);

    /**
     * Michael, 2026-08-31 -- Calibration Pane editing. paneIdentifier
     * is a unique DB column -- checked explicitly so editing it to a
     * value already used by a different pane produces a clean 409, not
     * a raw, unhandled SQL exception. Same reasoning and same pattern
     * as this project's other existsBy...AndIdNot() checks (Truck/
     * Trailer identifier, TestingSystem, etc.).
     */
    boolean existsByPaneIdentifierAndIdNot(String paneIdentifier, Long id);
}
