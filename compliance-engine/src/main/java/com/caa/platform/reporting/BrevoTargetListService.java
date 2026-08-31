package com.caa.platform.reporting;

import com.caa.platform.client.Client;
import com.caa.platform.enrollment.Enrollment;
import com.caa.platform.enrollment.EnrollmentRepository;
import com.caa.platform.session.Session;
import com.caa.platform.session.SessionDay;
import com.caa.platform.session.SessionDayRepository;
import com.caa.platform.session.SessionRepository;
import com.caa.platform.session.SchoolType;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.time.LocalDate;
import java.util.List;
import java.util.Objects;
import java.util.Set;
import java.util.stream.Collectors;

/**
 * Michael, 2026-08-25 -- Section 4a extension, built specifically to
 * drive Brevo enrollment-open campaigns (60/30/14-day notifications,
 * confirmed with Michael, matching the real augusta-ga-8499-30-day.html
 * sample). Output columns match the real Augusta_GA.xlsx sample
 * exactly (Client Name, Client First, Client Last, Email, Phone) --
 * this is a Brevo-import-ready list, not the fuller staff-facing
 * comparison dashboard SessionComparisonService already builds.
 *
 * The exclusion rule, confirmed with Michael: a client is dropped from
 * the list not just if they're already enrolled in THIS target
 * session, but if they're currently enrolled in ANY upcoming session,
 * anywhere -- "so we don't send a campaign to a client who has decided
 * to go to a different program." This is deliberately the same
 * traceability concept as piece 3B (student/client shift detection),
 * built directly into this list rather than as a separate report --
 * confirmed by Michael as working "in tandem," not as competing
 * pieces.
 */
@Service
public class BrevoTargetListService {

    private final SessionRepository sessionRepository;
    private final SessionDayRepository sessionDayRepository;
    private final EnrollmentRepository enrollmentRepository;

    public BrevoTargetListService(SessionRepository sessionRepository,
                                   SessionDayRepository sessionDayRepository,
                                   EnrollmentRepository enrollmentRepository) {
        this.sessionRepository = sessionRepository;
        this.sessionDayRepository = sessionDayRepository;
        this.enrollmentRepository = enrollmentRepository;
    }

    /** Column order and naming deliberately match Augusta_GA.xlsx exactly, for a direct Brevo import. */
    public record BrevoTargetRow(String clientName, String clientFirst, String clientLast, String email, String phone) {}

    /**
     * Michael, 2026-08-25 -- found live, in response to a direct
     * question: "are we still able to see where they went, rather than
     * that data being lost?" The underlying Enrollment data was never
     * actually lost -- the exclusion check itself depends on looking
     * it up -- but nothing surfaced it as its own, readable answer.
     * This closes that gap: for each excluded client, exactly where
     * they're currently enrolled instead, not just a silent drop from
     * the send list.
     */
    public record ExcludedClientRow(String clientName, String currentSessionLocation, LocalDate currentSessionDate) {}

    public record RetentionGapResult(List<BrevoTargetRow> targetList, List<ExcludedClientRow> excludedShiftedElsewhere) {}

