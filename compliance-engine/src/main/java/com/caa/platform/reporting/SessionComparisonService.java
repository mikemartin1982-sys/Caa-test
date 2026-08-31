package com.caa.platform.reporting;

import com.caa.platform.client.Client;
import com.caa.platform.enrollment.Enrollment;
import com.caa.platform.enrollment.EnrollmentRepository;
import com.caa.platform.enrollment.RosterStatus;
import com.caa.platform.session.*;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.time.LocalDate;
import java.util.*;
import java.util.stream.Collectors;

/**
 * Section 4a: Public sessions only. Auto-matches the current/upcoming
 * session against its up to 4 most recent PAST sessions at the same
 * school (locationName + addressState, the closest available proxy for
 * "school" -- there's no dedicated School entity), strict match, no
 * regional fallback (Section 4a's "Resolved" note). Output is one
 * consolidated report with a summary row per session followed immediately
 * by client-level detail rows, matching the real DIBs export structure.
 */
@Service
public class SessionComparisonService {

    private final SessionRepository sessionRepository;
    private final SessionDayRepository sessionDayRepository;
    private final EnrollmentRepository enrollmentRepository;

    public SessionComparisonService(SessionRepository sessionRepository,
                                     SessionDayRepository sessionDayRepository,
                                     EnrollmentRepository enrollmentRepository) {
        this.sessionRepository = sessionRepository;
        this.sessionDayRepository = sessionDayRepository;
        this.enrollmentRepository = enrollmentRepository;
    }

    public record ClientEnrollmentRow(Long clientId, String clientName, String clientFirst, String clientLast,
                                       String email, String phone, int enrollCount) {}

    /** Comp/DNA/DNC only populated for completed historical sessions -- null on the current/upcoming block. */
    public record SessionSummary(Long sessionId, String sessionName, LocalDate date, int enrollCount,
                                  Integer compCount, Integer dnaCount, Integer dncCount, Integer uniqueClientCount) {}

    public record ComparisonBlock(SessionSummary summary, List<ClientEnrollmentRow> clientRows) {}

    public record ComparisonReport(ComparisonBlock current, List<ComparisonBlock> historical) {}

    /**
     * @Transactional -- buildBlock() (called from within this method)
     * fetches Enrollments and then directly calls .getClient() on them
     * in the same call chain, which is a LAZY relation. Same class of
     * fix as SessionController.roster() -- see that comment for the
     * full explanation of why this matters.
     *
     * Michael, 2026-08-29 -- performance audit finding: this method
     * was loading every Public session ever created via findAll() to
     * find same-school candidates, then making a SEPARATE database
     * query for each candidate's earliest SessionDay -- a full-table
     * scan compounded by a real N+1. Fixed the same two ways as
     * SessionController.calendar(): the schoolType==PUBLIC filter now
     * reaches the database itself, and every candidate's earliest date
     * is fetched in one batch query instead of one per row. Same
     * results, purely faster.
     */
    @Transactional(readOnly = true)
    public ComparisonReport compare(Long targetSessionId) {
        Session target = sessionRepository.findById(targetSessionId)
                .orElseThrow(() -> new IllegalArgumentException("Session not found: " + targetSessionId));

        if (target.getSchoolType() != SchoolType.PUBLIC) {
            throw new IllegalArgumentException("Session comparison (Section 4a) applies to Public sessions only.");
        }

        LocalDate targetDate = sessionDayRepository.findFirstBySessionIdOrderBySessionDateAsc(target.getId())
                .map(SessionDay::getSessionDate)
                .orElse(null);

        ComparisonBlock currentBlock = buildBlock(target, targetDate, false);

        List<Session> candidates = sessionRepository.findBySchoolType(SchoolType.PUBLIC).stream()
                .filter(s -> !s.getId().equals(target.getId()))
                .filter(s -> sameSchool(s, target))
                .toList();

        java.util.Map<Long, LocalDate> candidateDates = sessionDayRepository.earliestDatesBySessionId(
                candidates.stream().map(Session::getId).toList());

        // Local record instead of Map.entry() -- Map.entry() requires both
        // key and value to be non-null (Objects.requireNonNull internally),
        // but a session with no SessionDay scheduled yet legitimately has
        // a null date here, which needs to flow through to be filtered
        // out below, not throw an NPE on construction.
        record SessionWithDate(Session session, LocalDate date) {}

        List<ComparisonBlock> historical = candidates.stream()
                .map(s -> new SessionWithDate(s, candidateDates.get(s.getId())))
                .filter(e -> e.date() != null)
                .filter(e -> targetDate == null || e.date().isBefore(targetDate))
                .sorted((a, b) -> b.date().compareTo(a.date())) // most recent first
                .limit(4)
                .map(e -> buildBlock(e.session(), e.date(), true))
                .toList();

        return new ComparisonReport(currentBlock, historical);
    }

    private boolean sameSchool(Session a, Session b) {
        return Objects.equals(a.getLocationName(), b.getLocationName())
                && Objects.equals(a.getAddressState(), b.getAddressState());
    }

    private ComparisonBlock buildBlock(Session session, LocalDate date, boolean isCompleted) {
        List<Enrollment> enrollments = enrollmentRepository.findBySessionId(session.getId());

        Integer comp = null, dna = null, dnc = null, uniqueClients = null;
        if (isCompleted) {
            comp = (int) enrollments.stream().filter(e -> e.getRosterStatus() == RosterStatus.CERTIFIED).count();
            dna = (int) enrollments.stream().filter(e -> e.getRosterStatus() == RosterStatus.DNA).count();
            dnc = (int) enrollments.stream().filter(e -> e.getRosterStatus() == RosterStatus.DNC).count();
            uniqueClients = (int) enrollments.stream().map(e -> e.getClient().getId()).distinct().count();
        }

        SessionSummary summary = new SessionSummary(
                session.getId(), session.getLocationName(), date, enrollments.size(), comp, dna, dnc, uniqueClients);

        Map<Client, List<Enrollment>> byClient = enrollments.stream()
                .collect(Collectors.groupingBy(Enrollment::getClient));

        List<ClientEnrollmentRow> rows = byClient.entrySet().stream()
                .map(e -> new ClientEnrollmentRow(
                        e.getKey().getId(),
                        e.getKey().getRecordName(),
                        e.getKey().getFirstName(),
                        e.getKey().getLastName(),
                        e.getKey().getEmail(),
                        e.getKey().getPhone(),
                        e.getValue().size()))
                .toList();

        return new ComparisonBlock(summary, rows);
    }
}
