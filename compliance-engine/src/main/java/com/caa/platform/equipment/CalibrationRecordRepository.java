package com.caa.platform.equipment;

import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;
import java.util.Optional;

public interface CalibrationRecordRepository extends JpaRepository<CalibrationRecord, Long> {

    /**
     * Most recent calibration record for a system -- used to check whether
     * a CertificationRun is happening under an expired/failed 5-Filter
     * (Section 4h's ComplianceFlag trigger). The full validity check
     * (pass status + 6-month expiry + maintenance-event reset) lives in
     * CalibrationService.isCurrentlyValid, not here -- this repository
     * only fetches the raw record.
     */
    Optional<CalibrationRecord> findFirstByTestingSystemIdOrderByPerformedAtDesc(Long testingSystemId);

    /** Full history, newest first -- for displaying a TestingSystem's real calibration record over time. */
    List<CalibrationRecord> findByTestingSystemIdOrderByPerformedAtDesc(Long testingSystemId);
}
