package com.caa.platform.equipment;

import com.caa.platform.certification.CertificationRun;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.math.BigDecimal;
import java.time.OffsetDateTime;
import java.util.List;
import java.util.Optional;

/**
 * Section 4h: the 5-Filter calibration -- 3 panes x 5 readings each,
 * tolerance +/-3% from each pane's certified opacity value, plus the six
 * EPA Method 9 Section 3.1.2 parameters. ANY one parameter or reading
 * failing invalidates the whole 5-Filter, even if the rest pass.
 *
 * Enforcement is "flag for audit review, not a real-time block" -- an
 * expired/failed calibration never stops a CertificationRun from
 * happening; it raises a ComplianceFlag that must be resolved once staff
 * are aware (Section 4h).
 */
@Service
public class CalibrationService {

    private static final BigDecimal PANE_TOLERANCE_PERCENT = new BigDecimal("3.00");
    private static final int VALID_MONTHS = 6;

    private final CalibrationRecordRepository calibrationRecordRepository;
    private final PaneReadingRepository paneReadingRepository;
    private final CalibrationPaneRepository calibrationPaneRepository;
    private final ComplianceFlagRepository complianceFlagRepository;
    private final MaintenanceEventRepository maintenanceEventRepository;

    public CalibrationService(CalibrationRecordRepository calibrationRecordRepository,
                               PaneReadingRepository paneReadingRepository,
                               CalibrationPaneRepository calibrationPaneRepository,
                               ComplianceFlagRepository complianceFlagRepository,
                               MaintenanceEventRepository maintenanceEventRepository) {
        this.calibrationRecordRepository = calibrationRecordRepository;
        this.paneReadingRepository = paneReadingRepository;
        this.calibrationPaneRepository = calibrationPaneRepository;
        this.complianceFlagRepository = complianceFlagRepository;
        this.maintenanceEventRepository = maintenanceEventRepository;
    }

    public record PaneReadingInput(Long calibrationPaneId, short sequenceNumber, BigDecimal recordedValue) {}

    public record CreateCalibrationRequest(
            TestingSystem testingSystem, CalibrationTrigger triggerReason,
            boolean lightSourceVoltagePass, boolean photocellSpectralResponsePass, boolean angleOfViewPass,
            boolean angleOfProjectionPass, boolean calibrationErrorPass, boolean responseTimePass,
            List<PaneReadingInput> paneReadings) {}

    /**
     * Records a 5-Filter event: scores each of the 15 pane readings
     * (3 panes x 5, Section 4h) against +/-3% tolerance, combines that
     * with the six EPA parameters, and sets overallPass only if every
     * single one of those 21 checks passes.
     */
    @Transactional
    public CalibrationRecord createCalibrationRecord(CreateCalibrationRequest req) {
        CalibrationRecord record = new CalibrationRecord();
        record.setTestingSystem(req.testingSystem());
        record.setPerformedAt(OffsetDateTime.now());
        record.setTriggerReason(req.triggerReason());
        record.setLightSourceVoltagePass(req.lightSourceVoltagePass());
        record.setPhotocellSpectralResponsePass(req.photocellSpectralResponsePass());
        record.setAngleOfViewPass(req.angleOfViewPass());
        record.setAngleOfProjectionPass(req.angleOfProjectionPass());
        record.setCalibrationErrorPass(req.calibrationErrorPass());
        record.setResponseTimePass(req.responseTimePass());

        boolean sixParamsPass = req.lightSourceVoltagePass() && req.photocellSpectralResponsePass()
                && req.angleOfViewPass() && req.angleOfProjectionPass()
                && req.calibrationErrorPass() && req.responseTimePass();

        record.setExpiresAt(OffsetDateTime.now().plusMonths(VALID_MONTHS));

        CalibrationRecord savedRecord = calibrationRecordRepository.save(record);

        boolean allPanesWithinTolerance = true;
        for (PaneReadingInput input : req.paneReadings()) {
            CalibrationPane pane = calibrationPaneRepository.findById(input.calibrationPaneId())
                    .orElseThrow(() -> new IllegalArgumentException("CalibrationPane not found: " + input.calibrationPaneId()));

            BigDecimal deviation = input.recordedValue().subtract(pane.getCertifiedOpacityValue()).abs();
            boolean withinTolerance = deviation.compareTo(PANE_TOLERANCE_PERCENT) <= 0;
            allPanesWithinTolerance &= withinTolerance;

            PaneReading reading = new PaneReading();
            reading.setCalibrationRecord(savedRecord);
            reading.setCalibrationPane(pane);
            reading.setSequenceNumber(input.sequenceNumber());
            reading.setRecordedValue(input.recordedValue());
            reading.setDeviation(deviation);
            reading.setWithinTolerance(withinTolerance);
            paneReadingRepository.save(reading);
        }

        savedRecord.setOverallPass(sixParamsPass && allPanesWithinTolerance);
        return calibrationRecordRepository.save(savedRecord);
    }

    /**
     * Section 4h: a system is valid only if its most recent 5-Filter (a)
     * passed overall, (b) hasn't hit its 6-month expiry, AND (c) has no
     * Significant Repair or Replace event dated on/after that calibration
     * -- either kind of maintenance event resets the clock immediately,
     * independent of the 6-month schedule.
     */
    public boolean isCurrentlyValid(Long testingSystemId) {
        Optional<CalibrationRecord> latest = calibrationRecordRepository
                .findFirstByTestingSystemIdOrderByPerformedAtDesc(testingSystemId);
        if (latest.isEmpty()) {
            return false;
        }
        CalibrationRecord record = latest.get();

        boolean passedAndNotExpired = record.isOverallPass()
                && record.getExpiresAt() != null
                && record.getExpiresAt().isAfter(OffsetDateTime.now());
        if (!passedAndNotExpired) {
            return false;
        }

        boolean maintenanceEventSinceCalibration = maintenanceEventRepository
                .findByTestingSystemIdOrderByEventDateDesc(testingSystemId).stream()
                .anyMatch(event -> !event.getEventDate().isBefore(record.getPerformedAt().toLocalDate()));

        return !maintenanceEventSinceCalibration;
    }

    /**
     * Section 4h enforcement: called after a CertificationRun is
     * administered. Never blocks the run -- only raises a ComplianceFlag
     * if the system's calibration was expired/failed/reset-by-maintenance
     * at the time. Auto-invoked from CertificationRunController's
     * create() flow; also callable directly via
     * POST /equipment/compliance-check/{runId} for a manual re-check.
     */
    @Transactional
    public ComplianceFlag checkAndFlagIfNeeded(CertificationRun run) {
        Long systemId = run.getTestingSystem().getId();
        if (isCurrentlyValid(systemId)) {
            return null;
        }

        ComplianceFlag flag = new ComplianceFlag();
        flag.setTestingSystem(run.getTestingSystem());
        flag.setReason(calibrationRecordRepository.findFirstByTestingSystemIdOrderByPerformedAtDesc(systemId).isEmpty()
                ? "5filter_never_performed" : "5filter_expired_or_failed");
        flag.setRaisedAt(OffsetDateTime.now());
        flag.setStatus(ComplianceFlagStatus.OPEN);
        flag.getAffectedRuns().add(run);

        return complianceFlagRepository.save(flag);
    }
}
