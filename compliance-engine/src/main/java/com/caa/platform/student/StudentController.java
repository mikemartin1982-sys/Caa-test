package com.caa.platform.student;

import com.caa.platform.client.Client;
import com.caa.platform.client.ClientRepository;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

import java.util.List;
import java.util.Map;

/**
 * Backs /clients/{clientId}/students in api-contract/openapi.yaml.
 * Section 4e: "Manage their employees" -- the Client Portal's own roster
 * of students, distinct from any single Enrollment's billing client.
 */
@RestController
@RequestMapping("/api/v1/clients/{clientId}/students")
public class StudentController {

    private final StudentRepository studentRepository;
    private final ClientRepository clientRepository;
    private final StudentRosterService rosterService;
    private final com.caa.platform.enrollment.EnrollmentRepository enrollmentRepository;

    public StudentController(StudentRepository studentRepository, ClientRepository clientRepository,
                              StudentRosterService rosterService,
                              com.caa.platform.enrollment.EnrollmentRepository enrollmentRepository) {
        this.studentRepository = studentRepository;
        this.clientRepository = clientRepository;
        this.rosterService = rosterService;
        this.enrollmentRepository = enrollmentRepository;
    }

    @GetMapping
    public ResponseEntity<List<Student>> list(@PathVariable Long clientId) {
        return ResponseEntity.ok(studentRepository.findByEmployerClientId(clientId));
    }

    /**
     * Michael, 2026-08-23 -- Client Page roster (Layer 2). A new,
     * separate endpoint rather than changing list()'s shape -- that
     * one is already a real consumer (Manual Enroll's student
     * dropdown), which only needs id/name/studentNumber and shouldn't
     * pay the cost of this endpoint's derived-data computation
     * (per-student enrollment history + lazy session lookups) for a
     * simple dropdown. @Transactional to safely resolve the lazy
     * Session fields StudentRosterService reads.
     */
    @GetMapping("/roster")
    @org.springframework.transaction.annotation.Transactional(readOnly = true)
    public ResponseEntity<List<StudentRosterService.RosterEntry>> roster(@PathVariable Long clientId) {
        List<Student> students = studentRepository.findByEmployerClientId(clientId);
        List<StudentRosterService.RosterEntry> entries = students.stream()
                .map(s -> rosterService.buildEntry(s, enrollmentRepository.findByStudentId(s.getId())))
                .toList();
        return ResponseEntity.ok(entries);
    }

    public record CreateStudentRequest(String name, String phone, String email) {}

    /**
     * Michael, 2026-08-23 -- email is now required and format-validated,
     * not optional -- certificates are emailed to the student on
     * successful certification, so a Student record without a valid
     * email would have nowhere to actually send that to. See
     * EnrollmentController's own create() for the enrollment-time
     * backstop check covering existing records created before this.
     */
    @PostMapping
    public ResponseEntity<?> create(@PathVariable Long clientId, @RequestBody CreateStudentRequest req) {
        if (!com.caa.platform.common.EmailValidator.isValid(req.email())) {
            return ResponseEntity.unprocessableEntity().body(Map.of("error", "A valid email address is required."));
        }

        Client client = clientRepository.findById(clientId)
                .orElseThrow(() -> new IllegalArgumentException("Client not found: " + clientId));

        Student student = new Student();
        student.setEmployerClient(client);
        student.setName(req.name());
        student.setPhone(req.phone());
        student.setEmail(req.email());
        // Michael, 2026-08-23, found live: the studentNumber column is
        // NOT NULL, but the real, final value ("S" + this row's own
        // id) can't be known until AFTER the first insert generates
        // that id -- a short-lived placeholder satisfies the
        // constraint for this one save, then gets overwritten below.
        student.setStudentNumber("PENDING");
        student = studentRepository.save(student);

        student.setStudentNumber(generateStudentNumber(student.getId()));
        student = studentRepository.save(student);

        return ResponseEntity.status(HttpStatus.CREATED).body(student);
    }

    public record UpdateStudentRequest(String name, String phone, String email, Boolean active, Boolean lectureFeeExempt) {}

