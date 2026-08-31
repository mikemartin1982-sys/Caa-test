package com.caa.platform.reporting;

import com.caa.platform.enrollment.Enrollment;
import com.caa.platform.enrollment.EnrollmentRepository;
import com.caa.platform.enrollment.RosterStatus;
import com.caa.platform.session.SchoolType;
import com.caa.platform.student.Student;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.time.LocalDate;
import java.time.OffsetDateTime;
import java.util.*;
import java.util.stream.Collectors;

/**
 * Section 4b: window anchors to each student's FIRST VR attempt (not a
 * sliding "last 7 days" view), and tracks attempt count through the
 * window. At the 7-day mark, "Not Yet Certified" students are surfaced
 * for STAFF REVIEW -- the platform never auto-sends the Brevo coaching
 * email; a staff member decides and triggers that send themselves
 * (Section 4b). This service only ever produces the readiness list.
 */
@Service
public class VRRollingStatusService {

    private final EnrollmentRepository enrollmentRepository;

    public VRRollingStatusService(EnrollmentRepository enrollmentRepository) {
        this.enrollmentRepository = enrollmentRepository;
    }

    public record RollingStatusEntry(Long studentId, String studentName, String phone, String email,
                                      LocalDate firstAttemptDate, int attemptCount, String status,
                                      boolean readyForStaffReview) {}

    /**
     * @Transactional -- fetches Enrollments then directly calls
     * .getSession() and groups by Enrollment::getStudent, both LAZY
     * relations. Same class of fix as SessionController.roster().
     */
    @Transactional(readOnly = true)
    public List<RollingStatusEntry> currentRollingStatus() {
        List<Enrollment> vrEnrollments = enrollmentRepository.findAll().stream()
                .filter(e -> e.getSession().isVrSession())
                .toList();

        Map<Student, List<Enrollment>> byStudent = vrEnrollments.stream()
                .collect(Collectors.groupingBy(Enrollment::getStudent));

        List<RollingStatusEntry> entries = new ArrayList<>();
        for (Map.Entry<Student, List<Enrollment>> e : byStudent.entrySet()) {
            Student student = e.getKey();
            List<Enrollment> attempts = e.getValue();

            OffsetDateTime firstAttempt = attempts.stream()
                    .map(Enrollment::getEnrollmentDate)
                    .filter(Objects::nonNull)
                    .min(OffsetDateTime::compareTo)
                    .orElse(null);
            if (firstAttempt == null) {
                continue;
            }

            boolean certified = attempts.stream().anyMatch(a -> a.getRosterStatus() == RosterStatus.CERTIFIED);
            String status = certified ? "CERTIFIED" : "NOT_YET_CERTIFIED";

            long daysSinceFirstAttempt = java.time.Duration.between(firstAttempt, OffsetDateTime.now()).toDays();
            boolean readyForStaffReview = !certified && daysSinceFirstAttempt >= 7;

            entries.add(new RollingStatusEntry(
                    student.getId(), student.getName(), student.getPhone(), student.getEmail(),
                    firstAttempt.toLocalDate(), attempts.size(), status, readyForStaffReview));
        }

        return entries;
    }
}
