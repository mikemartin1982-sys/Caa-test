package com.caa.platform.enrollment;
 
import com.caa.platform.client.Client;
import com.caa.platform.client.ClientRepository;
import com.caa.platform.session.Session;
import com.caa.platform.session.SessionAuthorizationService;
import com.caa.platform.session.SessionRepository;
import com.caa.platform.student.Student;
import com.caa.platform.student.StudentRepository;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;
 
import java.util.List;
import java.util.Map;
 
/**
 * Backs /enrollments in api-contract/openapi.yaml. No capacity check on
 * create (Section 4: sessions have no enrollment cap -- confirmed still
 * correct with Michael, 2026-08-23). QuickBooks invoice creation on
 * enrollment (Section 7) is a downstream integration concern, not
 * implemented in this scaffold pass -- see the compliance-engine
 * README for what's still unbuilt.
 */
@RestController
@RequestMapping("/api/v1/enrollments")
public class EnrollmentController {
 
    private final EnrollmentRepository enrollmentRepository;
    private final StudentRepository studentRepository;
    private final ClientRepository clientRepository;
    private final SessionRepository sessionRepository;
    private final VrEnrollmentEligibilityService vrEligibilityService;
    private final SessionAuthorizationService sessionAuthorizationService;
    private final PaymentRepository paymentRepository;
 
    public EnrollmentController(EnrollmentRepository enrollmentRepository,
                                 StudentRepository studentRepository,
                                 ClientRepository clientRepository,
                                 SessionRepository sessionRepository,
                                 VrEnrollmentEligibilityService vrEligibilityService,
                                 SessionAuthorizationService sessionAuthorizationService,
                                 PaymentRepository paymentRepository) {
        this.enrollmentRepository = enrollmentRepository;
        this.studentRepository = studentRepository;
        this.clientRepository = clientRepository;
        this.sessionRepository = sessionRepository;
        this.vrEligibilityService = vrEligibilityService;
        this.sessionAuthorizationService = sessionAuthorizationService;
        this.paymentRepository = paymentRepository;
    }
 
    /**
     * Michael, 2026-08-25 -- Client Portal Enroll rebuild. "components"
     * is request-level shorthand, not the stored value directly:
     * "LECTURE_ONLY" or "FIELD_ONLY" create exactly one Enrollment row;
     * "BOTH" creates two (one of each) -- confirmed with Michael as a
     * UI-level choice, not a third stored EnrollmentComponents value.
     */
    public record CreateEnrollmentRequest(Long studentId, Long clientId, Long sessionId, String components) {}
 