    /**
     * Michael, 2026-08-25 -- Section 4a extension, piece 3A: certification
     * history on the Student page. Confirmed with Michael: only actual
     * CERTIFIED outcomes count as "certifications" here -- an
     * enrollment that never certified isn't part of a student's
     * certification history, even though it's still a real, unrelated
     * enrollment record on its own. Same ownership check every other
     * client-scoped student endpoint here already has.
     */
    public record CertificationHistoryEntry(Long enrollmentId, Long sessionId, String sessionLocationName,
                                              com.caa.platform.session.SchoolType schoolType,
                                              com.caa.platform.enrollment.EnrollmentComponents enrollmentComponents,
                                              Integer runNumber, java.time.OffsetDateTime performedAt,
                                              Boolean whitePass, Boolean blackPass) {}

    @GetMapping("/{studentId}/certification-history")
    @org.springframework.transaction.annotation.Transactional(readOnly = true)
    public ResponseEntity<?> certificationHistory(@PathVariable Long clientId, @PathVariable Long studentId) {
        Student student = studentRepository.findById(studentId)
                .orElseThrow(() -> new IllegalArgumentException("Student not found: " + studentId));

        if (student.getEmployerClient() == null || !student.getEmployerClient().getId().equals(clientId)) {
            return ResponseEntity.status(HttpStatus.CONFLICT)
                    .body(Map.of("error", "This student does not currently belong to the client in the URL."));
        }

        List<CertificationHistoryEntry> history = enrollmentRepository.findByStudentId(studentId).stream()
                .filter(e -> e.getRosterStatus() == com.caa.platform.enrollment.RosterStatus.CERTIFIED)
                .map(e -> new CertificationHistoryEntry(
                        e.getId(),
                        e.getSession().getId(),
                        e.getSession().getLocationName(),
                        e.getSession().getSchoolType(),
                        e.getEnrollmentComponents(),
                        e.getCertifyingRun() != null ? e.getCertifyingRun().getRunNumber() : null,
                        e.getCertifyingRun() != null ? e.getCertifyingRun().getPerformedAt() : null,
                        e.getCertifyingRun() != null ? e.getCertifyingRun().getWhitePass() : null,
                        e.getCertifyingRun() != null ? e.getCertifyingRun().getBlackPass() : null
                ))
                .sorted((a, b) -> {
                    if (a.performedAt() == null) return 1;
                    if (b.performedAt() == null) return -1;
                    return b.performedAt().compareTo(a.performedAt());
                })
                .toList();

        return ResponseEntity.ok(history);
    }

    /**
     * Michael, 2026-08-23 -- Client Page roster rebuild (Layer 1) --
     * there was no way to edit an existing Student at all before this,
     * matching the same gap Client had before tonight. Partial update,
     * same pattern as Client/Session's own update(). email is
     * re-validated if changed -- same requirement as create(), not
     * relaxed just because the record already exists.
     */
    @PatchMapping("/{studentId}")
    /**
     * Michael, 2026-08-24 -- found while building the client-facing
     * "mark inactive" feature: clientId was accepted as a path
     * parameter here but never actually validated against anything --
     * any caller could patch any studentId regardless of the clientId
     * in the URL. Never a real problem for the admin side (staff are
     * trusted to edit any student), but this same endpoint is about to
     * be exposed to client-facing, untrusted callers, where a client
     * must only ever be able to modify their OWN employees. Added the
     * same ownership check reassign() already has, below.
     */
    public ResponseEntity<?> update(@PathVariable Long clientId, @PathVariable Long studentId, @RequestBody UpdateStudentRequest req) {
        Student student = studentRepository.findById(studentId)
                .orElseThrow(() -> new IllegalArgumentException("Student not found: " + studentId));

        if (student.getEmployerClient() == null || !student.getEmployerClient().getId().equals(clientId)) {
            return ResponseEntity.status(HttpStatus.CONFLICT)
                    .body(Map.of("error", "This student does not currently belong to the client in the URL."));
        }

        if (req.email() != null) {
            if (!com.caa.platform.common.EmailValidator.isValid(req.email())) {
                return ResponseEntity.unprocessableEntity().body(Map.of("error", "A valid email address is required."));
            }
            student.setEmail(req.email());
        }
        if (req.name() != null) student.setName(req.name());
        if (req.phone() != null) student.setPhone(req.phone());
        if (req.active() != null) student.setActive(req.active());
        if (req.lectureFeeExempt() != null) student.setLectureFeeExempt(req.lectureFeeExempt());

        return ResponseEntity.ok(studentRepository.save(student));
    }

