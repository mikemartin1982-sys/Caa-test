package com.caa.platform.certification;

import com.caa.platform.enrollment.Enrollment;
import com.caa.platform.enrollment.EnrollmentRepository;
import com.caa.platform.enrollment.RosterStatus;
import com.caa.platform.equipment.SystemDesignation;
import com.caa.platform.equipment.TestingSystem;
import com.caa.platform.equipment.TestingSystemRepository;
import com.caa.platform.session.Session;
import com.caa.platform.staff.StaffUser;
import org.springframework.stereotype.Service;

import java.time.OffsetDateTime;
import java.util.ArrayList;
import java.util.List;
import java.util.Optional;
import java.util.Set;

/**
 * Section 4/Digital-Testing: coordinates the REAL live-test interaction
 * model (Michael, 2026-08-16) -- one shared smoke plume, the Operator
 * records the true opacity value per point via their tablet, every
 * actively-testing student independently submits their own guess, each
 * guess is scored immediately (Method9ScoringService, unchanged), and
 * the Operator advances once everyone's submitted.
 *
 * Deliberately built on the EXISTING per-student CertificationRun model
 * (confirmed with Michael) rather than a new shared-run concept -- the
 * "sharedness" is achieved by writing the SAME true value across every
 * active student's own run for the current point, not by a new entity.
 * This keeps everything already built (scoring, split-run eligibility,
 * PDF certs) working exactly as before.
 *
 * Split-run (Michael, 2026-08-16): a MIXED live session is real -- some
 * students retake a failed prior run via a 25-point, White-only split
 * run (chained via CertificationRun.priorRun, using the EXISTING
 * SplitRunEligibilityService rules unchanged), while OTHER students who
 * "just walked up" have no prior run and need the full 50 consecutive
 * points, in the SAME live session at the same time. Split-run
 * participants grade and finish once they confirm their 25th point --
 * they never continue into the Black block, and advance()'s "has
 * everyone submitted" gate does not wait on them past point 25.
 *
 * A real, separate bug was also fixed here alongside split-run support:
 * the original version of completeLiveTest() bypassed
 * CertificationDeterminationService entirely and set RosterStatus
 * directly -- meaning no Certification record, no PDF, and no email
 * were ever produced for a live-test pass. Both the full-run and
 * split-run paths now go through the same real determineAndApply()
 * logic the original batch-creation endpoint always used, including
 * the White-retake + prior-Black combination logic split-run needs.
 *
 * Deliberately NOT @Transactional at this layer -- every public method
 * here is always called from a controller method that's already
 * @Transactional, and Spring's default REQUIRED propagation means
 * adding it here too would just join the same physical transaction.
 * Worse than redundant: when one of these methods throws (the expected
 * IllegalStateException path for a 409), an inner @Transactional
 * boundary marks that shared transaction rollback-only BEFORE the
 * exception reaches the controller's try/catch -- so even though the
 * controller catches it and returns a normal 409, the transaction then
 * fails to commit with UnexpectedRollbackException, surfacing as a
 * confusing 500 instead. Caught live, 2026-08-16, on the SECOND call to
 * advance() (the one meant to be correctly rejected with a 409).
 */
@Service
public class LiveTestingService {

    private final EnrollmentRepository enrollmentRepository;
    private final CertificationRunRepository runRepository;
    private final ObservationRepository observationRepository;
    private final TestingSystemRepository testingSystemRepository;
    private final Method9ScoringService scoringService;
    private final SplitRunEligibilityService eligibilityService;
    private final CertificationDeterminationService determinationService;
    private final SplitRunAuthorizationRepository splitRunAuthorizationRepository;
    private final CertificationRepository certificationRepository;
    private final SignatureStorageService signatureStorageService;

    public LiveTestingService(EnrollmentRepository enrollmentRepository,
                               CertificationRunRepository runRepository,
                               ObservationRepository observationRepository,
                               TestingSystemRepository testingSystemRepository,
                               Method9ScoringService scoringService,
                               SplitRunEligibilityService eligibilityService,
                               CertificationDeterminationService determinationService,
                               SplitRunAuthorizationRepository splitRunAuthorizationRepository,
                               CertificationRepository certificationRepository,
                               SignatureStorageService signatureStorageService) {
        this.enrollmentRepository = enrollmentRepository;
        this.runRepository = runRepository;
        this.observationRepository = observationRepository;
        this.testingSystemRepository = testingSystemRepository;
        this.scoringService = scoringService;
        this.eligibilityService = eligibilityService;
        this.determinationService = determinationService;
        this.splitRunAuthorizationRepository = splitRunAuthorizationRepository;
        this.certificationRepository = certificationRepository;
        this.signatureStorageService = signatureStorageService;
    }

