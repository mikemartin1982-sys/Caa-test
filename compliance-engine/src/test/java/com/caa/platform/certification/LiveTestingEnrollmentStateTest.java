package com.caa.platform.certification;

import com.caa.platform.enrollment.Enrollment;
import com.caa.platform.enrollment.EnrollmentRepository;
import com.caa.platform.equipment.TestingSystemRepository;
import com.caa.platform.session.Session;
import org.junit.jupiter.api.Test;

import java.util.Optional;

import static org.junit.jupiter.api.Assertions.assertFalse;
import static org.junit.jupiter.api.Assertions.assertNull;
import static org.mockito.Mockito.mock;
import static org.mockito.Mockito.when;

class LiveTestingEnrollmentStateTest {
    @Test
    void enrollmentWithoutActiveRunIsNotShownAsTesting() {
        EnrollmentRepository enrollments = mock(EnrollmentRepository.class);
        LiveTestingService service = new LiveTestingService(
                enrollments,
                mock(CertificationRunRepository.class),
                mock(ObservationRepository.class),
                mock(TestingSystemRepository.class),
                mock(Method9ScoringService.class),
                mock(SplitRunEligibilityService.class),
                mock(CertificationDeterminationService.class),
                mock(SplitRunAuthorizationRepository.class),
                mock(CertificationRepository.class),
                mock(SignatureStorageService.class));

        Session session = new Session();
        session.setLiveTestActive(true);
        session.setLiveTestPointNumber((short) 5);
        Enrollment lateEnrollment = new Enrollment();
        lateEnrollment.setId(999L);
        when(enrollments.findById(999L)).thenReturn(Optional.of(lateEnrollment));

        LiveTestingService.MyDetailedStatus status = service.myDetailedStatus(session, 999L);

        assertFalse(status.active());
        assertNull(status.pointCount());
    }
}
