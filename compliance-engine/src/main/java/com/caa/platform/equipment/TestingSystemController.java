package com.caa.platform.equipment;

import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

import java.time.LocalDate;
import java.util.List;

/**
 * Section 4h: Primary/Secondary TestingSystems per Trailer, and
 * MaintenanceEvents (Significant Repair / Replace) which trigger a new
 * 5-Filter requirement independent of the standard 6-month schedule.
 */
@RestController
@RequestMapping("/api/v1/testing-systems")
public class TestingSystemController {

    private final TestingSystemRepository testingSystemRepository;
    private final TrailerRepository trailerRepository;
    private final MaintenanceEventRepository maintenanceEventRepository;
    private final CalibrationService calibrationService;
    private final ChartRecorderImportService chartRecorderImportService;
    private final CalibrationPaneRepository calibrationPaneRepository;

    public TestingSystemController(TestingSystemRepository testingSystemRepository,
                                    TrailerRepository trailerRepository,
                                    MaintenanceEventRepository maintenanceEventRepository,
                                    CalibrationService calibrationService,
                                    ChartRecorderImportService chartRecorderImportService,
                                    CalibrationPaneRepository calibrationPaneRepository) {
        this.testingSystemRepository = testingSystemRepository;
        this.trailerRepository = trailerRepository;
        this.maintenanceEventRepository = maintenanceEventRepository;
        this.calibrationService = calibrationService;
        this.chartRecorderImportService = chartRecorderImportService;
        this.calibrationPaneRepository = calibrationPaneRepository;
    }

    @GetMapping
    public ResponseEntity<List<TestingSystem>> listByTrailer(@RequestParam Long trailerId) {
        return ResponseEntity.ok(testingSystemRepository.findByTrailerId(trailerId));
    }

    public record CreateSystemRequest(Long trailerId, SystemDesignation designation, String lightSourceId,
                                       String photoCellId, String opAmpCardId, String dataSourceId, String monitorId) {}

    @PostMapping
    public ResponseEntity<TestingSystem> create(@RequestBody CreateSystemRequest req) {
        var trailer = trailerRepository.findById(req.trailerId())
                .orElseThrow(() -> new IllegalArgumentException("Trailer not found: " + req.trailerId()));

        TestingSystem system = new TestingSystem();
        system.setTrailer(trailer);
        system.setDesignation(req.designation());
        system.setLightSourceId(req.lightSourceId());
        system.setPhotoCellId(req.photoCellId());
        system.setOpAmpCardId(req.opAmpCardId());
        system.setDataSourceId(req.dataSourceId());
        system.setMonitorId(req.monitorId());
        return ResponseEntity.status(HttpStatus.CREATED).body(testingSystemRepository.save(system));
    }

    /** Section 4h: whether this system's 5-Filter is currently valid (not expired, last result passing). */
    @GetMapping("/{systemId}/calibration-validity")
    public ResponseEntity<java.util.Map<String, Boolean>> calibrationValidity(@PathVariable Long systemId) {
        return ResponseEntity.ok(java.util.Map.of("valid", calibrationService.isCurrentlyValid(systemId)));
    }

    public record MaintenanceEventRequest(MaintenanceEventType eventType, MaintenanceComponent componentAffected, LocalDate eventDate) {}

    /**
     * Section 4h: recording a Significant Repair or Replace here
     * immediately invalidates the system's current 5-Filter, independent
     * of the standard 6-month schedule -- enforced in
     * CalibrationService.isCurrentlyValid, which checks for any
     * maintenance event dated on/after the last calibration's date.
     */
    @PostMapping("/{systemId}/maintenance-events")
    public ResponseEntity<MaintenanceEvent> recordMaintenanceEvent(@PathVariable Long systemId, @RequestBody MaintenanceEventRequest req) {
        var system = testingSystemRepository.findById(systemId)
                .orElseThrow(() -> new IllegalArgumentException("TestingSystem not found: " + systemId));

        MaintenanceEvent event = new MaintenanceEvent();
        event.setTestingSystem(system);
        event.setEventType(req.eventType());
        event.setComponentAffected(req.componentAffected());
        event.setEventDate(req.eventDate() != null ? req.eventDate() : LocalDate.now());
        return ResponseEntity.status(HttpStatus.CREATED).body(maintenanceEventRepository.save(event));
    }

