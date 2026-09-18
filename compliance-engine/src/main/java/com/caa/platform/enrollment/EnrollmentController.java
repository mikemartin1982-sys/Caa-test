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
import org.springframework.transaction.annotation.Transactional;

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
    private final com.caa.platform.integration.qbo.QboPublicSessionInvoiceService qboPublicSessionInvoiceService;
    private final EnrollmentPricingService pricingService;
    private final com.caa.platform.integration.qbo.PrivateEnrollmentNotificationService privateEnrollmentNotificationService;

    public EnrollmentController(EnrollmentRepository enrollmentRepository,
                                 StudentRepository studentRepository,
                                 ClientRepository clientRepository,
                                 SessionRepository sessionRepository,
                                 VrEnrollmentEligibilityService vrEligibilityService,
                                 SessionAuthorizationService sessionAuthorizationService,
                                 PaymentRepository paymentRepository,
                                 com.caa.platform.integration.qbo.QboPublicSessionInvoiceService qboPublicSessionInvoiceService,
                                 EnrollmentPricingService pricingService,
                                 com.caa.platform.integration.qbo.PrivateEnrollmentNotificationService privateEnrollmentNotificationService) {
        this.enrollmentRepository = enrollmentRepository;
        this.studentRepository = studentRepository;
        this.clientRepository = clientRepository;
        this.sessionRepository = sessionRepository;
        this.vrEligibilityService = vrEligibilityService;
        this.sessionAuthorizationService = sessionAuthorizationService;
        this.paymentRepository = paymentRepository;
        this.qboPublicSessionInvoiceService = qboPublicSessionInvoiceService;
        this.pricingService = pricingService;
        this.privateEnrollmentNotificationService = privateEnrollmentNotificationService;
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

        // Michael, 2026-09-04 -- session close-out lock, confirmed with
        // Michael as matching DIBs' own, real behavior: a genuinely
        // absolute lock once closedOut, no in-app exception for any
        // staff member at all -- only a real, direct DB/API-level
        // intervention can reverse it. "No new enrollments should occur
        // once the session is complete and closed out" -- Michael's own
        // words. This is the one, real, shared endpoint every
        // enrollment path (Manual Enroll, Bulk Enroll, the Client
        // Portal) ultimately goes through, making it the right, single
        // place for this guard.
        if (session.isClosedOut()) {
            return ResponseEntity.status(HttpStatus.CONFLICT)
                    .body(Map.of("error", "This session is closed out -- no new enrollments can be created."));
        }

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
            Enrollment saved = enrollmentRepository.save(enrollment);
            created.add(saved);

            // Michael, 2026-09-02 -- Client Auto-Notify feature,
            // Private/Semi-Private path. Confirmed with Michael:
            // fires synchronously, right here, the instant an
            // enrollment is locked in -- not deferred, not batched,
            // and with no dependency on Payment/QBO at all, since
            // Private/Semi-Private don't bill until session close.
            // Strictly PRIVATE/SEMI_PRIVATE, and FIELD_ONLY or
            // LECTURE_ONLY (expanded 2026-09-03 -- Lecture template
            // now built and confirmed reusable across session types,
            // same as the Field one already is) -- PUBLIC already has
            // its own, separate, payment-based path
            // (QboPaymentNotificationService); firing both would mean
            // a Public student getting two separate emails. PROPOSED
            // is confirmed by Michael as never having real enrollments
            // at all; VTCA is confirmed as externally operated by
            // VTCA's own staff, so no notification from this system
            // belongs there either.
            if ((component == EnrollmentComponents.FIELD_ONLY || component == EnrollmentComponents.LECTURE_ONLY)
                    && (session.getSchoolType() == com.caa.platform.session.SchoolType.PRIVATE
                        || session.getSchoolType() == com.caa.platform.session.SchoolType.SEMI_PRIVATE)) {
                privateEnrollmentNotificationService.notifyStudentForEnrollment(saved);
            }
        }

        return ResponseEntity.status(HttpStatus.CREATED).body(created.size() == 1 ? created.get(0) : created);
    }

    public record UpdateRosterStatusRequest(RosterStatus rosterStatus) {}

    /**
     * Section 4f/4g: ARR and CERTIFIED are normally system-set (Digital
     * Testing sign-in, passing certification) -- this endpoint is
     * primarily for staff setting DNC/DNA.
     *
     * Michael, 2026-09-04 -- session close-out lock, same guard and
     * same reasoning as create() above -- confirmed with Michael as
     * matching DIBs' own, genuinely absolute lock (no in-app exception
     * for any staff member). In the real, normal workflow this should
     * never actually fire, since Michael confirmed close-out itself
     * requires every roster entry already be terminal (DNA/DNC/
     * Certified) first -- but closedOut itself is still just a plain,
     * manual checkbox with no real, technical enforcement of that
     * prerequisite, so this stays a real, defensive backstop rather
     * than trusting the process alone.
     */
    @PatchMapping("/{enrollmentId}/roster-status")
    @Transactional
    public ResponseEntity<?> updateRosterStatus(@PathVariable Long enrollmentId,
                                                           @RequestBody UpdateRosterStatusRequest req) {
        Enrollment enrollment = enrollmentRepository.findById(enrollmentId)
                .orElseThrow(() -> new IllegalArgumentException("Enrollment not found: " + enrollmentId));
        if (enrollment.getSession().isClosedOut()) {
            return ResponseEntity.status(HttpStatus.CONFLICT)
                    .body(Map.of("error", "This session is closed out -- roster status can no longer be changed."));
        }
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
     * Michael, 2026-09-03 -- found live during real testing: this used
     * to hard-block unenrolling once ANY Payment existed at all -- a
     * generic, defensive safeguard written back when the comment above
     * still said "no real Payment records exist in practice yet
     * (QuickBooks integration isn't built)." That's no longer true --
     * real Payment rows exist now, and staff have a genuine, real need
     * to unenroll a student who's already been invoiced (enrolled by
     * mistake, changed their mind after paying, etc.). Confirmed with
     * Michael: allow it, but this is deliberately NOT an automatic QBO
     * void/credit -- that stays a real, manual accounting step. This
     * endpoint's own job is only to (1) let the unenroll happen at all,
     * and (2) hand back every real, affected QBO invoice ID so the
     * caller can surface it -- Michael specifically wants this shown to
     * staff so they can tell Chasity exactly which invoice needs a
     * manual void/credit in QBO itself.
     *
     * @Transactional -- Payment's own FK to Enrollment is NOT NULL, so
     * both deletes (Payment first, then Enrollment) have to succeed or
     * fail together, not leave an orphaned, FK-violating half-state.
     */
    @DeleteMapping("/{enrollmentId}")
    @org.springframework.transaction.annotation.Transactional
    public ResponseEntity<?> delete(@PathVariable Long enrollmentId) {
        Enrollment enrollment = enrollmentRepository.findById(enrollmentId)
                .orElseThrow(() -> new IllegalArgumentException("Enrollment not found: " + enrollmentId));

        if (enrollment.getRosterStatus() == RosterStatus.CERTIFIED) {
            return ResponseEntity.status(HttpStatus.CONFLICT)
                    .body(Map.of("error", "This student has already been certified in this session -- that record must remain permanent and can't be unenrolled."));
        }

        List<Payment> payments = paymentRepository.findByEnrollmentId(enrollmentId);
        List<String> affectedInvoiceIds = payments.stream()
                .map(Payment::getQbInvoiceId)
                .filter(id -> id != null && !"NO_CHARGE".equals(id))
                .distinct()
                .toList();
        if (!payments.isEmpty()) {
            paymentRepository.deleteAll(payments);
        }

        enrollmentRepository.delete(enrollment);

        if (!affectedInvoiceIds.isEmpty()) {
            return ResponseEntity.ok(Map.of(
                    "unenrolled", true,
                    "affectedQboInvoiceIds", affectedInvoiceIds
            ));
        }
        return ResponseEntity.noContent().build();
    }

    public record PricingPreviewRequest(List<Long> enrollmentIds) {}
    public record EnrollmentPricingLine(Long enrollmentId, java.math.BigDecimal price) {}
    public record PricingPreviewResponse(List<EnrollmentPricingLine> lines, boolean anyLate, java.math.BigDecimal lateFeeAmount) {}

    /**
     * Michael, 2026-09-01 -- QBO Per-Student Invoicing, Stage 3B.
     * Read-only preview of what generatePublicInvoice() would actually
     * charge, without mutating anything -- the confirmation screen
     * needs to show real prices before any of the three invoice
     * buttons are clicked, and EnrollmentPricingService's own
     * computePrice()/isLateEnrollment() had no way to be called from
     * outside this service until now. Batch-fetched in one call for
     * every ID at once, not one request per student -- the same
     * N+1-avoidance principle already established elsewhere in this
     * project.
     *
     * Michael, 2026-09-01 -- found live: a real
     * LazyInitializationException. Enrollment.session/client are both
     * lazy relationships -- findAllById() returns proxies, not
     * fully-loaded data, and computePrice() reads real fields off
     * both (session.getSchoolType(), client.getVrPricingOverrideRate(),
     * etc.). Without its own @Transactional, the DB transaction that
     * fetched the enrollments had already closed by the time the loop
     * tried to lazily initialize those proxies -- generatePublicInvoice()
     * already had @Transactional and never hit this; this method
     * simply didn't. readOnly = true since this never mutates anything.
     */
    @org.springframework.transaction.annotation.Transactional(readOnly = true)
    @PostMapping("/pricing-preview")
    public ResponseEntity<?> pricingPreview(@RequestBody PricingPreviewRequest req) {
        if (req.enrollmentIds() == null || req.enrollmentIds().isEmpty()) {
            return ResponseEntity.unprocessableEntity().body(Map.of("error", "enrollmentIds is required and cannot be empty."));
        }
        List<Enrollment> enrollments = enrollmentRepository.findAllById(req.enrollmentIds());
        List<EnrollmentPricingLine> lines = new java.util.ArrayList<>();
        boolean anyLate = false;
        for (Enrollment e : enrollments) {
            lines.add(new EnrollmentPricingLine(e.getId(), pricingService.computePrice(e)));
            if (pricingService.isLateEnrollment(e)) {
                anyLate = true;
            }
        }
        return ResponseEntity.ok(new PricingPreviewResponse(lines, anyLate, pricingService.lateFeeAmount()));
    }

    public record GeneratePublicInvoiceRequest(List<Long> enrollmentIds) {}

    /**
     * Michael, 2026-09-01 -- QBO Per-Student Invoicing, Stage 3B. The
     * real invoice-generation endpoint -- powers both "invoice just-now
     * registered" (specific enrollment IDs from the just-completed
     * batch) and "invoice all un-invoiced for this client+session"
     * (IDs from that same repository query, Stage 1) equally, since
     * both are fundamentally the same operation on a list of
     * enrollment IDs -- the caller decides which list to send.
     */
    @PostMapping("/generate-public-invoice")
    public ResponseEntity<?> generatePublicInvoice(@RequestBody GeneratePublicInvoiceRequest req) {
        if (req.enrollmentIds() == null || req.enrollmentIds().isEmpty()) {
            return ResponseEntity.unprocessableEntity().body(Map.of("error", "enrollmentIds is required and cannot be empty."));
        }
        try {
            Map<String, Object> invoice = qboPublicSessionInvoiceService.generatePublicSessionInvoice(req.enrollmentIds());
            return ResponseEntity.ok(invoice);
        } catch (IllegalArgumentException e) {
            return ResponseEntity.status(HttpStatus.CONFLICT).body(Map.of("error", e.getMessage()));
        }
    }

    /**
     * Michael, 2026-09-01 -- QBO Per-Student Invoicing, Stage 3B. Real
     * IDs behind the "Invoice ALL Un-Invoiced Registrations" button --
     * exposes Stage 1's own EnrollmentRepository query (already built,
     * never actually connected to a controller until now).
     */
    @GetMapping("/un-invoiced")
    public ResponseEntity<List<Enrollment>> unInvoiced(@RequestParam Long clientId, @RequestParam Long sessionId) {
        return ResponseEntity.ok(enrollmentRepository.findByClientIdAndSessionIdAndPaymentStatusAndOutsideAttendeeFalse(
                clientId, sessionId, PaymentStatus.PENDING));
    }

    public record PrivateNotificationRow(Long enrollmentId, String studentName, String clientName,
                                          com.caa.platform.session.SchoolType schoolType, String component,
                                          java.time.OffsetDateTime brevoNotifiedAt, String brevoNotificationError) {}

    /**
     * Michael, 2026-09-03 -- Client Auto-Notify feature, invoice-
     * checker readout for the Private/Semi-Private path (the Public
     * one lives on PaymentController -- this is a genuinely separate
     * table on the same page, not merged in, since "QBO Invoice" as a
     * concept doesn't apply here at all -- these enrollments aren't
     * billed at enrollment time).
     */
    @GetMapping("/private-notifications")
    @org.springframework.transaction.annotation.Transactional(readOnly = true)
    public ResponseEntity<List<PrivateNotificationRow>> privateNotifications() {
        List<com.caa.platform.session.SchoolType> schoolTypes = List.of(
                com.caa.platform.session.SchoolType.PRIVATE, com.caa.platform.session.SchoolType.SEMI_PRIVATE);
        java.time.OffsetDateTime cutoff = java.time.OffsetDateTime.now().minusDays(7);
        List<Enrollment> rows = enrollmentRepository.findPrivateNotificationRows(schoolTypes, cutoff);
        return ResponseEntity.ok(rows.stream().map(this::toPrivateNotificationRow).toList());
    }

    /**
     * Michael, 2026-09-03 -- real, manual retry, mirroring the Public
     * path's own retry-notification endpoint. Safe to call more than
     * once for the same enrollment -- notifyStudentForEnrollment()'s
     * own guard (added alongside this) skips anything already
     * genuinely notified.
     */
    @PostMapping("/{enrollmentId}/retry-notification")
    @org.springframework.transaction.annotation.Transactional
    public ResponseEntity<List<PrivateNotificationRow>> retryPrivateNotification(@PathVariable Long enrollmentId) {
        Enrollment enrollment = enrollmentRepository.findById(enrollmentId)
                .orElseThrow(() -> new IllegalArgumentException("Enrollment not found: " + enrollmentId));
        privateEnrollmentNotificationService.notifyStudentForEnrollment(enrollment);

        List<com.caa.platform.session.SchoolType> schoolTypes = List.of(
                com.caa.platform.session.SchoolType.PRIVATE, com.caa.platform.session.SchoolType.SEMI_PRIVATE);
        java.time.OffsetDateTime cutoff = java.time.OffsetDateTime.now().minusDays(7);
        List<Enrollment> rows = enrollmentRepository.findPrivateNotificationRows(schoolTypes, cutoff);
        return ResponseEntity.ok(rows.stream().map(this::toPrivateNotificationRow).toList());
    }

    private PrivateNotificationRow toPrivateNotificationRow(Enrollment enrollment) {
        return new PrivateNotificationRow(
                enrollment.getId(),
                enrollment.getStudent().getName(),
                enrollment.getClient().getRecordName(),
                enrollment.getSession().getSchoolType(),
                enrollment.getEnrollmentComponents().name(),
                enrollment.getBrevoNotifiedAt(),
                enrollment.getBrevoNotificationError()
        );
    }
}
