package com.caa.platform.student;

import com.caa.platform.enrollment.Enrollment;
import com.caa.platform.enrollment.RosterStatus;
import org.springframework.stereotype.Service;

import java.time.LocalDate;
import java.time.OffsetDateTime;
import java.util.Comparator;
import java.util.List;

/**
 * Michael, 2026-08-23 -- Client Page roster (Layer 2). Builds the
 * derived, per-student data DIBs' "View Employees" page shows that
 * isn't a stored Student column -- Last Field (from certification
 * history) and current Enrolled status (from non-terminal enrollment
 * history). Last Lecture is NOT derived here -- lectureComplete/
 * lectureCompletionDate are already direct, person-level Student
 * columns (see Student's own Javadoc), so there's nothing to compute
 * for that one.
 */
@Service
public class StudentRosterService {

    /** Confirmed with Michael, 2026-08-23: a certification's underlying lecture stays valid for exactly 1 calendar year from performedAt. */
    private static final int VALID_DAYS = 365;

    /** Matches DIBs' real, existing 60-day expiration-notice email job (not itself built here -- see CertificationRecencyStatus's own Javadoc). */
    private static final int NEAR_EXPIRATION_WINDOW_DAYS = 60;

    public record RosterEntry(
            Long id, String studentNumber, String name, String email, String phone, boolean active,
            boolean lectureComplete, LocalDate lectureCompletionDate, String lectureCompletionSource,
            OffsetDateTime lastFieldDate, CertificationRecencyStatus lastFieldStatus, Long lastFieldSessionId,
            Long currentEnrollmentSessionId, String currentEnrollmentSessionName, boolean currentEnrollmentIsVr
    ) {}

    public RosterEntry buildEntry(Student student, List<Enrollment> studentEnrollments) {
        Enrollment mostRecentCertified = studentEnrollments.stream()
                .filter(e -> e.getCertifyingRun() != null)
                .max(Comparator.comparing(e -> e.getCertifyingRun().getPerformedAt()))
                .orElse(null);

        OffsetDateTime lastFieldDate = mostRecentCertified != null
                ? mostRecentCertified.getCertifyingRun().getPerformedAt() : null;
        CertificationRecencyStatus lastFieldStatus = lastFieldDate != null ? computeStatus(lastFieldDate) : null;
        Long lastFieldSessionId = mostRecentCertified != null ? mostRecentCertified.getSession().getId() : null;

        // "Currently enrolled" -- any enrollment that hasn't reached a
        // terminal roster status yet (null = not yet set, or ARR).
        // Matches DIBs showing the upcoming session's roster link
        // instead of "Enroll Employee" once someone's already on one.
        Enrollment currentEnrollment = studentEnrollments.stream()
                .filter(e -> e.getRosterStatus() == null || !e.getRosterStatus().isTerminal())
                .findFirst()
                .orElse(null);

        return new RosterEntry(
                student.getId(), student.getStudentNumber(), student.getName(), student.getEmail(), student.getPhone(),
                student.isActive(),
                student.isLectureComplete(), student.getLectureCompletionDate(), student.getLectureCompletionSource(),
                lastFieldDate, lastFieldStatus, lastFieldSessionId,
                currentEnrollment != null ? currentEnrollment.getSession().getId() : null,
                currentEnrollment != null ? currentEnrollment.getSession().getLocationName() : null,
                currentEnrollment != null && currentEnrollment.getSession().isVrSession()
        );
    }

    /**
     * Purely informational (see CertificationRecencyStatus's own
     * Javadoc for the full reasoning) -- CAA is a certification
     * vendor, not an enforcement agency, so this never blocks
     * anything; it's a display signal only.
     */
    private CertificationRecencyStatus computeStatus(OffsetDateTime lastFieldDate) {
        long daysSince = java.time.Duration.between(lastFieldDate, OffsetDateTime.now()).toDays();
        if (daysSince > VALID_DAYS) {
            return CertificationRecencyStatus.EXPIRED;
        }
        if (daysSince > VALID_DAYS - NEAR_EXPIRATION_WINDOW_DAYS) {
            return CertificationRecencyStatus.NEAR_EXPIRATION;
        }
        return CertificationRecencyStatus.CERTIFIED;
    }
}