    @GetMapping("/{systemId}/maintenance-events")
    public ResponseEntity<List<MaintenanceEvent>> listMaintenanceEvents(@PathVariable Long systemId) {
        return ResponseEntity.ok(maintenanceEventRepository.findByTestingSystemIdOrderByEventDateDesc(systemId));
    }

    /**
     * Michael, 2026-08-31 -- Truck/Trailer Equipment feature, 5-Filter
     * import. Parse-and-preview only -- nothing persisted here.
     * Returns the parsed Chart Recorder data alongside this system's
     * real, actual CalibrationPanes (via its parent Trailer), so the
     * reviewer can confirm which real pane is Low/Medium/High
     * themselves before submitting -- confirmed with Michael as the
     * right call rather than an automatic, potentially-wrong match.
     */
    public record ImportPreviewResponse(ChartRecorderImportService.ParsedCalibrationImport parsed, List<CalibrationPane> trailerPanes, Long trailerId) {}

    @PostMapping("/{systemId}/calibration-records/import-preview")
    @org.springframework.transaction.annotation.Transactional(readOnly = true)
    public ResponseEntity<?> previewImport(@PathVariable Long systemId, @RequestParam("file") org.springframework.web.multipart.MultipartFile file) {
        TestingSystem system = testingSystemRepository.findById(systemId)
                .orElseThrow(() -> new IllegalArgumentException("TestingSystem not found: " + systemId));

        try {
            ChartRecorderImportService.ParsedCalibrationImport parsed = chartRecorderImportService.parse(file);
            List<CalibrationPane> trailerPanes = calibrationPaneRepository.findByTrailerId(system.getTrailer().getId());
            // Michael, 2026-08-31 -- same lazy-serialization fix
            // already learned and applied elsewhere tonight:
            // @Transactional(readOnly = true) alone doesn't force
            // these lazy fields to actually load before Jackson
            // serializes them.
            trailerPanes.forEach(p -> org.hibernate.Hibernate.initialize(p.getTrailer()));
            return ResponseEntity.ok(new ImportPreviewResponse(parsed, trailerPanes, system.getTrailer().getId()));
        } catch (IllegalArgumentException e) {
            return ResponseEntity.status(HttpStatus.CONFLICT).body(java.util.Map.of("error", e.getMessage()));
        } catch (java.io.IOException e) {
            return ResponseEntity.status(HttpStatus.INTERNAL_SERVER_ERROR)
                    .body(java.util.Map.of("error", "Could not read the uploaded file: " + e.getMessage()));
        }
    }

    /**
     * Michael, 2026-08-31 -- the actual submit, after the reviewer has
     * confirmed the pane matches and entered their own six pass/fail
     * judgments for the named EPA parameters. This does no new logic
     * of its own -- CalibrationService.createCalibrationRecord()
     * already exists, already does the real work (15 pane-reading
     * tolerance checks + combining with the six EPA parameters into
     * overallPass), confirmed with Michael as pre-dating today's
     * session entirely. This is just the HTTP entry point onto it.
     */
    @PostMapping("/{systemId}/calibration-records")
    public ResponseEntity<?> submitCalibrationRecord(@PathVariable Long systemId,
                                                       @RequestBody CalibrationService.CreateCalibrationRequest req) {
        TestingSystem system = testingSystemRepository.findById(systemId)
                .orElseThrow(() -> new IllegalArgumentException("TestingSystem not found: " + systemId));

        // Michael, 2026-08-31 -- rebuilt with the URL's resolved,
        // authoritative system rather than trusting whatever
        // testingSystem the request body itself claims -- same
        // reasoning as the request's own eventual field-by-field
        // reconstruction elsewhere in this project (a mismatched or
        // forged testingSystem in the body can't silently write to
        // the wrong system's calibration history).
        CalibrationService.CreateCalibrationRequest resolvedReq = new CalibrationService.CreateCalibrationRequest(
                system, req.triggerReason(),
                req.lightSourceVoltagePass(), req.photocellSpectralResponsePass(), req.angleOfViewPass(),
                req.angleOfProjectionPass(), req.calibrationErrorPass(), req.responseTimePass(),
                req.paneReadings());

        CalibrationRecord record = calibrationService.createCalibrationRecord(resolvedReq);
        return ResponseEntity.status(HttpStatus.CREATED).body(record);
    }
}
