package com.caa.platform.enrollment;

import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;

public interface EnrollmentRepository extends JpaRepository<Enrollment, Long> {
    List<Enrollment> findBySessionId(Long sessionId);
    List<Enrollment> findByRosterStatus(RosterStatus rosterStatus);

    /** Michael, 2026-08-23 -- used by EnrollmentController.create() to check this BEFORE attempting the insert, rather than letting the DB's own unique constraint (student_id, session_id) surface as a raw 500. */
    boolean existsByStudentIdAndSessionId(Long studentId, Long sessionId);

    /**
     * Michael, 2026-08-25 -- Client Portal Enroll rebuild. Replaces
     * existsByStudentIdAndSessionId() as the actual duplicate check in
     * EnrollmentController.create() -- the real unique constraint is
     * now (student_id, session_id, enrollment_components), not just
     * (student_id, session_id), since exactly one LECTURE_ONLY and one
     * FIELD_ONLY enrollment can legitimately coexist for the same
     * student/session pair ("Both"). The two-argument method above is
     * left in place -- it's still a valid, coarser query ("any
     * enrollment at all for this student/session") -- but is no longer
     * the right check for blocking a true duplicate.
     */
    boolean existsByStudentIdAndSessionIdAndEnrollmentComponents(Long studentId, Long sessionId, EnrollmentComponents enrollmentComponents);

    /** Michael, 2026-08-23 -- Client Page roster (Layer 2): a student's full enrollment history, used to derive "Last Field" (most recent certifying run) and "Enrolled" (any non-terminal roster status). */
    List<Enrollment> findByStudentId(Long studentId);

    /**
     * Michael, 2026-08-24 -- "Current Enrollments" (client-facing
     * portal): every enrollment for anyone who WORKS FOR this client
     * (Student.employerClient), across every session -- not scoped to
     * one session like the admin Roster page. Confirmed with Michael:
     * employer, not the enrollment's own client -- those can
     * legitimately differ (contractor/third-party cases), but
     * "Current Enrollments" is about the client's own people, same
     * definition "Manage Employees" already uses.
     */
    List<Enrollment> findByStudentEmployerClientId(Long clientId);

    /**
     * Michael, 2026-08-25 -- Brevo retention-gap target list (Section
     * 4a extension). Used to determine whether a client is currently
     * enrolled in ANY upcoming session, anywhere -- not just the one
     * campaign target -- so they don't get sent a "come back" email
     * for a school they've simply moved on from this season.
     */
    List<Enrollment> findByClientId(Long clientId);

    /**
     * Michael, 2026-08-29 -- performance audit finding: added to
     * eliminate the worst N+1 found in this audit --
     * BrevoTargetListService was calling findByClientId() once PER
     * historical client (potentially dozens/hundreds at a popular
     * school across real years of history), each call then looping
     * that one client's entire enrollment history with its OWN
     * separate SessionDay query per distinct session. This fetches
     * every enrollment for the whole set of clients in ONE query --
     * the caller then groups by client in memory (cheap; the
     * round-trip COUNT is what actually costs at scale).
     */
    List<Enrollment> findByClientIdIn(List<Long> clientIds);

    /**
     * Michael, 2026-08-29 -- staff calendar, Phase 1. Added so
     * calendar() can batch-fetch enrollment counts for every session
     * shown at once, rather than one query per session card -- the
     * exact class of mistake the performance audit just finished
     * fixing elsewhere, worth not reintroducing in brand-new code.
     */
    List<Enrollment> findBySessionIdIn(List<Long> sessionIds);
}