    /** Signed in, not already resolved DNC/DNA, hasn't already got a certifying run -- the FULL-run (fresh) candidates. */
    private List<Enrollment> eligibleToStart(Session session) {
        return enrollmentRepository.findBySessionId(session.getId()).stream()
                .filter(e -> e.getRosterStatus() != null)
                .filter(e -> e.getRosterStatus() != RosterStatus.DNC && e.getRosterStatus() != RosterStatus.DNA)
                .filter(e -> e.getCertifyingRun() == null)
                .toList();
    }

    /**
     * Students who already have a run and are genuinely split-run
     * eligible per the EXISTING, unchanged SplitRunEligibilityService
     * rules (White failed, Black passed, no region/format block) --
     * for the Operator/Field-Manager UI to show real candidates rather
     * than guessing who's eligible.
     */
    public List<Enrollment> splitRunCandidates(Session session) {
        return enrollmentRepository.findBySessionId(session.getId()).stream()
                .filter(e -> e.getCertifyingRun() != null)
                .filter(e -> eligibilityService.evaluate(e.getCertifyingRun(), session.getRegion(), session.getFormat())
                        == SplitRunEligibilityService.EligibilityResult.ELIGIBLE_WHITE_RETAKE)
                .toList();
    }

    /**
     * Everyone still "in play" for the current test run-number,
     * regardless of whether they've finished submitting their points
     * yet -- used for grading readiness (confirmFinalAnswers/
     * readyToGrade/gradeTest), which needs to see people even AFTER
     * they've stopped needing point-by-point gating.
     */
    /**
     * The run number currently in play for this session -- exposed for
     * the Operator's consolidated status view (Michael, 2026-08-18, real
     * DIBs reference screenshot: "Run: < 1 >"). Null if no live test has
     * ever started, or the most recent one already finished.
     */
    public Integer currentRunNumber(Session session) {
        return runRepository.findBySessionIdOrderByRunNumber(session.getId()).stream()
                .mapToInt(CertificationRun::getRunNumber).max().stream().boxed().findFirst().orElse(null);
    }

    private List<Enrollment> allCurrentRunParticipants(Session session) {
        Integer currentRunNumber = currentRunNumber(session);
        if (currentRunNumber == null) return List.of();
        return enrollmentRepository.findBySessionId(session.getId()).stream()
                .filter(e -> e.getCertifyingRun() != null
                        && currentRunNumber.equals(e.getCertifyingRun().getRunNumber())
                        && e.getCertifyingRun().isInProgress())
                .toList();
    }

    /**
     * Participants who still need POINT-BY-POINT gating -- excludes
     * anyone whose submissionsComplete is already true (split-run past
     * point 25, or full-run past point 50), since they're done
     * answering and just awaiting confirmation + grading now, not
     * blocking advancement of whoever's still going.
     */
    private List<Enrollment> currentParticipants(Session session) {
        return allCurrentRunParticipants(session).stream()
                .filter(e -> !e.getCertifyingRun().isSubmissionsComplete())
                .toList();
    }

