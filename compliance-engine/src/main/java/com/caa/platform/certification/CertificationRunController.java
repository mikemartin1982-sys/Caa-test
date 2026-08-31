package com.caa.platform.certification;
 
import com.caa.platform.equipment.CalibrationService;
import com.caa.platform.equipment.TestingSystem;
import com.caa.platform.equipment.TestingSystemRepository;
import com.caa.platform.equipment.Trailer;
import com.caa.platform.equipment.TrailerRepository;
import com.caa.platform.session.Session;
import com.caa.platform.session.SessionRepository;
import com.caa.platform.staff.StaffUser;
import com.caa.platform.staff.StaffUserRepository;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.web.bind.annotation.*;
 
import java.time.OffsetDateTime;
import java.util.ArrayList;
import java.util.List;
import java.util.Map;
 
/**
 * Backs /certification-runs in api-contract/openapi.yaml. Implements
 * Section 3b's structural rules at the write path: a 25-point run must be
 * White-only (enforced by simply never scoring/attaching Black
 * observations when pointCount == 25), and scoring runs through
 * {@link Method9ScoringService} rather than being computed ad hoc here.
 * After scoring, {@link CertificationDeterminationService} decides
 * whether this run resulted in an overall pass (Section 3b's standard vs.
 * split-run combination logic) and, if so, creates the Certification and
 * flips the Enrollment to CERTIFIED (Section 4g).
 */
@RestController
@RequestMapping("/api/v1/certification-runs")
public class CertificationRunController {
 
    private final CertificationRunRepository runRepository;
    private final ObservationRepository observationRepository;
    private final SessionRepository sessionRepository;
    private final TrailerRepository trailerRepository;
    private final TestingSystemRepository testingSystemRepository;
    private final StaffUserRepository staffUserRepository;
    private final Method9ScoringService scoringService;
    private final SplitRunEligibilityService eligibilityService;
    private final CalibrationService calibrationService;
    private final CertificationDeterminationService determinationService;
 
    public CertificationRunController(CertificationRunRepository runRepository,
                                       ObservationRepository observationRepository,
                                       SessionRepository sessionRepository,
                                       TrailerRepository trailerRepository,
                                       TestingSystemRepository testingSystemRepository,
                                       StaffUserRepository staffUserRepository,
                                       Method9ScoringService scoringService,
                                       SplitRunEligibilityService eligibilityService,
                                       CalibrationService calibrationService,
                                       CertificationDeterminationService determinationService) {
        this.runRepository = runRepository;
        this.observationRepository = observationRepository;
        this.sessionRepository = sessionRepository;
        this.trailerRepository = trailerRepository;
        this.testingSystemRepository = testingSystemRepository;
        this.staffUserRepository = staffUserRepository;
        this.scoringService = scoringService;
        this.eligibilityService = eligibilityService;
        this.calibrationService = calibrationService;
        this.determinationService = determinationService;
    }
 
    public record ObservationInput(PlumeColor color, Short pointNumber, Short trueOpacityValue, Short studentEstimatedOpacity) {}
 
    public record CreateRunRequest(
            Long sessionId, Long enrollmentId, Integer runNumber, Short pointCount, Long priorRunId,
            Long trailerId, Long testingSystemId, Long administeredByStaffId,
            List<ObservationInput> observations) {}
 
