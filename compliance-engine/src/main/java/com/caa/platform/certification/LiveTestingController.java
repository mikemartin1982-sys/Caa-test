package com.caa.platform.certification;

import com.caa.platform.session.Session;
import com.caa.platform.session.SessionRepository;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.web.bind.annotation.*;

import java.util.List;
import java.util.Map;

/**
 * Section 4/Digital-Testing live flow. Two audiences hit this:
 * Operator-facing endpoints (start/record-true-value/advance) are
 * called from the staff-authenticated Digital-Testing Admin page.
 * Student-facing endpoints (submit-guess, status polling) are called
 * server-side by Laravel's OnsiteController -- same pattern as the
 * rest of /onsite, the student's own browser never touches this API
 * or its credentials directly.
 */
@RestController
@RequestMapping("/api/v1/sessions/{sessionId}/live-test")
public class LiveTestingController {

    private final SessionRepository sessionRepository;
    private final LiveTestingService liveTestingService;

    public LiveTestingController(SessionRepository sessionRepository, LiveTestingService liveTestingService) {
        this.sessionRepository = sessionRepository;
        this.liveTestingService = liveTestingService;
    }

    private Session requireSession(Long sessionId) {
        return sessionRepository.findById(sessionId)
                .orElseThrow(() -> new IllegalArgumentException("Session not found: " + sessionId));
    }

    public record StartLiveTestRequest(java.util.Set<Long> splitRunEnrollmentIds) {}

    @PostMapping("/start")
    @Transactional
    public ResponseEntity<?> start(@PathVariable Long sessionId, @RequestBody(required = false) StartLiveTestRequest req) {
        try {
            java.util.Set<Long> splitRunIds = req != null ? req.splitRunEnrollmentIds() : null;
            liveTestingService.startLiveTest(requireSession(sessionId), splitRunIds);
            return ResponseEntity.ok(Map.of("started", true));
        } catch (IllegalStateException e) {
            return ResponseEntity.status(HttpStatus.CONFLICT).body(Map.of("error", e.getMessage()));
        }
    }

    public record SplitRunCandidate(Long enrollmentId, String studentName) {}

    /**
     * For the Operator/Field-Manager UI to show real split-run
     * candidates before starting -- who already has a failed-White/
     * passed-Black prior run and is actually eligible, using the SAME
     * unchanged SplitRunEligibilityService rules (region/format blocks
     * included), rather than the UI guessing.
     */
    @GetMapping("/split-run-candidates")
    @Transactional(readOnly = true)
    public ResponseEntity<List<SplitRunCandidate>> splitRunCandidates(@PathVariable Long sessionId) {
        Session session = requireSession(sessionId);
        List<SplitRunCandidate> candidates = liveTestingService.splitRunCandidates(session).stream()
                .map(e -> new SplitRunCandidate(e.getId(), e.getStudent().getName()))
                .toList();
        return ResponseEntity.ok(candidates);
    }

    public record RevisitRequest(Short pointNumber) {}

    /**
     * Reopens a PAST point for correction (Michael, 2026-08-17) -- does
     * NOT record a new true value, reuses whatever's already on file.
     * Blocks advance() until ended, so the main test's forward
     * progress can't get confused with an active revisit.
     */
    @PostMapping("/revisit")
    @Transactional
    public ResponseEntity<?> revisit(@PathVariable Long sessionId, @RequestBody RevisitRequest req) {
        try {
            liveTestingService.revisitPoint(requireSession(sessionId), req.pointNumber());
            return ResponseEntity.ok(Map.of("revisiting", req.pointNumber()));
        } catch (IllegalStateException e) {
            return ResponseEntity.status(HttpStatus.CONFLICT).body(Map.of("error", e.getMessage()));
        }
    }

    @PostMapping("/end-revisit")
    @Transactional
    public ResponseEntity<?> endRevisit(@PathVariable Long sessionId) {
        try {
            liveTestingService.endRevisit(requireSession(sessionId));
            return ResponseEntity.ok(Map.of("ended", true));
        } catch (IllegalStateException e) {
            return ResponseEntity.status(HttpStatus.CONFLICT).body(Map.of("error", e.getMessage()));
        }
    }

    public record RecordTrueValueRequest(Short trueOpacity) {}

    @PostMapping("/record-true-value")
    @Transactional
    public ResponseEntity<?> recordTrueValue(@PathVariable Long sessionId, @RequestBody RecordTrueValueRequest req) {
        try {
            liveTestingService.recordTrueValue(requireSession(sessionId), req.trueOpacity());
            return ResponseEntity.ok(Map.of("recorded", true));
        } catch (IllegalStateException e) {
            return ResponseEntity.status(HttpStatus.CONFLICT).body(Map.of("error", e.getMessage()));
        }
    }

    public record SubmitGuessRequest(Long enrollmentId, Short guess) {}

