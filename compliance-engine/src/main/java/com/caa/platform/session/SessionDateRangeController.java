package com.caa.platform.session;

import org.springframework.transaction.annotation.Transactional;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.bind.annotation.RestController;

import java.time.LocalDate;
import java.util.Comparator;
import java.util.List;
import java.util.Map;
import java.util.stream.Collectors;

/**
 * Michael, 2026-09-27 -- first and last day for a set of sessions, for the
 * public "By Location / Date" list (Laravel already has the session list;
 * Session itself carries no dates, they live in session_days). Kept out of
 * SessionController so it stays a small, independently testable read, and
 * so the public list doesn't have to use the staff calendar endpoint, which
 * also computes staff/equipment availability for every day in its range.
 */
@RestController
@RequestMapping("/api/v1/sessions")
public class SessionDateRangeController {

    public record SessionDateRange(Long sessionId, LocalDate firstDate, LocalDate lastDate) {}

    private final SessionDayRepository sessionDayRepository;

    public SessionDateRangeController(SessionDayRepository sessionDayRepository) {
        this.sessionDayRepository = sessionDayRepository;
    }

    /** Sessions with no days yet are simply absent from the result. */
    @GetMapping("/date-ranges")
    @Transactional(readOnly = true)
    public List<SessionDateRange> dateRanges(@RequestParam List<Long> ids) {
        if (ids.isEmpty()) {
            return List.of();
        }
        Map<Long, List<LocalDate>> datesBySession = sessionDayRepository.findBySessionIdIn(ids).stream()
                .collect(Collectors.groupingBy(d -> d.getSession().getId(),
                        Collectors.mapping(SessionDay::getSessionDate, Collectors.toList())));

        return datesBySession.entrySet().stream()
                .map(e -> new SessionDateRange(e.getKey(),
                        e.getValue().stream().min(Comparator.naturalOrder()).orElseThrow(),
                        e.getValue().stream().max(Comparator.naturalOrder()).orElseThrow()))
                .sorted(Comparator.comparing(SessionDateRange::firstDate).thenComparing(SessionDateRange::sessionId))
                .toList();
    }
}