    /**
     * Michael, 2026-08-25 -- "Both" needs true atomicity: if creating
     * the second row would fail for any reason, the first must not be
     * left behind as an orphaned, half-finished enrollment. @Transactional
     * here guarantees that -- a thrown exception or an early return
     * from inside this method rolls back anything already saved in the
     * same call.
     *
     * Every existing gate (duplicate check, email backstop, VR
     * eligibility, client authorization) is re-run independently for
     * EACH component being created, not applied once and assumed to
     * cover both -- confirmed explicitly with Michael. These
     * particular checks are pure functions of (student/session/client)
     * that don't actually vary by component, so looping them is
     * intentionally redundant computation for "Both" -- the point is
     * robustness (no future refactor can accidentally skip a gate for
     * one of the two rows), not that the checks themselves differ.
     */
    @PostMapping
    @org.springframework.transaction.annotation.Transactional
    public ResponseEntity<?> create(@RequestBody CreateEnrollmentRequest req) {
        Student student = studentRepository.findById(req.studentId())
                .orElseThrow(() -> new IllegalArgumentException("Student not found: " + req.studentId()));
        Client client = clientRepository.findById(req.clientId())
                .orElseThrow(() -> new IllegalArgumentException("Client not found: " + req.clientId()));
        Session session = sessionRepository.findById(req.sessionId())
                .orElseThrow(() -> new IllegalArgumentException("Session not found: " + req.sessionId()));
 
        List<EnrollmentComponents> componentsToCreate;
        if ("BOTH".equalsIgnoreCase(req.components())) {
            componentsToCreate = List.of(EnrollmentComponents.LECTURE_ONLY, EnrollmentComponents.FIELD_ONLY);
        } else {
            try {
                componentsToCreate = List.of(EnrollmentComponents.valueOf(String.valueOf(req.components()).toUpperCase()));
            } catch (IllegalArgumentException e) {
                return ResponseEntity.unprocessableEntity()
                        .body(Map.of("error", "components must be LECTURE_ONLY, FIELD_ONLY, or BOTH."));
            }
        }
 
        List<Enrollment> created = new java.util.ArrayList<>();
        for (EnrollmentComponents component : componentsToCreate) {
            // Michael, 2026-08-23 -- found live during testing: the DB's
            // own unique constraint already prevented a duplicate
            // enrollment, but only by throwing a raw, unhandled 500 on
            // the insert -- same class of bug as the earlier phone NOT
            // NULL issue. Checked explicitly here instead, so a genuine,
            // real-world case (re-enrolling someone already on the
            // roster, for this same component) gets a clean, anticipated
            // 409 rather than a stack trace. Component-aware now -- the
            // real constraint is (student, session, components), not
            // just (student, session), since exactly one of each
            // component can legitimately coexist.
            if (enrollmentRepository.existsByStudentIdAndSessionIdAndEnrollmentComponents(req.studentId(), req.sessionId(), component)) {
                return ResponseEntity.status(HttpStatus.CONFLICT)
                        .body(Map.of("error", "This student is already enrolled in this session for " + component + "."));
            }
 
            // Michael, 2026-08-23 -- the real backstop: certificates are
            // emailed to the student on successful certification, so this
            // catches any student record that predates the email
            // requirement now enforced at creation time (StudentController),
            // or that otherwise ended up without a valid one. Checked here
            // independently rather than assuming creation-time validation
            // already covered it.
            if (!com.caa.platform.common.EmailValidator.isValid(student.getEmail())) {
                return ResponseEntity.status(HttpStatus.CONFLICT)
                        .body(Map.of("error", "This student does not have a valid email on file -- required so certificates can be sent upon certification."));
            }
 
            // Michael, 2026-08-23 -- a purely business/commercial gate, not
            // a regulatory one (see VrEnrollmentEligibilityService's own
            // Javadoc for the full reasoning). Checks the ENROLLMENT's own
            // client (who's paying/responsible for this specific
            // enrollment), not the student's employer -- those can
            // legitimately differ, confirmed intentional, not a gap.
            //
            // Michael, 2026-08-30 -- Lecture Certificate Upload feature:
            // VrEnrollmentEligibilityService.isEligible() now also checks
            // lecture completion (a federal Alt-152-A precondition, a
            // genuinely different rule than the commercial gate above --
            // see that service's own Javadoc). A single boolean can't
            // distinguish which of the two actually failed, and each
            // needs its own clear, actionable message -- a client who
            // isn't VR-flagged at all is a different problem for staff to
            // resolve than a real VR client whose specific student just
            // hasn't completed their lecture yet. Checked explicitly here
            // first, ahead of the existing commercial-gate message below,
            // so the more specific, per-student reason takes priority
            // when it's the actual cause.
            if (session.isVrSession() && client.isVrClient() && !student.isLectureComplete()) {
                return ResponseEntity.status(HttpStatus.CONFLICT)
                        .body(Map.of("error", "This student must have a completed lecture on file before attempting VR certification."));
            }
 
            if (!vrEligibilityService.isEligible(session, client, student)) {
                return ResponseEntity.status(HttpStatus.CONFLICT)
                        .body(Map.of("error", "This client is not flagged as a VR client and cannot enroll in a VR session."));
            }
 
            // Michael, 2026-08-23 -- confirmed as a real gap and fixed:
            // nothing previously checked whether this client is actually
            // allowed on this session at all. PUBLIC has no restriction;
            // PRIVATE/VTCA/PROPOSED are locked to the one host client;
            // SEMI_PRIVATE allows the host or anyone on the authorized-
            // outside-client list. Reuses SessionAuthorizationService,
            // which already enforces this same rule for adding clients to
            // a session in the first place.
            if (!sessionAuthorizationService.isClientAuthorizedToEnroll(session, client)) {
                return ResponseEntity.status(HttpStatus.CONFLICT)
                        .body(Map.of("error", "This client is not authorized on this session."));
            }
 
            Enrollment enrollment = new Enrollment();
            enrollment.setStudent(student);
            enrollment.setClient(client);
            enrollment.setSession(session);
            enrollment.setEnrollmentComponents(component);
            // Michael, 2026-08-25 -- LECTURE_ONLY grants lecture access
            // on creation. The actual self-paced lecture delivery
            // mechanism is still to be designed/wired in separately
            // (confirmed with Michael) -- this flag existed already and
            // is the real, correct place to record that access was
            // granted, even before that downstream piece exists.
            if (component == EnrollmentComponents.LECTURE_ONLY) {
                enrollment.setLectureAccessGranted(true);
            }
            created.add(enrollmentRepository.save(enrollment));
        }
 
        return ResponseEntity.status(HttpStatus.CREATED).body(created.size() == 1 ? created.get(0) : created);
    }
 