    @PostMapping("/submit-guess")
    @Transactional
    public ResponseEntity<?> submitGuess(@PathVariable Long sessionId, @RequestBody SubmitGuessRequest req) {
        try {
            liveTestingService.submitGuess(requireSession(sessionId), req.enrollmentId(), req.guess());
            return ResponseEntity.ok(Map.of("submitted", true));
        } catch (IllegalStateException e) {
            return ResponseEntity.status(HttpStatus.CONFLICT).body(Map.of("error", e.getMessage()));
        }
    }

    public record LiveTestStatus(boolean active, Integer runNumber, Short pointNumber, PlumeColor color,
                                  boolean trueValueSet, Short revisitPointNumber,
                                  int readyToGradeCount,
                                  List<LiveTestingService.PointStatus> students,
                                  List<LiveTestingService.FailedPointStatus> failedCompletedPoints) {}

    /**
     * Operator's full status view -- every participant's submission
     * state for the current point (or the revisit point, if one's
     * active -- see currentPointStatus()'s own Javadoc). pointNumber/
     * color/trueValueSet always reflect the REAL forward progress,
     * unaffected by any active revisit -- revisitPointNumber is the
     * separate signal for that. readyToGradeCount (2026-08-17) is how
     * many students have finished all their points AND confirmed their
     * final answers -- the UI shows "Grade Test" once this is above 0.
     * runNumber (2026-08-18, real DIBs reference) identifies which run
     * is currently in play, for the consolidated status view.
     */
    @GetMapping("/status")
    @Transactional(readOnly = true)
    public ResponseEntity<LiveTestStatus> status(@PathVariable Long sessionId) {
        Session session = requireSession(sessionId);
        return ResponseEntity.ok(new LiveTestStatus(
                session.isLiveTestActive(),
                liveTestingService.currentRunNumber(session),
                session.getLiveTestPointNumber(),
                session.getLiveTestColor(),
                session.getLiveTestTrueOpacity() != null,
                session.getLiveTestRevisitPointNumber(),
                liveTestingService.readyToGrade(session).size(),
                liveTestingService.currentPointStatus(session),
                liveTestingService.failedCompletedPointStatus(session)));
    }

    /** Student's own detailed status (via Laravel) -- everything the testing webapp needs: their run's length, current point/color, submission history, and (once graded) their real pass/fail outcome. */
    @GetMapping("/my-status")
    @Transactional(readOnly = true)
    public ResponseEntity<LiveTestingService.MyDetailedStatus> myStatus(@PathVariable Long sessionId, @RequestParam Long enrollmentId) {
        Session session = requireSession(sessionId);
        return ResponseEntity.ok(liveTestingService.myDetailedStatus(session, enrollmentId));
    }

    /** Student's attestation once they've answered every required point: "these answers are your own and not somebody else's." */
    @PostMapping("/confirm-final-answers")
    @Transactional
    public ResponseEntity<?> confirmFinalAnswers(@PathVariable Long sessionId, @RequestParam Long enrollmentId) {
        try {
            liveTestingService.confirmFinalAnswers(requireSession(sessionId), enrollmentId);
            return ResponseEntity.ok(Map.of("confirmed", true));
        } catch (IllegalStateException e) {
            return ResponseEntity.status(HttpStatus.CONFLICT).body(Map.of("error", e.getMessage()));
        }
    }

    /**
     * Operator/Field-Manager action -- grades whoever's currently
     * ready (see LiveTestingService.gradeTest()'s Javadoc). Repeatable:
     * click again later for a group that finishes after this one
     * (e.g. a full-run group still testing while split-run finishers
     * already got graded).
     */
    @PostMapping("/grade-test")
    @Transactional
    public ResponseEntity<?> gradeTest(@PathVariable Long sessionId) {
        try {
            int gradedCount = liveTestingService.gradeTest(requireSession(sessionId));
            return ResponseEntity.ok(Map.of("graded", gradedCount));
        } catch (IllegalStateException e) {
            return ResponseEntity.status(HttpStatus.CONFLICT).body(Map.of("error", e.getMessage()));
        }
    }

    public record SubmitSignatureRequest(Long enrollmentId, String signatureDataUrl) {}

    /** A passing student's drawn signature (canvas data URL) -- a failing student never calls this at all. */
    @PostMapping("/submit-signature")
    @Transactional
    public ResponseEntity<?> submitSignature(@PathVariable Long sessionId, @RequestBody SubmitSignatureRequest req) {
        try {
            liveTestingService.submitSignature(req.enrollmentId(), req.signatureDataUrl());
            return ResponseEntity.ok(Map.of("submitted", true));
        } catch (IllegalStateException e) {
            return ResponseEntity.status(HttpStatus.CONFLICT).body(Map.of("error", e.getMessage()));
        }
    }

    @PostMapping("/advance")
    @Transactional
    public ResponseEntity<?> advance(@PathVariable Long sessionId) {
        try {
            LiveTestingService.AdvanceResult result = liveTestingService.advance(requireSession(sessionId));
            return ResponseEntity.ok(result);
        } catch (IllegalStateException e) {
            return ResponseEntity.status(HttpStatus.CONFLICT).body(Map.of("error", e.getMessage()));
        }
    }
}