    /**
     * @param splitRunEnrollmentIds Field-Manager-designated students who
     *                              already have an eligible prior run --
     *                              they get a 25-point White-only retake
     *                              instead of a fresh 50-point run.
     *                              Null/empty is fine -- a normal
     *                              full-run-only session.
     */
    public void startLiveTest(Session session, Set<Long> splitRunEnrollmentIds) {
        if (session.isLiveTestActive()) {
            throw new IllegalStateException("Live test already in progress for session " + session.getId());
        }
        StaffUser administeredBy = session.getOperator() != null ? session.getOperator() : session.getFieldManager();
        if (administeredBy == null) {
            throw new IllegalStateException("Session has no Operator or Field Manager assigned -- set one in Session Details first.");
        }
        if (session.getTrailer() == null) {
            throw new IllegalStateException("Session has no Trailer assigned -- set one in Session Details first.");
        }
        TestingSystem primarySystem = testingSystemRepository.findByTrailerId(session.getTrailer().getId()).stream()
                .filter(t -> t.getDesignation() == SystemDesignation.PRIMARY)
                .findFirst()
                .orElseThrow(() -> new IllegalStateException(
                        "Trailer " + session.getTrailer().getIdentifier() + " has no Primary testing system configured."));

        List<Enrollment> freshEligible = eligibleToStart(session);

        List<Enrollment> splitRunParticipants = new ArrayList<>();
        if (splitRunEnrollmentIds != null) {
            for (Long enrollmentId : splitRunEnrollmentIds) {
                Enrollment e = enrollmentRepository.findById(enrollmentId)
                        .orElseThrow(() -> new IllegalArgumentException("Enrollment not found: " + enrollmentId));
                if (e.getCertifyingRun() == null) {
                    throw new IllegalStateException("Enrollment " + enrollmentId + " has no prior run to retake -- not split-run eligible.");
                }
                SplitRunEligibilityService.EligibilityResult eligibility =
                        eligibilityService.evaluate(e.getCertifyingRun(), session.getRegion(), session.getFormat());
                if (eligibility != SplitRunEligibilityService.EligibilityResult.ELIGIBLE_WHITE_RETAKE) {
                    throw new IllegalStateException("Enrollment " + enrollmentId + " is not split-run eligible: " + eligibility);
                }
                splitRunParticipants.add(e);
            }
        }

        if (freshEligible.isEmpty() && splitRunParticipants.isEmpty()) {
            throw new IllegalStateException(
                    "No eligible students to start a live test -- everyone's either not signed in yet, already has an active run, or not split-run eligible.");
        }

        int nextRunNumber = runRepository.findBySessionIdOrderByRunNumber(session.getId()).stream()
                .mapToInt(CertificationRun::getRunNumber).max().orElse(0) + 1;

        for (Enrollment e : freshEligible) {
            createRun(session, e, (short) 50, null, administeredBy, primarySystem, nextRunNumber);
        }
        for (Enrollment e : splitRunParticipants) {
            createRun(session, e, (short) 25, e.getCertifyingRun(), administeredBy, primarySystem, nextRunNumber);
        }

        session.setLiveTestActive(true);
        session.setLiveTestPointNumber((short) 1);
        session.setLiveTestColor(PlumeColor.WHITE);
        session.setLiveTestTrueOpacity(null);
    }

    private void createRun(Session session, Enrollment e, short pointCount, CertificationRun priorRun,
                            StaffUser administeredBy, TestingSystem testingSystem, int runNumber) {
        CertificationRun run = new CertificationRun();
        run.setSession(session);
        run.setRunNumber(runNumber);
        run.setPointCount(pointCount);
        run.setPerformedAt(OffsetDateTime.now());
        run.setTrailer(session.getTrailer());
        run.setTestingSystem(testingSystem);
        run.setAdministeredBy(administeredBy);
        run.setInProgress(true);
        if (priorRun != null) {
            run.setPriorRun(priorRun);
        }
        CertificationRun saved = runRepository.save(run);
        e.setCertifyingRun(saved);
        enrollmentRepository.save(e);
    }

    /**
     * Reopens a PAST point for correction (Michael, 2026-08-17) -- a
     * real field scenario: a student's answer was outside allowable
     * deviation, and the Operator needs to bring it back up WITHOUT
     * disrupting the live test's real forward progress, which may
     * already be many points ahead. Does NOT re-record a new true
     * value -- reuses whatever's already on file from the original
     * recording. Lockstep, same as the main flow: the Operator must
     * explicitly trigger this BEFORE it becomes editable for students.
     */
    public void revisitPoint(Session session, short pointNumber) {
        requireActive(session);
        if (pointNumber < 1 || pointNumber > 50) {
            throw new IllegalStateException("Point number must be between 1 and 50.");
        }
        if (session.getLiveTestRevisitPointNumber() != null) {
            throw new IllegalStateException(
                    "A revisit is already active for point " + session.getLiveTestRevisitPointNumber() + " -- end it before starting another.");
        }
        boolean pointWasReached = currentParticipants(session).stream()
                .anyMatch(e -> observationRepository.existsByCertificationRunIdAndPointNumber(e.getCertifyingRun().getId(), pointNumber));
        if (!pointWasReached) {
            throw new IllegalStateException("Point " + pointNumber + " hasn't been reached yet in this test -- nothing to revisit.");
        }
        session.setLiveTestRevisitPointNumber(pointNumber);
    }

