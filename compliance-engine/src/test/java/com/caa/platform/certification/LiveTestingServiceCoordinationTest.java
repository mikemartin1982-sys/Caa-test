package com.caa.platform.certification;

import com.caa.platform.enrollment.Enrollment;
import com.caa.platform.enrollment.EnrollmentRepository;
import com.caa.platform.enrollment.RosterStatus;
import com.caa.platform.equipment.TestingSystemRepository;
import com.caa.platform.session.Session;
import com.caa.platform.student.Student;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.Test;
import org.junit.jupiter.api.extension.ExtendWith;
import org.mockito.Mock;
import org.mockito.junit.jupiter.MockitoExtension;

import java.util.HashMap;
import java.util.ArrayList;
import java.util.List;
import java.util.Map;
import java.util.Optional;

import static org.junit.jupiter.api.Assertions.*;
import static org.mockito.ArgumentMatchers.any;
import static org.mockito.Mockito.*;

@ExtendWith(MockitoExtension.class)
class LiveTestingServiceCoordinationTest {
    @Mock private EnrollmentRepository enrollments;
    @Mock private CertificationRunRepository runs;
    @Mock private ObservationRepository observations;
    @Mock private TestingSystemRepository testingSystems;
    @Mock private Method9ScoringService scoring;
    @Mock private SplitRunEligibilityService eligibility;
    @Mock private CertificationDeterminationService determination;
    @Mock private SplitRunAuthorizationRepository splitRunAuthorizations;
    @Mock private CertificationRepository certifications;
    @Mock private SignatureStorageService signatures;

    private LiveTestingService service;
    private Session session;
    private Enrollment firstStudent;
    private Enrollment secondStudent;
    private List<Enrollment> participants;
    private final Map<String, Observation> stored = new HashMap<>();

    @BeforeEach
    void setUp() {
        service = new LiveTestingService(enrollments, runs, observations, testingSystems,
                scoring, eligibility, determination, splitRunAuthorizations, certifications, signatures);

        session = new Session();
        session.setId(14L);
        session.setLiveTestActive(true);
        session.setLiveTestPointNumber((short) 5);
        session.setLiveTestColor(PlumeColor.WHITE);
        session.setLiveTestTrueOpacity((short) 50);

        configureParticipants(2);

        when(observations.findByCertificationRunIdAndPointNumber(anyLong(), anyShort()))
                .thenAnswer(invocation -> Optional.ofNullable(stored.get(key(
                        invocation.getArgument(0), invocation.getArgument(1)))));
        when(observations.findByCertificationRunIdOrderByPointNumber(anyLong()))
                .thenAnswer(invocation -> stored.values().stream()
                        .filter(observation -> observation.getCertificationRun().getId()
                                .equals(invocation.getArgument(0)))
                        .sorted(java.util.Comparator.comparing(Observation::getPointNumber))
                        .toList());
        when(observations.save(any(Observation.class))).thenAnswer(invocation -> {
            Observation observation = invocation.getArgument(0);
            stored.put(key(observation.getCertificationRun().getId(), observation.getPointNumber()), observation);
            return observation;
        });
        doAnswer(invocation -> {
            Observation observation = invocation.getArgument(0);
            short deviation = (short) Math.abs(
                    observation.getTrueOpacityValue() - observation.getStudentEstimatedOpacity());
            observation.setDeviation(deviation);
            observation.setFailedReading(deviation >= 20);
            return null;
        }).when(scoring).scoreObservation(any(Observation.class));
    }

