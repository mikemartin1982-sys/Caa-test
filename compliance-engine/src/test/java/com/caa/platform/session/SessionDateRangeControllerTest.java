package com.caa.platform.session;

import org.junit.jupiter.api.Test;

import java.time.LocalDate;
import java.util.List;

import static org.junit.jupiter.api.Assertions.assertEquals;
import static org.junit.jupiter.api.Assertions.assertTrue;
import static org.mockito.Mockito.mock;
import static org.mockito.Mockito.verifyNoInteractions;
import static org.mockito.Mockito.when;

class SessionDateRangeControllerTest {
    private final SessionDayRepository days = mock(SessionDayRepository.class);
    private final SessionDateRangeController controller = new SessionDateRangeController(days);

    @Test
    void returnsFirstAndLastDayPerSessionSortedByFirstDay() {
        when(days.findBySessionIdIn(List.of(1L, 2L, 3L))).thenReturn(List.of(
                day(1L, "2026-10-06"), day(1L, "2026-10-05"),   // two-day session, days out of order
                day(2L, "2026-09-30")));                         // session 3 has no days yet

        List<SessionDateRangeController.SessionDateRange> ranges = controller.dateRanges(List.of(1L, 2L, 3L));

        assertEquals(2, ranges.size());
        assertEquals(new SessionDateRangeController.SessionDateRange(2L, LocalDate.parse("2026-09-30"), LocalDate.parse("2026-09-30")), ranges.get(0));
        assertEquals(new SessionDateRangeController.SessionDateRange(1L, LocalDate.parse("2026-10-05"), LocalDate.parse("2026-10-06")), ranges.get(1));
    }

    @Test
    void noIdsMeansNoQuery() {
        assertTrue(controller.dateRanges(List.of()).isEmpty());
        verifyNoInteractions(days);
    }

    private static SessionDay day(long sessionId, String date) {
        Session session = new Session();
        session.setId(sessionId);
        SessionDay day = new SessionDay();
        day.setSession(session);
        day.setSessionDate(LocalDate.parse(date));
        return day;
    }
}