    public void endRevisit(Session session) {
        if (session.getLiveTestRevisitPointNumber() == null) {
            throw new IllegalStateException("No revisit is currently active.");
        }
        session.setLiveTestRevisitPointNumber(null);
    }

    public void recordTrueValue(Session session, short trueValue) {
        requireActive(session);
        session.setLiveTestTrueOpacity(trueValue);
    }

    public void submitGuess(Session session, Long enrollmentId, short guess) {
        requireActive(session);
        Enrollment enrollment = enrollmentRepository.findById(enrollmentId)
                .orElseThrow(() -> new IllegalArgumentException("Enrollment not found: " + enrollmentId));
        if (enrollment.getCertifyingRun() == null || !enrollment.getCertifyingRun().isInProgress()) {
            throw new IllegalStateException("This student isn't part of the active live test.");
        }
        CertificationRun run = enrollment.getCertifyingRun();

        // Students can change their mind and resubmit for the CURRENT
        // (or currently-revisited) point as many times as they want
        // (Michael, 2026-08-17: "we cannot lock them out of changing
        // their answer" -- e.g. a self-correction) -- but only while
        // it's still live. This method always writes to whichever
        // point is currently reachable (the forward point, or the
        // Operator's active revisit target); once that's no longer
        // true, there's no way to target that point again, so it's
        // naturally locked from then on without a separate flag.
        short targetPoint;
        PlumeColor targetColor;
        short targetTrueValue;

        Short revisitPoint = session.getLiveTestRevisitPointNumber();
        Optional<Observation> revisitExisting = revisitPoint != null
                ? observationRepository.findByCertificationRunIdAndPointNumber(run.getId(), revisitPoint)
                : Optional.empty();

        if (revisitExisting.isPresent()) {
            // Revisiting a point this student already answered -- reuse
            // the ALREADY-recorded true value, never re-enter one.
            targetPoint = revisitPoint;
            targetColor = revisitPoint <= 25 ? PlumeColor.WHITE : PlumeColor.BLACK;
            targetTrueValue = revisitExisting.get().getTrueOpacityValue();
        } else {
            // Not in a revisit (or this student has nothing to revisit
            // at that point) -- normal forward-point submission.
            //
            // Blocked once finalAnswersConfirmed is true (Michael,
            // 2026-08-17, found live during testing): the attestation
            // "these answers are your own" should actually mean
            // something -- without this, a student could keep changing
            // their last point indefinitely after confirming it was
            // final. Deliberately NOT applied to the revisit branch
            // above -- an Operator-triggered revisit is a separate,
            // explicit override and must keep working regardless of
            // confirmation state (e.g. "before we grade, let's fix
            // point 12 after all").
            if (run.isFinalAnswersConfirmed()) {
                throw new IllegalStateException("You've already confirmed your final answers -- see your Field Manager if something needs correcting.");
            }
            if (session.getLiveTestTrueOpacity() == null) {
                throw new IllegalStateException("Operator hasn't recorded the true value for this point yet.");
            }
            targetPoint = session.getLiveTestPointNumber();
            targetColor = session.getLiveTestColor();
            targetTrueValue = session.getLiveTestTrueOpacity();
        }

        Observation obs = observationRepository.findByCertificationRunIdAndPointNumber(run.getId(), targetPoint)
                .orElseGet(Observation::new);

        obs.setCertificationRun(run);
        obs.setColor(targetColor);
        obs.setPointNumber(targetPoint);
        obs.setTrueOpacityValue(targetTrueValue);
        obs.setStudentEstimatedOpacity(guess);
        scoringService.scoreObservation(obs);
        observationRepository.save(obs);

        if (revisitExisting.isEmpty()) {
            // Only a genuine forward-point submission can complete a
            // run or trigger auto-advance -- a revisit always updates
            // an EXISTING observation (never adds a new one), so the
            // count below never changes during a revisit, and there's
            // nothing to advance since revisit doesn't touch the real
            // forward point at all.
            long submittedCount = observationRepository.findByCertificationRunIdOrderByPointNumber(run.getId()).size();
            if (submittedCount >= run.getPointCount()) {
                run.setSubmissionsComplete(true);
                runRepository.save(run);
            }
            tryAutoAdvance(session);
        }
    }

