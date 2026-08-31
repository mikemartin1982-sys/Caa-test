package com.caa.platform.certification;

import com.caa.platform.enrollment.Enrollment;
import com.caa.platform.enrollment.EnrollmentRepository;
import com.caa.platform.enrollment.RosterStatus;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.time.LocalDate;

/**
 * The piece that was missing between "a CertificationRun was scored" and
 * "the student is CERTIFIED" (Section 4g). Called after every
 * CertificationRun is created and scored (see
 * CertificationRunController.create()).
 *
 * Two paths to an overall pass:
 *  - Standard 50-point run: whitePass AND blackPass on the SAME run.
 *  - White-only split retake (Section 3b): this run's whitePass is true,
 *    AND its priorRun's blackPass was already true (guaranteed by
 *    SplitRunEligibilityService -- Black is never split, so priorRun's
 *    Black result is what's being "banked" against this retake's White).
 *
 * A run that doesn't produce an overall pass doesn't create a
 * Certification at all -- the student simply isn't certified yet, and
 * may need another run (Section 3b's uncapped-retries rule).
 */
@Service
public class CertificationDeterminationService {

    private static final Logger log = LoggerFactory.getLogger(CertificationDeterminationService.class);

    private final CertificationRepository certificationRepository;
    private final EnrollmentRepository enrollmentRepository;
    private final CertificatePdfService pdfService;
    private final CertificationEmailService emailService;

    public CertificationDeterminationService(CertificationRepository certificationRepository,
                                               EnrollmentRepository enrollmentRepository,
                                               CertificatePdfService pdfService,
                                               CertificationEmailService emailService) {
        this.certificationRepository = certificationRepository;
        this.enrollmentRepository = enrollmentRepository;
        this.pdfService = pdfService;
        this.emailService = emailService;
    }

    /**
     * @param enrollmentId which Enrollment this run's certification
     *                     attempt belongs to -- not stored on
     *                     CertificationRun itself (that's session-level),
     *                     so the caller must supply it.
     * @return the Certification if this run resulted in an overall pass, else null.
     */
    @Transactional
    public Certification determineAndApply(CertificationRun run, Long enrollmentId) {
        CertificationRun whiteRun = null;
        CertificationRun blackRun = null;

        if (!run.isSplitRetake()) {
            // Standard run: both colors must pass on this same run.
            if (Boolean.TRUE.equals(run.getWhitePass()) && Boolean.TRUE.equals(run.getBlackPass())) {
                whiteRun = run;
                blackRun = run;
            }
        } else {
            // White-only split retake: this run's White banked against
            // the prior run's already-passing Black (Black is never split).
            if (Boolean.TRUE.equals(run.getWhitePass())
                    && run.getPriorRun() != null
                    && Boolean.TRUE.equals(run.getPriorRun().getBlackPass())) {
                whiteRun = run;
                blackRun = run.getPriorRun();
            }
        }

        if (whiteRun == null) {
            return null; // not an overall pass -- no Certification issued
        }

        Enrollment enrollment = enrollmentRepository.findById(enrollmentId)
                .orElseThrow(() -> new IllegalArgumentException("Enrollment not found: " + enrollmentId));

        Certification certification = certificationRepository.findByEnrollmentId(enrollmentId)
                .orElseGet(Certification::new);

        certification.setEnrollment(enrollment);
        certification.setWhiteRun(whiteRun);
        certification.setBlackRun(blackRun);
        certification.setWhiteCumulativeDeviation(whiteRun.getWhiteCumulativeDeviation());
        certification.setBlackCumulativeDeviation(blackRun.getBlackCumulativeDeviation());
        certification.setAnyFailedReading(whiteRun.isWhiteFailedReading() || blackRun.isBlackFailedReading());
        certification.setPassFail(true);
        certification.setIssueDate(LocalDate.now());
        // Expiration policy (e.g. annual renewal) isn't defined anywhere
        // in the architecture doc yet -- left null rather than guessing
        // at a duration.

        Certification saved = certificationRepository.save(certification);

        // Section 4g: fires the instant an Enrollment flips to CERTIFIED.
        // A PDF generation failure should NOT stop the certification
        // itself from being recorded -- the student is still certified
        // either way; log and move on rather than failing the whole
        // request over a document-generation problem.
        try {
            String pdfPath = pdfService.generate(saved);
            saved.setPdfCertificateLink(pdfPath);
            saved = certificationRepository.save(saved);
        } catch (Exception e) {
            log.error("PDF generation failed for Certification {} -- certification itself was still recorded.",
                    saved.getId(), e);
        }

        // Section 4g: same failure-tolerance approach as PDF generation --
        // an email problem (bad SMTP config, network blip) should never
        // undo or block the certification itself.
        try {
            emailService.sendCertificationEmail(saved);
        } catch (Exception e) {
            log.error("Certification email failed to send for Certification {} -- certification itself was still recorded.",
                    saved.getId(), e);
        }

        enrollment.setRosterStatus(RosterStatus.CERTIFIED);
        enrollment.setCertifyingRun(run);
        enrollmentRepository.save(enrollment);

        return saved;
    }
}