    @Test
    void waitsForEveryStudentThenAdvancesOnceWithSharedTrueValue() {
        service.submitGuess(session, 126L, (short) 45);

        assertEquals((short) 5, session.getLiveTestPointNumber());
        assertEquals((short) 50, session.getLiveTestTrueOpacity());
        assertTrue(stored.containsKey(key(101L, (short) 5)));
        assertFalse(stored.containsKey(key(102L, (short) 5)));

        service.submitGuess(session, 127L, (short) 40);

        Observation first = stored.get(key(101L, (short) 5));
        Observation second = stored.get(key(102L, (short) 5));
        assertEquals((short) 50, first.getTrueOpacityValue());
        assertEquals((short) 50, second.getTrueOpacityValue());
        assertEquals((short) 6, session.getLiveTestPointNumber());
        assertEquals(PlumeColor.WHITE, session.getLiveTestColor());
        assertNull(session.getLiveTestTrueOpacity());
        verify(observations, times(2)).save(any(Observation.class));
    }

    @Test
    void fiftyStudentClassAdvancesOnlyAfterFinalSubmission() {
        configureParticipants(50);

        for (int index = 0; index < participants.size(); index++) {
            service.submitGuess(session, participants.get(index).getId(), (short) (index % 21));

            if (index < participants.size() - 1) {
                assertEquals((short) 5, session.getLiveTestPointNumber(),
                        "class advanced before student " + (index + 2) + " submitted");
                assertEquals((short) 50, session.getLiveTestTrueOpacity());
            }
        }

        assertEquals(50, stored.size());
        assertTrue(stored.values().stream()
                .allMatch(observation -> observation.getTrueOpacityValue() == 50));
        assertEquals((short) 6, session.getLiveTestPointNumber());
        assertNull(session.getLiveTestTrueOpacity());
        verify(observations, times(50)).save(any(Observation.class));
    }

    @Test
    void dncStudentNoLongerBlocksRemainingClass() {
        service.submitGuess(session, 126L, (short) 45);

        assertThrows(IllegalStateException.class, () -> service.advance(session),
                "an unanswered participating student must block advancement");
        assertEquals((short) 5, session.getLiveTestPointNumber());

        secondStudent.setRosterStatus(RosterStatus.DNC);
        LiveTestingService.AdvanceResult result = service.advance(session);

        assertFalse(result.testComplete());
        assertEquals((short) 6, result.pointNumber());
        assertEquals((short) 6, session.getLiveTestPointNumber());
        assertNull(session.getLiveTestTrueOpacity());
        assertFalse(stored.containsKey(key(102L, (short) 5)),
                "DNC must not invent an answer for the departing student");
    }

    private void configureParticipants(int count) {
        List<CertificationRun> configuredRuns = new ArrayList<>();
        List<Enrollment> configuredEnrollments = new ArrayList<>();
        for (int index = 0; index < count; index++) {
            CertificationRun run = run(101L + index);
            configuredRuns.add(run);
            configuredEnrollments.add(enrollment(
                    126L + index,
                    index == 0 ? "Michael Martin" : "Student " + (index + 1),
                    run));
        }
        participants = List.copyOf(configuredEnrollments);
        firstStudent = participants.get(0);
        secondStudent = participants.get(1);
        stored.clear();

        when(runs.findBySessionIdOrderByRunNumber(14L)).thenReturn(configuredRuns);
        when(enrollments.findBySessionId(14L)).thenReturn(participants);
        when(enrollments.findById(anyLong())).thenAnswer(invocation -> {
            Long enrollmentId = invocation.getArgument(0);
            return participants.stream()
                    .filter(enrollment -> enrollment.getId().equals(enrollmentId))
                    .findFirst();
        });
    }

    private static CertificationRun run(long id) {
        CertificationRun run = new CertificationRun();
        run.setId(id);
        run.setRunNumber(1);
        run.setPointCount((short) 50);
        run.setInProgress(true);
        return run;
    }

    private static Enrollment enrollment(long id, String name, CertificationRun run) {
        Student student = new Student();
        student.setName(name);
        Enrollment enrollment = new Enrollment();
        enrollment.setId(id);
        enrollment.setStudent(student);
        enrollment.setCertifyingRun(run);
        return enrollment;
    }

    private static String key(Long runId, Short pointNumber) {
        return runId + ":" + pointNumber;
    }
}