    /**
     * Runs automatically after every forward-point submission (Michael,
     * 2026-08-17: "we don't want the process of test taking to be a
     * series of at minimum 50 button clicks") -- the Operator's only
     * remaining per-point action is Record True Value; this replaces
     * the old requirement to also manually click Advance every time.
     * Does nothing if not everyone's submitted yet, or if a revisit is
     * active (advancing the real forward point during a revisit would
     * be exactly the confusion revisit was built to avoid). Silent by
     * design -- there's no one to show an error to here, unlike the
     * manual advance() endpoint below, which is kept as a fallback.
     */
    private void tryAutoAdvance(Session session) {
        if (session.getLiveTestRevisitPointNumber() != null) return;

        List<PointStatus> status = currentPointStatus(session);
        boolean allSubmitted = !status.isEmpty() && status.stream().allMatch(PointStatus::submitted);
        if (!allSubmitted) return;

        short next = (short) (session.getLiveTestPointNumber() + 1);
        List<Enrollment> stillActive = currentParticipants(session);

        if (stillActive.isEmpty() || next > 50) {
            // Nothing left to advance to for anyone still going -- they're
            // all now awaiting confirmation + grading (confirmFinalAnswers()/
            // gradeTest() below), NOT graded automatically here.
            return;
        }

        PlumeColor nextColor = next <= 25 ? PlumeColor.WHITE : PlumeColor.BLACK;
        session.setLiveTestPointNumber(next);
        session.setLiveTestColor(nextColor);
        session.setLiveTestTrueOpacity(null);
    }

    /**
     * Student's attestation once they've answered every required point
     * (Michael, 2026-08-17): "these answers are your own and not
     * somebody else's." Idempotent -- confirming twice is a harmless
     * no-op, not an error, since the student's own UI might retry.
     */
    public void confirmFinalAnswers(Session session, Long enrollmentId) {
        Enrollment enrollment = enrollmentRepository.findById(enrollmentId)
                .orElseThrow(() -> new IllegalArgumentException("Enrollment not found: " + enrollmentId));
        CertificationRun run = enrollment.getCertifyingRun();
        if (run == null || !run.isInProgress()) {
            throw new IllegalStateException("No active run to confirm for this student.");
        }
        if (!run.isSubmissionsComplete()) {
            throw new IllegalStateException("You haven't answered all your points yet.");
        }
        if (run.isFinalAnswersConfirmed()) {
            return;
        }
        run.setFinalAnswersConfirmed(true);
        run.setFinalAnswersConfirmedAt(OffsetDateTime.now());
        runRepository.save(run);
    }

    /**
     * Whoever has finished all their points AND confirmed, and is
     * still ungraded -- NOT gated on the whole class being ready
     * together, since split-run participants (done at point 25) must
     * stay gradable independently of a full-run group that's still
     * testing (the same principle as everywhere else in this class).
     */
    public List<Enrollment> readyToGrade(Session session) {
        return allCurrentRunParticipants(session).stream()
                .filter(e -> e.getCertifyingRun().isSubmissionsComplete() && e.getCertifyingRun().isFinalAnswersConfirmed())
                .toList();
    }

    /**
     * Grades whoever's currently ready (see readyToGrade()) -- a
     * REPEATABLE action, not a one-time end-of-test button: click it
     * once for split-run finishers at point 25, again later for the
     * full-run group at point 50. Reuses the exact same finishParticipants()
     * this class already used for both split-run and full-run
     * completion -- real Method 9 scoring, real Certification/PDF/
     * email via CertificationDeterminationService, real
     * SplitRunAuthorization audit entry where applicable.
     */
    public int gradeTest(Session session) {
        List<Enrollment> ready = readyToGrade(session);
        if (ready.isEmpty()) {
            throw new IllegalStateException("No one is ready to grade yet -- students need to finish and confirm their final answers first.");
        }
        finishParticipants(ready);
        if (allCurrentRunParticipants(session).isEmpty()) {
            endLiveTest(session);
        }
        return ready.size();
    }