    /**
     * @Transactional -- this method and its helpers call .getClient()
     * on Enrollments fetched in the same call chain, a LAZY relation.
     * Same class of fix as SessionController.roster() and
     * SessionComparisonService.compare() -- see those comments for the
     * full explanation of why this matters.
     *
     * Michael, 2026-08-29 -- performance audit finding: this was the
     * worst offender found in the whole audit. Two separate problems,
     * both fixed:
     *  1. The same "same school" full-table scan + per-candidate
     *     SessionDay N+1 as SessionComparisonService -- fixed the same
     *     way (schoolType filter pushed to the database, dates
     *     batch-fetched).
     *  2. findCurrentlyEnrolledUpcomingSession() (now removed) was
     *     called once PER historical client, and each call looped
     *     that one client's ENTIRE enrollment history with its own
     *     separate SessionDay query per distinct session -- at a
     *     popular school with real years of history, potentially
     *     hundreds of extra queries for a single list generation.
     *     Replaced with two batch fetches upfront (every enrollment
     *     for every historical client in one query, every relevant
     *     session's earliest date in one query), then the exclusion
     *     decision itself runs entirely in memory per client. Exact
     *     same rule as before, confirmed unchanged with Michael:
     *     current session plus up to 4 seasons back, excluded if
     *     currently enrolled in any upcoming session anywhere -- only
     *     HOW it's computed changed, not what it computes.
     */
    @Transactional(readOnly = true)
    public RetentionGapResult buildRetentionGapList(Long targetSessionId) {
        Session target = sessionRepository.findById(targetSessionId)
                .orElseThrow(() -> new IllegalArgumentException("Session not found: " + targetSessionId));

        if (target.getSchoolType() != SchoolType.PUBLIC) {
            throw new IllegalArgumentException("Brevo retention-gap targeting (Section 4a) applies to Public sessions only.");
        }

        LocalDate targetDate = sessionDayRepository.findFirstBySessionIdOrderBySessionDateAsc(target.getId())
                .map(SessionDay::getSessionDate)
                .orElse(null);

        // Michael, 2026-08-25 -- same "same school" matching as
        // SessionComparisonService (locationName + addressState, the
        // closest available proxy for "school" -- there's no dedicated
        // School entity). Deliberately re-implemented here rather than
        // shared, since it's a single two-field equality check -- not
        // worth coupling two otherwise-independent services over.
        List<Session> historicalSessions = sessionRepository.findBySchoolType(SchoolType.PUBLIC).stream()
                .filter(s -> !s.getId().equals(target.getId()))
                .filter(s -> Objects.equals(s.getLocationName(), target.getLocationName())
                        && Objects.equals(s.getAddressState(), target.getAddressState()))
                .toList();

        java.util.Map<Long, LocalDate> historicalDates = sessionDayRepository.earliestDatesBySessionId(
                historicalSessions.stream().map(Session::getId).toList());

        record SessionWithDate(Session session, LocalDate date) {}

        List<Session> pastFourSeasons = historicalSessions.stream()
                .map(s -> new SessionWithDate(s, historicalDates.get(s.getId())))
                .filter(e -> e.date() != null)
                .filter(e -> targetDate == null || e.date().isBefore(targetDate))
                .sorted((a, b) -> b.date().compareTo(a.date()))
                .limit(4)
                .map(SessionWithDate::session)
                .toList();

        // Clients with real history at this school, across the last 4 seasons.
        Set<Client> historicalClients = pastFourSeasons.stream()
                .flatMap(s -> enrollmentRepository.findBySessionId(s.getId()).stream())
                .map(Enrollment::getClient)
                .collect(Collectors.toSet());

        // Michael, 2026-08-29 -- the actual fix: every historical
        // client's FULL enrollment history, fetched in ONE query
        // rather than one query per client.
        List<Long> historicalClientIds = historicalClients.stream().map(Client::getId).toList();
        List<Enrollment> allEnrollmentsForHistoricalClients = enrollmentRepository.findByClientIdIn(historicalClientIds);

        // Every session any of those enrollments touch, with earliest
        // dates fetched in ONE query -- rather than one query per
        // distinct session per client.
        List<Long> relevantSessionIds = allEnrollmentsForHistoricalClients.stream()
                .map(e -> e.getSession().getId())
                .distinct()
                .toList();
        java.util.Map<Long, LocalDate> relevantSessionDates = sessionDayRepository.earliestDatesBySessionId(relevantSessionIds);

        java.util.Map<Client, List<Enrollment>> enrollmentsByClient = allEnrollmentsForHistoricalClients.stream()
                .collect(Collectors.groupingBy(Enrollment::getClient));

        LocalDate today = LocalDate.now();
        List<BrevoTargetRow> targetList = new java.util.ArrayList<>();
        List<ExcludedClientRow> excluded = new java.util.ArrayList<>();

        for (Client client : historicalClients) {
            // Michael, 2026-08-25 -- "currently enrolled" means enrolled
            // in a session whose earliest scheduled date is still in
            // the future -- ANY upcoming session, at ANY school, not
            // just the campaign's own target session. Old, completed
            // enrollments (the whole point of the 4-season lookback)
            // never count here; only a genuinely upcoming commitment
            // does. Entirely in-memory now -- no DB calls in this loop.
            Session currentUpcoming = enrollmentsByClient.getOrDefault(client, List.of()).stream()
                    .map(Enrollment::getSession)
                    .distinct()
                    .filter(s -> {
                        LocalDate d = relevantSessionDates.get(s.getId());
                        return d != null && !d.isBefore(today);
                    })
                    .findFirst()
                    .orElse(null);

            if (currentUpcoming == null) {
                targetList.add(new BrevoTargetRow(
                        client.getRecordName(), client.getFirstName(), client.getLastName(),
                        client.getEmail(), client.getPhone()));
            } else {
                excluded.add(new ExcludedClientRow(client.getRecordName(), currentUpcoming.getLocationName(),
                        relevantSessionDates.get(currentUpcoming.getId())));
            }
        }

        return new RetentionGapResult(targetList, excluded);
    }
}
