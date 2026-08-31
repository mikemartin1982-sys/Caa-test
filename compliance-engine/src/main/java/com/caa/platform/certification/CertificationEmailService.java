package com.caa.platform.certification;

import jakarta.mail.MessagingException;
import jakarta.mail.internet.MimeMessage;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.mail.javamail.JavaMailSender;
import org.springframework.mail.javamail.MimeMessageHelper;
import org.springframework.stereotype.Service;

import java.io.File;
import java.time.format.DateTimeFormatter;

/**
 * Section 4g: "Per-student certification document: AUTOMATIC, the
 * instant an Enrollment's rosterStatus flips to CERTIFIED. No staff
 * action, no waiting on the rest of the roster." -- sent to the
 * student's own email address (Student.email), not the billing client's.
 *
 * Called from CertificationDeterminationService right after
 * CertificatePdfService generates the PDF. Same failure-tolerance
 * pattern as PDF generation: a send failure logs and moves on rather
 * than failing the certification itself -- the student IS certified
 * either way, this is just the notification.
 */
@Service
public class CertificationEmailService {

    private static final Logger log = LoggerFactory.getLogger(CertificationEmailService.class);

    private final JavaMailSender mailSender;

    @Value("${app.mail.from:no-reply@compliance-assurance.com}")
    private String fromAddress;

    public CertificationEmailService(JavaMailSender mailSender) {
        this.mailSender = mailSender;
    }

    public void sendCertificationEmail(Certification certification) throws MessagingException {
        var student = certification.getEnrollment().getStudent();
        var session = certification.getEnrollment().getSession();

        MimeMessage message = mailSender.createMimeMessage();
        MimeMessageHelper helper = new MimeMessageHelper(message, true, "UTF-8");

        helper.setFrom(fromAddress);
        helper.setTo(student.getEmail());
        helper.setSubject("Your EPA Method 9 Field Certification -- Compliance Assurance Associates, Inc.");

        String issueDateText = certification.getIssueDate() != null
                ? certification.getIssueDate().format(DateTimeFormatter.ofPattern("MMMM d, yyyy"))
                : "today";

        String body = "Hi " + student.getName() + ",\n\n"
                + "Congratulations -- you've successfully passed your EPA Method 9 visual opacity "
                + "field certification" + (session.getLocationName() != null ? " at " + session.getLocationName() : "")
                + " on " + issueDateText + ".\n\n"
                + "Your certificate is attached to this email for your records.\n\n"
                + "Compliance Assurance Associates, Inc.\n"
                + "682 Orvil Smith Rd. Harvest, AL 35749";

        helper.setText(body, false);

        if (certification.getPdfCertificateLink() != null) {
            File pdfFile = new File(certification.getPdfCertificateLink());
            if (pdfFile.exists()) {
                helper.addAttachment("certificate-" + certification.getId() + ".pdf", pdfFile);
            } else {
                log.warn("Certification {} has a pdfCertificateLink but the file doesn't exist at {} -- sending without attachment.",
                        certification.getId(), certification.getPdfCertificateLink());
            }
        }

        mailSender.send(message);
        log.info("Certification email sent for Certification {} to {}", certification.getId(), student.getEmail());
    }
}