    /**
     * Drawn (finger/stylus) signature, collected only after a PASSING
     * grade (Michael, 2026-08-17) -- a failing student sees a visual
     * indication instead (handled entirely client-side via
     * myDetailedStatus()'s passed field, no signature involved).
     * Stored on Certification, following the exact same file-path
     * pattern as pdfCertificateLink.
     */
    public void submitSignature(Long enrollmentId, String signatureDataUrl) {
        Enrollment enrollment = enrollmentRepository.findById(enrollmentId)
                .orElseThrow(() -> new IllegalArgumentException("Enrollment not found: " + enrollmentId));
        if (enrollment.getRosterStatus() != RosterStatus.CERTIFIED) {
            throw new IllegalStateException("A signature is only collected for a passing result.");
        }
        Certification certification = certificationRepository.findByEnrollmentId(enrollmentId)
                .orElseThrow(() -> new IllegalStateException("No certification found for this student yet."));

        try {
            String path = signatureStorageService.save(certification.getId(), signatureDataUrl);
            certification.setSignatureImagePath(path);
            certificationRepository.save(certification);
        } catch (java.io.IOException e) {
            throw new IllegalStateException("Could not save signature: " + e.getMessage());
        }
    }

    /**
     * colorCode uses the EXACT thresholds already documented in
     * Method9ScoringService's own comments -- 15% is a UI-only warning
     * color, never a scoring rule; 20%+ matches the real failedReading
     * threshold. Not a new scale -- this was already anticipated.
     */
    public record PointStatus(Long enrollmentId, String studentName, boolean submitted,
                               Short estimatedOpacity, Short deviation, String colorCode) {}

    /**
     * Revisit-aware: while a revisit is active, reports submission
     * status for the REVISITED point (among students who have an
     * existing answer there to revise), not the forward point --
     * matching myDetailedStatus()'s same reporting choice, so the
     * Operator sees who's actually updated their corrected answer.
     */
    public List<PointStatus> currentPointStatus(Session session) {
        if (!session.isLiveTestActive()) return List.of();
        Short revisitPoint = session.getLiveTestRevisitPointNumber();
        short targetPoint = revisitPoint != null ? revisitPoint : session.getLiveTestPointNumber();

        List<Enrollment> relevant = currentParticipants(session);
        if (revisitPoint != null) {
            relevant = relevant.stream()
                    .filter(e -> observationRepository.existsByCertificationRunIdAndPointNumber(e.getCertifyingRun().getId(), revisitPoint))
                    .toList();
        }

        return relevant.stream()
                .map(e -> {
                    Optional<Observation> obs = observationRepository.findByCertificationRunIdAndPointNumber(
                            e.getCertifyingRun().getId(), targetPoint);
                    return new PointStatus(
                            e.getId(),
                            e.getStudent().getName(),
                            obs.isPresent(),
                            obs.map(Observation::getStudentEstimatedOpacity).orElse(null),
                            obs.map(Observation::getDeviation).orElse(null),
                            obs.map(o -> colorCodeFor(o.getDeviation())).orElse(null));
                })
                .toList();
    }

    public static String colorCodeFor(short deviation) {
        if (deviation >= 20) return "RED";
        if (deviation >= 15) return "ORANGE";
        return "GREEN";
    }

    /**
     * A student's own submission history -- during an active test,
     * trueOpacityValue/deviation/colorCode stay null (matching the
     * earlier design call that live accuracy feedback is Operator/
     * Admin-only, never shown to the student mid-test). Once graded,
     * this same record carries the full breakdown for a POST-test
     * review (Michael, 2026-08-18): "students can identify how they
     * did and ask staff for guidance" -- a deliberately different case
     * from mid-test feedback, since nothing about a finished, graded
     * result can bias how they take the test.
     */
    public record MyPointSubmission(short pointNumber, PlumeColor color, short estimatedOpacity,
                                     Short trueOpacityValue, Short deviation, String colorCode) {}

    public record MyDetailedStatus(boolean active, Short pointCount, Short currentPointNumber, PlumeColor currentColor,
                                    boolean trueValueSet, boolean alreadySubmittedCurrentPoint,
                                    List<MyPointSubmission> myObservations,
                                    boolean submissionsComplete, boolean finalAnswersConfirmed,
                                    boolean graded, Boolean passed, boolean signatureSubmitted) {}