    /**
     * Michael, 2026-08-24 -- "Reassign Employee": moves a student to a
     * different employer -- their own record's identity/studentNumber
     * doesn't change, and past Enrollment history is deliberately left
     * untouched (an enrollment's own client field is a separate,
     * historical record of who was actually responsible at the time --
     * same employer/billing-client distinction already established for
     * the VR gate work; reassignment only changes who they work for
     * going forward). A dedicated endpoint, not folded into update()
     * above -- this is its own distinct action, matching DIBs' own
     * separate "Reassign Employee" process, not a routine field edit.
     *
     * clientId in the path is confirmed to be the student's CURRENT
     * employer before the move, so a mistaken URL (reassigning what's
     * actually believed to be someone else's employee) is caught
     * rather than silently reassigning regardless.
     */
    public record ReassignStudentRequest(Long newClientId) {}

    @PatchMapping("/{studentId}/reassign")
    public ResponseEntity<?> reassign(@PathVariable Long clientId, @PathVariable Long studentId, @RequestBody ReassignStudentRequest req) {
        Student student = studentRepository.findById(studentId)
                .orElseThrow(() -> new IllegalArgumentException("Student not found: " + studentId));

        if (student.getEmployerClient() == null || !student.getEmployerClient().getId().equals(clientId)) {
            return ResponseEntity.status(HttpStatus.CONFLICT)
                    .body(Map.of("error", "This student does not currently belong to the client in the URL."));
        }

        if (req.newClientId() == null) {
            return ResponseEntity.unprocessableEntity().body(Map.of("error", "newClientId is required."));
        }
        if (req.newClientId().equals(clientId)) {
            return ResponseEntity.unprocessableEntity().body(Map.of("error", "This student is already assigned to that client."));
        }

        Client newClient = clientRepository.findById(req.newClientId())
                .orElseThrow(() -> new IllegalArgumentException("Client not found: " + req.newClientId()));

        // Michael, 2026-08-24 -- found via real DIBs source for this
        // exact page: the destination can't be a "singleton" (their
        // own term for a client representing exactly one person,
        // usually created for VR-only access). Confirmed with Michael:
        // ClientType.INDIVIDUAL is this project's own structured,
        // modern equivalent of that same concept -- an Individual
        // account exists to represent one specific person, so
        // reassigning a different student into it would break that
        // invariant, same reasoning DIBs already had, just enforced
        // through a real field instead of a naming convention ("vr_"
        // prefix) that could be applied inconsistently.
        if (newClient.getClientType() == com.caa.platform.client.ClientType.INDIVIDUAL) {
            return ResponseEntity.status(HttpStatus.CONFLICT)
                    .body(Map.of("error", "This client is an Individual account, representing exactly one person -- another employee can't be reassigned to it."));
        }

        student.setEmployerClient(newClient);
        return ResponseEntity.ok(studentRepository.save(student));
    }

    /**
     * Michael, 2026-08-24 -- "Combine Employee" backend: merges a
     * duplicate Student record into a survivor. NOT a hard delete --
     * matches the same soft, audit-friendly preference already
     * established (active flag, blocking deletion of a certified
     * enrollment): the duplicate is marked inactive with mergedInto
     * pointing at the survivor, so the old record stays visible with a
     * clear trail instead of simply vanishing.
     *
     * The real landmine: Enrollment has a unique constraint on
     * (student_id, session_id). If the survivor and the duplicate each
     * already have their own separate enrollment in the SAME session --
     * a real possibility if staff didn't realize they were the same
     * person -- repointing the duplicate's enrollment would violate
     * that constraint outright. Checked explicitly and rejected with a
     * clear, specific error BEFORE touching anything, rather than
     * attempting to silently guess which enrollment should win, or
     * letting it fail midway through as a raw DB error (same class of
     * bug already found and fixed twice: the phone column, the
     * duplicate-enrollment unique constraint on create()).
     *
     * @Transactional -- repointing potentially many enrollments plus
     * updating the duplicate needs to be all-or-nothing; a failure
     * partway through must not leave a half-merged state.
     */
    public record CombineStudentsRequest(Long duplicateStudentId) {}

