package com.caa.platform.equipment;

import com.caa.platform.session.Session;
import com.caa.platform.session.SessionDay;
import com.caa.platform.session.SessionDayRepository;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import java.time.LocalDate;
import java.time.LocalTime;
import java.time.ZoneId;
import java.util.List;

/** Staff-authenticated endpoints used directly by the field Chart Recorder. */
@RestController
@RequestMapping("/api/v1/chart-recorder")
public class ChartRecorderController {
    private static final ZoneId CAA_TIME_ZONE = ZoneId.of("America/Chicago");
    private final SessionDayRepository sessionDayRepository;
    private final TestingSystemRepository testingSystemRepository;
    private final CalibrationRecordRepository calibrationRecordRepository;

    public ChartRecorderController(SessionDayRepository sessionDayRepository,
            TestingSystemRepository testingSystemRepository,
            CalibrationRecordRepository calibrationRecordRepository) {
        this.sessionDayRepository = sessionDayRepository;
        this.testingSystemRepository = testingSystemRepository;
        this.calibrationRecordRepository = calibrationRecordRepository;
    }

    public record TodaySession(
            Long sessionId,
            String courseNumber,
            LocalDate date,
            LocalTime startTime,
            String schoolName,
            String schoolLocation,
            String fieldManager,
            String operator,
            List<String> proctors,
            String trailer,
            LocalDate lastFiveFilterTestDate,
            String daqId,
            String monitorId,
            String lightId,
            String photocellId) {}

    @GetMapping("/sessions/today")
    @PreAuthorize("hasAnyRole('STAFF', 'COMPLIANCE_ADMINISTRATOR')")
    @Transactional(readOnly = true)
    public List<TodaySession> today() {
        LocalDate today = LocalDate.now(CAA_TIME_ZONE);
        return sessionDayRepository.findBySessionDateOrderByStartTime(today).stream()
                .map(SessionDay::getSession)
                .distinct()
                .filter(session -> !session.isCanceled() && !session.isClosedOut())
                .map(session -> toTodaySession(session, today))
                .toList();
    }

    private TodaySession toTodaySession(Session session, LocalDate date) {
        SessionDay day = sessionDayRepository.findBySessionIdOrderByDayNumber(session.getId()).stream()
                .filter(candidate -> date.equals(candidate.getSessionDate()))
                .findFirst().orElseThrow();
        TestingSystem primarySystem = session.getTrailer() == null ? null
                : testingSystemRepository.findByTrailerId(session.getTrailer().getId()).stream()
                        .filter(system -> system.getDesignation() == SystemDesignation.PRIMARY)
                        .findFirst().orElse(null);
        LocalDate lastCalibrationDate = primarySystem == null ? null
                : calibrationRecordRepository
                        .findFirstByTestingSystemIdOrderByPerformedAtDesc(primarySystem.getId())
                        .map(record -> record.getPerformedAt().atZoneSameInstant(CAA_TIME_ZONE).toLocalDate())
                        .orElse(null);

        return new TodaySession(
                session.getId(), String.valueOf(session.getId()), date, day.getStartTime(),
                session.getLocationName(), schoolLocation(session),
                session.getFieldManager() != null ? session.getFieldManager().getName() : null,
                session.getOperator() != null ? session.getOperator().getName() : null,
                java.util.stream.Stream.of(session.getProctor1(), session.getProctor2(), session.getProctor3())
                        .filter(java.util.Objects::nonNull).map(com.caa.platform.staff.StaffUser::getName).toList(),
                session.getTrailer() != null ? session.getTrailer().getIdentifier() : null,
                lastCalibrationDate,
                primarySystem != null ? primarySystem.getDataSourceId() : null,
                primarySystem != null ? primarySystem.getMonitorId() : null,
                primarySystem != null ? primarySystem.getLightSourceId() : null,
                primarySystem != null ? primarySystem.getPhotoCellId() : null);
    }

    private String schoolLocation(Session session) {
        String facility = session.getFieldFacility();
        String city = session.getFieldCity() != null ? session.getFieldCity() : session.getAddressCity();
        String state = session.getFieldState() != null ? session.getFieldState() : session.getAddressState();
        return java.util.stream.Stream.of(facility, city, state)
                .filter(value -> value != null && !value.isBlank())
                .collect(java.util.stream.Collectors.joining(", "));
    }
}
