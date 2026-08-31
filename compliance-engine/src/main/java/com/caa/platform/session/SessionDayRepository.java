package com.caa.platform.session;

import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;
import java.util.Optional;

public interface SessionDayRepository extends JpaRepository<SessionDay, Long> {
    List<SessionDay> findBySessionIdOrderByDayNumber(Long sessionId);

    /** Earliest calendar day for a session -- used as "the session's date" for reporting/comparison purposes. */
    Optional<SessionDay> findFirstBySessionIdOrderBySessionDateAsc(Long sessionId);

    /**
     * Michael, 2026-08-29 -- performance audit finding: every caller
     * needing "the earliest date" for a SET of sessions (session list
     * filtering, the staff calendar, session comparison, the Brevo
     * target list) was calling findFirstBySessionIdOrderBySessionDateAsc()
     * once PER session inside a loop -- a real N+1: one query per row,
     * scaling directly with however many sessions exist rather than
     * staying flat. This fetches every SessionDay for the whole set of
     * sessions in ONE query -- callers then group by session id and
     * take the earliest per group in memory (cheap; the round-trip
     * COUNT is what actually costs at scale, not raw row count).
     */
    List<SessionDay> findBySessionIdIn(List<Long> sessionIds);

    /**
     * Michael, 2026-08-29 -- staff calendar, Phase 1. The calendar
     * needs "every SessionDay in this date range" -- a genuinely
     * different query shape than every other method here, which are
     * all scoped by session id, not by date.
     */
    List<SessionDay> findBySessionDateBetween(java.time.LocalDate start, java.time.LocalDate end);

    /**
     * Michael, 2026-08-29 -- the actual "group by session, keep the
     * earliest" transformation, done once here rather than
     * reimplemented slightly differently in each of the three callers
     * (SessionController.calendar(), SessionComparisonService,
     * BrevoTargetListService) that all needed the exact same shape.
     * A default method keeps this co-located with the batch query it
     * depends on, rather than scattered as a static utility elsewhere.
     */
    default java.util.Map<Long, java.time.LocalDate> earliestDatesBySessionId(List<Long> sessionIds) {
        if (sessionIds.isEmpty()) {
            return java.util.Map.of();
        }
        return findBySessionIdIn(sessionIds).stream()
                .collect(java.util.stream.Collectors.toMap(
                        d -> d.getSession().getId(),
                        SessionDay::getSessionDate,
                        (existing, candidate) -> candidate.isBefore(existing) ? candidate : existing
                ));
    }
}