    public record UpdateRosterStatusRequest(RosterStatus rosterStatus) {}
 
    /**
     * Section 4f/4g: ARR and CERTIFIED are normally system-set (Digital
     * Testing sign-in, passing certification) -- this endpoint is
     * primarily for staff setting DNC/DNA.
     */
    @PatchMapping("/{enrollmentId}/roster-status")
    public ResponseEntity<Enrollment> updateRosterStatus(@PathVariable Long enrollmentId,
                                                           @RequestBody UpdateRosterStatusRequest req) {
        Enrollment enrollment = enrollmentRepository.findById(enrollmentId)
                .orElseThrow(() -> new IllegalArgumentException("Enrollment not found: " + enrollmentId));
        enrollment.setRosterStatus(req.rosterStatus());
        return ResponseEntity.ok(enrollmentRepository.save(enrollment));
    }
 
    /**
     * Michael, 2026-08-23 -- "unenroll cleanly" from the Session Roster
     * view. Confirmed with Michael: blocked entirely once a student has
     * actually been CERTIFIED in this session -- that has to remain a
     * permanent record (compliance/audit reasons), not something staff
     * can simply undo. ARR, DNC, and DNA are NOT certifications, so
     * unenrolling from any of those (or no status yet) is allowed --
     * covers the common cases (enrolled by mistake, changed their mind)
     * without touching real certification history.
     *
     * Also checks for an attached Payment before deleting -- Payment's
     * own FK to Enrollment is NOT NULL, so an unhandled delete here
     * would hit the same class of raw-500-on-a-DB-constraint bug
     * already found and fixed twice tonight (the phone column, the
     * duplicate-enrollment unique constraint). No real Payment records
     * exist in practice yet (QuickBooks integration isn't built), but
     * checked explicitly rather than assuming that stays true.
     */
    @DeleteMapping("/{enrollmentId}")
    public ResponseEntity<?> delete(@PathVariable Long enrollmentId) {
        Enrollment enrollment = enrollmentRepository.findById(enrollmentId)
                .orElseThrow(() -> new IllegalArgumentException("Enrollment not found: " + enrollmentId));
 
        if (enrollment.getRosterStatus() == RosterStatus.CERTIFIED) {
            return ResponseEntity.status(HttpStatus.CONFLICT)
                    .body(Map.of("error", "This student has already been certified in this session -- that record must remain permanent and can't be unenrolled."));
        }
 
        List<Payment> payments = paymentRepository.findByEnrollmentId(enrollmentId);
        if (!payments.isEmpty()) {
            return ResponseEntity.status(HttpStatus.CONFLICT)
                    .body(Map.of("error", "This enrollment has payment records attached and can't be deleted."));
        }
 
        enrollmentRepository.delete(enrollment);
        return ResponseEntity.noContent().build();
    }
}