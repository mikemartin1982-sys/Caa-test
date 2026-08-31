package com.caa.platform.equipment;

import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;

public interface PaneReadingRepository extends JpaRepository<PaneReading, Long> {
    List<PaneReading> findByCalibrationRecordId(Long calibrationRecordId);
}
