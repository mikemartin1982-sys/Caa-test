package com.caa.platform.equipment;

import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;

public interface DailyCalibrationCheckRepository extends JpaRepository<DailyCalibrationCheck, Long> {
    List<DailyCalibrationCheck> findByTestingSystemId(Long testingSystemId);
}