    @PostMapping
    @Transactional
    public ResponseEntity<?> create(@RequestBody CreateRunRequest req) {
        if (req.pointCount() != 25 && req.pointCount() != 50) {
            return unprocessable("pointCount must be 25 or 50 (Section 3b) — got " + req.pointCount());
        }
 
        List<ObservationInput> whiteInputs = req.observations().stream()
                .filter(o -> o.color() == PlumeColor.WHITE).toList();
        List<ObservationInput> blackInputs = req.observations().stream()
                .filter(o -> o.color() == PlumeColor.BLACK).toList();
 
        if (req.pointCount() == 25 && !blackInputs.isEmpty()) {
            return unprocessable("A 25-point run is White-only — Black is never split (Section 3b). "
                    + "Got " + blackInputs.size() + " Black observations.");
        }
        if (req.pointCount() == 50 && blackInputs.size() != 25) {
            return unprocessable("A 50-point run requires exactly 25 Black observations — got " + blackInputs.size());
        }
        if (whiteInputs.size() != 25) {
            return unprocessable("Every run requires exactly 25 White observations — got " + whiteInputs.size());
        }
 
        Session session = sessionRepository.findById(req.sessionId())
                .orElseThrow(() -> new IllegalArgumentException("Session not found: " + req.sessionId()));
        Trailer trailer = trailerRepository.findById(req.trailerId())
                .orElseThrow(() -> new IllegalArgumentException("Trailer not found: " + req.trailerId()));
        TestingSystem testingSystem = testingSystemRepository.findById(req.testingSystemId())
                .orElseThrow(() -> new IllegalArgumentException("TestingSystem not found: " + req.testingSystemId()));
        StaffUser administeredBy = staffUserRepository.findById(req.administeredByStaffId())
                .orElseThrow(() -> new IllegalArgumentException("Staff user not found: " + req.administeredByStaffId()));
 
        CertificationRun run = new CertificationRun();
        run.setSession(session);
        run.setRunNumber(req.runNumber());
        run.setPointCount(req.pointCount());
        run.setPerformedAt(OffsetDateTime.now());
        run.setTrailer(trailer);
        run.setTestingSystem(testingSystem);
        run.setAdministeredBy(administeredBy);
 
        if (req.priorRunId() != null) {
            CertificationRun priorRun = runRepository.findById(req.priorRunId())
                    .orElseThrow(() -> new IllegalArgumentException("Prior run not found: " + req.priorRunId()));
            run.setPriorRun(priorRun);
        }
 
        // Scoring happens BEFORE the first save -- CertificationRun's
        // 50-point check constraint (chk_50pt_requires_both_colors)
        // requires blackCumulativeDeviation to already be populated at
        // INSERT time, not filled in by a later UPDATE. Observations
        // reference 'run' as a Java object here, not yet needing its
        // generated ID -- Hibernate assigns that in place on save()
        // below, and these same in-memory Observation instances pick it
        // up automatically before observationRepository.saveAll() runs.
        List<Observation> whiteObservations = toObservations(whiteInputs, run);
        List<Observation> blackObservations = toObservations(blackInputs, run);
 
        scoringService.scoreRun(run, whiteObservations, blackObservations);
 
        CertificationRun savedRun = runRepository.save(run);
 
        observationRepository.saveAll(whiteObservations);
        if (!blackObservations.isEmpty()) {
            observationRepository.saveAll(blackObservations);
        }
 
        // Section 4h: "flag, don't block." This never prevents the run
        // above from having already happened -- it only records a
        // ComplianceFlag for audit review if the system's 5-Filter was
        // expired/failed/reset-by-maintenance at the time.
        calibrationService.checkAndFlagIfNeeded(savedRun);
 
        // Section 3b/4g: determines whether THIS run (standard or
        // White-split-retake) results in an overall pass. If so, creates
        // the Certification and flips the Enrollment to CERTIFIED --
        // which is what should trigger the per-student certificate email
        // (Section 4g), once email dispatch is built.
        Certification certification = determinationService.determineAndApply(savedRun, req.enrollmentId());
 
        return ResponseEntity.status(HttpStatus.CREATED)
                .body(Map.of("run", savedRun, "certified", certification != null,
                        "certification", certification != null ? certification : Map.of()));
    }
 
    @GetMapping("/{runId}/split-run-eligibility")
    public ResponseEntity<?> splitRunEligibility(@PathVariable Long runId) {
        CertificationRun run = runRepository.findById(runId)
                .orElseThrow(() -> new IllegalArgumentException("Run not found: " + runId));
        Session session = run.getSession();
        var result = eligibilityService.evaluate(run, session.getRegion(), session.getFormat());
        return ResponseEntity.ok(Map.of("result", result.name()));
    }
 
    private List<Observation> toObservations(List<ObservationInput> inputs, CertificationRun run) {
        List<Observation> result = new ArrayList<>();
        for (ObservationInput in : inputs) {
            Observation obs = new Observation();
            obs.setCertificationRun(run);
            obs.setColor(in.color());
            obs.setPointNumber(in.pointNumber());
            obs.setTrueOpacityValue(in.trueOpacityValue());
            obs.setStudentEstimatedOpacity(in.studentEstimatedOpacity());
            result.add(obs);
        }
        return result;
    }
 
    private ResponseEntity<Map<String, String>> unprocessable(String message) {
        return ResponseEntity.unprocessableEntity().body(Map.of("error", message));
    }
}