    /**
     * When a revisit is active AND this specific student has an
     * existing answer for that point, the revisit point is reported
     * AS IF it were the normal current point -- same field names,
     * same meaning -- so the testing webapp's existing row logic
     * (built before revisit existed) handles this correctly with zero
     * changes: whichever point is reported here just becomes editable.
     * If this student has nothing to revisit at that point, falls
     * through to reporting their real forward-progress point instead.
     *
     * graded/passed/signatureSubmitted (2026-08-17): graded is true the
     * moment gradeTest() has run for this student (run.inProgress flips
     * false); passed just reads RosterStatus == CERTIFIED, the SAME
     * authoritative source CertificationDeterminationService already
     * sets -- deliberately not re-deriving pass/fail here, since that
     * logic (including the White-retake + prior-Black combination) only
     * needs to exist in one place.
     *
     * A split-run participant's certifyingRun only covers their 25-point
     * White retake -- once graded, this method ALSO pulls in their
     * priorRun's Black observations (Michael, 2026-08-18, review
     * feature), so their review shows the complete 50-point picture,
     * not just half of it.
     */
    public MyDetailedStatus myDetailedStatus(Session session, Long enrollmentId) {
        Enrollment enrollment = enrollmentRepository.findById(enrollmentId)
                .orElseThrow(() -> new IllegalArgumentException("Enrollment not found: " + enrollmentId));

        boolean active = session.isLiveTestActive();
        Short pointCount = null;
        List<MyPointSubmission> myObservations = List.of();
        boolean alreadySubmitted = false;
        Short reportedPointNumber = session.getLiveTestPointNumber();
        PlumeColor reportedColor = session.getLiveTestColor();
        boolean reportedTrueValueSet = session.getLiveTestTrueOpacity() != null;
        boolean submissionsComplete = false;
        boolean finalAnswersConfirmed = false;
        boolean graded = false;
        Boolean passed = null;
        boolean signatureSubmitted = false;

        CertificationRun run = enrollment.getCertifyingRun();
        if (run != null) {
            pointCount = run.getPointCount();
            graded = !run.isInProgress();

            if (graded) {
                List<Observation> allObs = new ArrayList<>(
                        observationRepository.findByCertificationRunIdOrderByPointNumber(run.getId()));
                if (run.isSplitRetake() && run.getPriorRun() != null) {
                    allObs.addAll(observationRepository.findByCertificationRunIdAndColorOrderByPointNumber(
                            run.getPriorRun().getId(), PlumeColor.BLACK));
                }
                myObservations = allObs.stream()
                        .sorted(java.util.Comparator.comparing(Observation::getPointNumber))
                        .map(o -> new MyPointSubmission(o.getPointNumber(), o.getColor(), o.getStudentEstimatedOpacity(),
                                o.getTrueOpacityValue(), o.getDeviation(), colorCodeFor(o.getDeviation())))
                        .toList();
            } else {
                myObservations = observationRepository.findByCertificationRunIdOrderByPointNumber(run.getId()).stream()
                        .map(o -> new MyPointSubmission(o.getPointNumber(), o.getColor(), o.getStudentEstimatedOpacity(),
                                null, null, null))
                        .toList();
            }

            submissionsComplete = run.isSubmissionsComplete();
            finalAnswersConfirmed = run.isFinalAnswersConfirmed();

            if (graded) {
                passed = enrollment.getRosterStatus() == RosterStatus.CERTIFIED;
                if (passed) {
                    signatureSubmitted = certificationRepository.findByEnrollmentId(enrollmentId)
                            .map(c -> c.getSignatureImagePath() != null)
                            .orElse(false);
                }
            }

            if (active && run.isInProgress()) {
                Short revisitPoint = session.getLiveTestRevisitPointNumber();
                boolean inRevisitForThisStudent = revisitPoint != null
                        && observationRepository.existsByCertificationRunIdAndPointNumber(run.getId(), revisitPoint);

                if (inRevisitForThisStudent) {
                    reportedPointNumber = revisitPoint;
                    reportedColor = revisitPoint <= 25 ? PlumeColor.WHITE : PlumeColor.BLACK;
                    reportedTrueValueSet = true; // reuses the already-recorded value
                    alreadySubmitted = false; // always editable while the revisit is active
                } else {
                    alreadySubmitted = observationRepository.existsByCertificationRunIdAndPointNumber(
                            run.getId(), session.getLiveTestPointNumber());
                }
            }
        }

        return new MyDetailedStatus(
                active,
                pointCount,
                reportedPointNumber,
                reportedColor,
                reportedTrueValueSet,
                alreadySubmitted,
                myObservations,
                submissionsComplete,
                finalAnswersConfirmed,
                graded,
                passed,
                signatureSubmitted);
    }