    @PostMapping("/{studentId}/combine")
    @org.springframework.transaction.annotation.Transactional
    public ResponseEntity<?> combine(@PathVariable Long clientId, @PathVariable Long studentId, @RequestBody CombineStudentsRequest req) {
        if (req.duplicateStudentId() == null) {
            return ResponseEntity.unprocessableEntity().body(Map.of("error", "duplicateStudentId is required."));
        }
        if (req.duplicateStudentId().equals(studentId)) {
            return ResponseEntity.unprocessableEntity().body(Map.of("error", "A student can't be combined with themselves."));
        }

        Student a = studentRepository.findById(studentId)
                .orElseThrow(() -> new IllegalArgumentException("Student not found: " + studentId));
        Student b = studentRepository.findById(req.duplicateStudentId())
                .orElseThrow(() -> new IllegalArgumentException("Student not found: " + req.duplicateStudentId()));

        // Michael, 2026-08-24 -- found in real DIBs source for this
        // exact page (confirmSubmit()'s own emp1 > emp2 comparison):
        // the LOWER id always survives, regardless of which one the
        // caller names as the "survivor" in the URL vs the body --
        // the older, original record wins every time, not whichever
        // one happened to get clicked first. Enforced here on the
        // server rather than trusted to the UI, so this guarantee
        // holds even for a direct API call or a differently-built UI
        // later, not just this one form.
        Student survivor = a.getId() < b.getId() ? a : b;
        Student duplicate = a.getId() < b.getId() ? b : a;

        if (survivor.getMergedInto() != null) {
            return ResponseEntity.status(HttpStatus.CONFLICT)
                    .body(Map.of("error", "The survivor record is itself already a merged duplicate -- combine into its own survivor instead."));
        }
        if (duplicate.getMergedInto() != null) {
            return ResponseEntity.status(HttpStatus.CONFLICT)
                    .body(Map.of("error", "This student record has already been merged into another survivor."));
        }

        List<com.caa.platform.enrollment.Enrollment> duplicateEnrollments = enrollmentRepository.findByStudentId(duplicate.getId());

        // Michael, 2026-08-25 -- component-aware now (Client Portal
        // Enroll rebuild). The real unique constraint is (student,
        // session, enrollment_components), not just (student, session)
        // -- so a survivor with a FIELD_ONLY enrollment and a duplicate
        // with a LECTURE_ONLY enrollment for the SAME session no longer
        // conflicts; only a genuine same-component clash (both have
        // FIELD_ONLY, or both have LECTURE_ONLY, for the same session)
        // actually blocks the merge now. Using the old, coarser
        // existsByStudentIdAndSessionId() here would have incorrectly
        // blocked merges that are actually fine under the new schema.
        List<Long> conflictingSessionIds = duplicateEnrollments.stream()
                .filter(e -> enrollmentRepository.existsByStudentIdAndSessionIdAndEnrollmentComponents(
                        survivor.getId(), e.getSession().getId(), e.getEnrollmentComponents()))
                .map(e -> e.getSession().getId())
                .toList();
        if (!conflictingSessionIds.isEmpty()) {
            return ResponseEntity.status(HttpStatus.CONFLICT)
                    .body(Map.of("error", "Both students are separately enrolled in the same session (for the same lecture/field component), which can't be automatically combined -- resolve manually first.",
                            "conflictingSessionIds", conflictingSessionIds));
        }

        for (com.caa.platform.enrollment.Enrollment enrollment : duplicateEnrollments) {
            enrollment.setStudent(survivor);
        }
        enrollmentRepository.saveAll(duplicateEnrollments);

        duplicate.setActive(false);
        duplicate.setMergedInto(survivor);
        studentRepository.save(duplicate);

        return ResponseEntity.ok(studentRepository.findById(survivor.getId()).orElseThrow());
    }

    /**
     * Michael, 2026-08-23 -- was a millisecond timestamp ("S" +
     * System.currentTimeMillis()), producing huge, unwieldy numbers
     * like S1787498908490. DIBs' own real numbering is sequential
     * starting from 1, currently around 30,000 -- switched to this
     * row's own sequential database id instead, which naturally
     * starts at 1 on a fresh database (matching the actual Hostinger
     * deployment, which starts empty per the earlier hosting
     * decision). Reconciling this against DIBs' real, existing
     * ~30,000 numbers is a genuine question for the separate,
     * still-unscoped DIBs data migration project -- not something
     * this local dev numbering needs to solve.
     */
    private String generateStudentNumber(Long id) {
        return "S" + id;
    }
}
