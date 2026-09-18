package com.caa.platform.enrollment;

import com.caa.platform.session.SchoolType;
import com.caa.platform.session.Session;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.time.OffsetDateTime;
import java.util.List;

/**
 * Section 4f: the staff-facing Roster. Company/enrollmentDate/paymentStatus
 * only populate for Public and VR sessions -- Private/Semi-Private are
 * already scoped to a known host/authorized-client list, so that extra
 * column isn't needed there (Section 4f's actual stated rule).
 */
@Service
public class RosterService {

    /**
     * Michael, 2026-08-25 -- Client Portal Enroll rebuild. Added for
     * the same reason as ClientEnrollmentEntry's own comment below: a
     * "Both" enrollment is genuinely two separate records (one
     * Lecture, one Field) -- without this, the same student's name
     * would appear twice on a session's roster with nothing telling
     * staff which row is which.
     */
    /**
     * Michael, 2026-08-26 -- found live: the admin Roster page's
     * Company column has always linked nowhere real (a literal
     * href="#" placeholder in the view) -- this DTO never actually
     * carried the client's id, only its display name, so the view
     * had no real id to link to even if someone had gone to fix it.
     */
    /**
     * Michael, 2026-08-29 -- staff calendar Phase 5 (Roster Office-Use/
     * Field-Use split). studentEmail/studentPhone are new -- confirmed
     * with Michael as needed regardless of which mode someone's in,
     * since both office and field staff need a way to actually contact
     * a student directly.
     */
    public record RosterEntry(
            Long enrollmentId,
            Long studentId,
            String studentName,
            String studentEmail,
            String studentPhone,
            EnrollmentComponents enrollmentComponents,
            RosterStatus rosterStatus,
            boolean lectureComplete,
            Integer certificationRunNumber,
            Integer practiceRunNumber,
            Long clientId,
            String companyName,
            OffsetDateTime enrollmentDate,
            PaymentStatus paymentStatus,
            java.time.LocalDate fieldDate
    ) {}

    /**
     * @Transactional here joins whatever transaction the caller already
     * has open (Spring's default REQUIRED propagation) -- it does NOT by
     * itself fix a case where the enrollments were fetched in an earlier,
     * already-closed transaction and passed in afterward. That's why
     * SessionController.roster() (the actual caller) also has its own
     * @Transactional: it has to cover both the enrollment fetch AND this
     * method's use of their lazy fields in the SAME session, since
     * Hibernate proxies are permanently bound to the session that
     * created them and can't be "reconnected" to a later one.
     */
    @Transactional(readOnly = true)
    public List<RosterEntry> buildRoster(Session session, List<Enrollment> enrollments) {
        boolean showBillingColumns = session.getSchoolType() == SchoolType.PUBLIC
                || session.isVrSession();

        return enrollments.stream()
                .map(e -> new RosterEntry(
                        e.getId(),
                        e.getStudent().getId(),
                        e.getStudent().getName(),
                        e.getStudent().getEmail(),
                        e.getStudent().getPhone(),
                        e.getEnrollmentComponents(),
                        e.getRosterStatus(),
                        e.getStudent().isLectureComplete(),
                        e.getCertifyingRun() != null ? e.getCertifyingRun().getRunNumber() : null,
                        e.getPracticeRun() != null ? e.getPracticeRun().getRunNumber() : null,
                        showBillingColumns ? e.getClient().getId() : null,
                        showBillingColumns ? e.getClient().getRecordName() : null,
                        showBillingColumns ? e.getEnrollmentDate() : null,
                        showBillingColumns ? e.getPaymentStatus() : null,
                        // "Field Date" (Digital-Testing Admin, 2026-08-16):
                        // when this student's certifying run was performed.
                        // FMgr/Oper are NOT per-run here -- corrected per
                        // Michael: those come from the session's own
                        // fieldManager/operator (Session Details' TEAM
                        // section), shown uniformly for every row, not
                        // computed per-enrollment.
                        e.getCertifyingRun() != null && e.getCertifyingRun().getPerformedAt() != null
                                ? e.getCertifyingRun().getPerformedAt().toLocalDate() : null
                ))
                .toList();
    }

    /**
     * Michael, 2026-08-24 -- "Current Enrollments" (client-facing
     * portal): same shape of information as the admin Roster
     * (RosterEntry above), but scoped per-CLIENT across every session
     * their employees are in, not per-session across every enrollee.
     * A separate record, not a reuse of RosterEntry, because this
     * view needs to identify WHICH session each row belongs to (the
     * admin roster never does, since it's already scoped to one).
     * paymentStatus/companyName deliberately omitted -- billing/staff-
     * facing detail a client doesn't need about their own enrollment,
     * confirmed with Michael.
     */
    /**
     * Michael, 2026-08-25 -- Client Portal Enroll rebuild. Found live
     * while investigating a "duplicate" report: this DTO predates the
     * enrollmentComponents field entirely (built the night before),
     * so a "Both" selection's two real, separate rows (one
     * LECTURE_ONLY, one FIELD_ONLY, same session) had no way to be
     * told apart on this page -- they showed as the same location
     * twice with nothing distinguishing them, reading as an
     * unexplained duplicate rather than two legitimately different
     * records. Added here so the view can label each row clearly.
     */
    public record ClientEnrollmentEntry(
            Long enrollmentId,
            String studentName,
            Long sessionId,
            String sessionLocationName,
            com.caa.platform.session.SchoolType schoolType,
            boolean vrSession,
            EnrollmentComponents enrollmentComponents,
            RosterStatus rosterStatus,
            boolean lectureComplete,
            Integer certificationRunNumber,
            java.time.LocalDate fieldDate
    ) {}

    @Transactional(readOnly = true)
    public List<ClientEnrollmentEntry> buildClientEnrollmentsView(List<Enrollment> enrollments) {
        return enrollments.stream()
                .map(e -> new ClientEnrollmentEntry(
                        e.getId(),
                        e.getStudent().getName(),
                        e.getSession().getId(),
                        e.getSession().getLocationName(),
                        e.getSession().getSchoolType(),
                        e.getSession().isVrSession(),
                        e.getEnrollmentComponents(),
                        e.getRosterStatus(),
                        e.getStudent().isLectureComplete(),
                        e.getCertifyingRun() != null ? e.getCertifyingRun().getRunNumber() : null,
                        e.getCertifyingRun() != null && e.getCertifyingRun().getPerformedAt() != null
                                ? e.getCertifyingRun().getPerformedAt().toLocalDate() : null
                ))
                .toList();
    }
}