    public record AdvanceResult(boolean testComplete, short pointNumber, PlumeColor color) {}

    /**
     * Manual fallback only -- tryAutoAdvance() (called automatically
     * from submitGuess()) handles the normal case now, so this should
     * rarely be needed. No longer grades anything itself -- that's
     * gradeTest()'s job exclusively, triggered once students have
     * confirmed their final answers. currentParticipants() already
     * excludes anyone whose submissionsComplete is true, so no special
     * split-run-at-25 handling is needed here anymore either -- that
     * exclusion happens automatically the moment submitGuess() sets it.
     */
    public AdvanceResult advance(Session session) {
        requireActive(session);
        if (session.getLiveTestRevisitPointNumber() != null) {
            throw new IllegalStateException(
                    "A revisit is active for point " + session.getLiveTestRevisitPointNumber() + " -- end it before advancing the main test.");
        }

        short justCompleted = session.getLiveTestPointNumber();
        List<Enrollment> stillActive = currentParticipants(session);
        if (stillActive.isEmpty()) {
            // Nothing left to gate on -- everyone still in play already
            // finished their points (auto-advance already handled this
            // silently); nothing more for a manual Advance to do here.
            return new AdvanceResult(true, justCompleted, session.getLiveTestColor());
        }

        List<PointStatus> status = currentPointStatus(session);
        boolean allSubmitted = status.stream().allMatch(PointStatus::submitted);
        if (!allSubmitted) {
            throw new IllegalStateException("Not everyone has submitted for the current point yet.");
        }

        short next = (short) (justCompleted + 1);
        List<Enrollment> stillActiveAfter = currentParticipants(session);

        if (stillActiveAfter.isEmpty() || next > 50) {
            return new AdvanceResult(true, justCompleted, session.getLiveTestColor());
        }

        PlumeColor nextColor = next <= 25 ? PlumeColor.WHITE : PlumeColor.BLACK;
        session.setLiveTestPointNumber(next);
        session.setLiveTestColor(nextColor);
        session.setLiveTestTrueOpacity(null);
        return new AdvanceResult(false, next, nextColor);
    }

    /**
     * Runs real Method 9 scoring and the SAME CertificationDetermination
     * Service logic the original batch-creation endpoint always used --
     * handles both a standard 50-point pass AND a White-retake combined
     * with its prior run's already-passing Black. Also records a
     * SplitRunAuthorization audit entry for any split-retake that
     * results in an actual certification (Section 3b's audit trail --
     * this entity already existed but nothing ever wrote to it).
     */
    private void finishParticipants(List<Enrollment> participants) {
        for (Enrollment e : participants) {
            CertificationRun run = e.getCertifyingRun();
            List<Observation> whiteObs = observationRepository.findByCertificationRunIdAndColorOrderByPointNumber(run.getId(), PlumeColor.WHITE);
            List<Observation> blackObs = observationRepository.findByCertificationRunIdAndColorOrderByPointNumber(run.getId(), PlumeColor.BLACK);
            scoringService.scoreRun(run, whiteObs, blackObs);
            run.setInProgress(false);
            CertificationRun savedRun = runRepository.save(run);

            Certification certification = determinationService.determineAndApply(savedRun, e.getId());

            if (certification != null && savedRun.isSplitRetake()) {
                SplitRunAuthorization auth = new SplitRunAuthorization();
                auth.setCertification(certification);
                auth.setAllowed(true);
                auth.setAuthorizedBy(savedRun.getAdministeredBy());
                auth.setRegionAtTime(savedRun.getSession().getRegion());
                auth.setFormatAtTime(savedRun.getSession().getFormat());
                splitRunAuthorizationRepository.save(auth);
            }
        }
    }

    private void endLiveTest(Session session) {
        session.setLiveTestActive(false);
        session.setLiveTestPointNumber(null);
        session.setLiveTestColor(null);
        session.setLiveTestTrueOpacity(null);
    }

    private void requireActive(Session session) {
        if (!session.isLiveTestActive()) {
            throw new IllegalStateException("No live test is currently active for session " + session.getId());
        }
    }
}
