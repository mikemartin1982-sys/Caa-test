package com.caa.platform.enrollment;

import com.caa.platform.certification.CertificationRun;
import com.caa.platform.client.ClientRepository;
import com.caa.platform.integration.qbo.PrivateEnrollmentNotificationService;
import com.caa.platform.integration.qbo.QboPublicSessionInvoiceService;
import com.caa.platform.session.Session;
import com.caa.platform.session.SessionAuthorizationService;
import com.caa.platform.session.SessionRepository;
import com.caa.platform.student.StudentRepository;
import org.junit.jupiter.api.Test;

import java.util.Optional;

import static org.junit.jupiter.api.Assertions.assertFalse;
import static org.junit.jupiter.api.Assertions.assertNotNull;
import static org.junit.jupiter.api.Assertions.assertNull;
import static org.junit.jupiter.api.Assertions.assertTrue;
import static org.mockito.Mockito.mock;
import static org.mockito.Mockito.verify;
import static org.mockito.Mockito.when;

class EnrollmentControllerRosterStatusTest {
    @Test
    void dncClosesActiveCertificationRunWithoutDeletingIt() {
        EnrollmentRepository enrollments = mock(EnrollmentRepository.class);
        EnrollmentController controller = controller(enrollments);
        Enrollment enrollment = activeEnrollment();
        when(enrollments.findById(127L)).thenReturn(Optional.of(enrollment));
        when(enrollments.save(enrollment)).thenReturn(enrollment);

        controller.updateRosterStatus(127L,
                new EnrollmentController.UpdateRosterStatusRequest(RosterStatus.DNC));

        assertFalse(enrollment.getCertifyingRun().isInProgress());
        assertNotNull(enrollment.getCertifyingRun().getAbandonedAt());
        verify(enrollments).save(enrollment);
    }

    @Test
    void arrivedStatusDoesNotCloseActiveCertificationRun() {
        EnrollmentRepository enrollments = mock(EnrollmentRepository.class);
        EnrollmentController controller = controller(enrollments);
        Enrollment enrollment = activeEnrollment();
        when(enrollments.findById(126L)).thenReturn(Optional.of(enrollment));
        when(enrollments.save(enrollment)).thenReturn(enrollment);

        controller.updateRosterStatus(126L,
                new EnrollmentController.UpdateRosterStatusRequest(RosterStatus.ARR));

        assertTrue(enrollment.getCertifyingRun().isInProgress());
        assertNull(enrollment.getCertifyingRun().getAbandonedAt());
        verify(enrollments).save(enrollment);
    }

    private static Enrollment activeEnrollment() {
        Session session = new Session();
        Enrollment enrollment = new Enrollment();
        enrollment.setSession(session);
        CertificationRun run = new CertificationRun();
        run.setInProgress(true);
        enrollment.setCertifyingRun(run);
        return enrollment;
    }

    private static EnrollmentController controller(EnrollmentRepository enrollments) {
        return new EnrollmentController(
                enrollments,
                mock(StudentRepository.class),
                mock(ClientRepository.class),
                mock(SessionRepository.class),
                mock(VrEnrollmentEligibilityService.class),
                mock(SessionAuthorizationService.class),
                mock(PaymentRepository.class),
                mock(QboPublicSessionInvoiceService.class),
                mock(EnrollmentPricingService.class),
                mock(PrivateEnrollmentNotificationService.class));
    }
}
