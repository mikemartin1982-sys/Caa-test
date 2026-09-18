package com.caa.platform.certification;

import com.caa.platform.student.LectureCertificate;
import com.caa.platform.student.LectureCertificateRepository;
import com.caa.platform.student.Student;
import com.caa.platform.student.StudentRepository;
import org.springframework.core.io.FileSystemResource;
import org.springframework.core.io.Resource;
import org.springframework.http.HttpHeaders;
import org.springframework.http.HttpStatus;
import org.springframework.http.MediaType;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.bind.annotation.RestController;

import java.io.File;
import java.util.Map;
import java.util.Optional;

/**
 * Michael, 2026-09-04 -- Public Certificate Lookup, matching the real,
 * existing DIBs feature (compliance-assurance.com/certs.php and
 * certs-email-id.php). Confirmed with Michael: genuinely public, no
 * login at all -- if a student can't reach their Client Contact, they
 * come here directly with just their Student # and last name (or, if
 * they don't know their Student #, their email and last name first).
 *
 * Deliberately a NEW, SEPARATE controller under /api/v1/public/certs,
 * not added to the existing CertificationController -- that one is the
 * authenticated Client Portal's own download path (its own comment:
 * "the audit-ready certificate download the Client Portal offers").
 * Reusing it directly for a genuinely public flow would mean either
 * weakening its existing auth rules or it simply not working for an
 * unauthenticated caller at all -- kept fully separate instead, same
 * "public and authenticated paths never share a route" principle
 * already used for Password Reset. Confirmed with Michael: this whole
 * controller's routes live outside /admin/ and /account/ entirely, not
 * merely whitelisted within them.
 *
 * Confirmed with Michael: no defensive rate-limiting added here --
 * matching the real, current DIBs site's own behavior, and a
 * deliberate choice given a real student should already know their own
 * email/last name (or Student #/last name) at minimum.
 */
@RestController
@RequestMapping("/api/v1/public/certs")
public class PublicCertificateLookupController {

    private final StudentRepository studentRepository;
    private final CertificationRepository certificationRepository;
    private final LectureCertificateRepository lectureCertificateRepository;

    public PublicCertificateLookupController(StudentRepository studentRepository,
                                              CertificationRepository certificationRepository,
                                              LectureCertificateRepository lectureCertificateRepository) {
        this.studentRepository = studentRepository;
        this.certificationRepository = certificationRepository;
        this.lectureCertificateRepository = lectureCertificateRepository;
    }

    public record StudentNumberLookupResult(String studentNumber) {}

    /**
     * Matches certs-email-id.php -- a student who doesn't know/recall
     * their own Student # looks it up by email + last name first.
     */
    @GetMapping("/lookup-student-number")
    public ResponseEntity<?> lookupStudentNumber(@RequestParam String email, @RequestParam String lastName) {
        Optional<Student> match = studentRepository.findAll().stream()
                .filter(s -> email.equalsIgnoreCase(s.getEmail()))
                .filter(s -> matchesLastName(s.getName(), lastName))
                .findFirst();

        if (match.isEmpty()) {
            return ResponseEntity.status(HttpStatus.NOT_FOUND)
                    .body(Map.of("error", "No record found matching that email and last name."));
        }
        return ResponseEntity.ok(new StudentNumberLookupResult(match.get().getStudentNumber()));
    }

    public record CertRecord(String issueDate, String expirationDate, Long downloadId) {}
    public record CertLookupResult(String studentName, CertRecord fieldCertification, CertRecord lectureCertificate) {}

    /**
     * Matches certs.php -- the main lookup, Student # + last name.
     * Confirmed with Michael: shows the student's most recent, real
     * Field Certification, and their most recent Lecture Certificate
     * if they have one (not every student does) -- both genuinely
     * downloadable from here.
     */
    @GetMapping
    public ResponseEntity<?> lookup(@RequestParam String studentNumber, @RequestParam String lastName) {
        Optional<Student> match = studentRepository.findByStudentNumber(studentNumber)
                .filter(s -> matchesLastName(s.getName(), lastName));

        if (match.isEmpty()) {
            return ResponseEntity.status(HttpStatus.NOT_FOUND)
                    .body(Map.of("error", "No record found matching that Student # and last name."));
        }
        Student student = match.get();

        CertRecord fieldCert = certificationRepository.findTopByEnrollment_Student_IdOrderByIssueDateDesc(student.getId())
                .map(c -> new CertRecord(
                        c.getIssueDate() != null ? c.getIssueDate().toString() : null,
                        c.getExpirationDate() != null ? c.getExpirationDate().toString() : null,
                        c.getId()))
                .orElse(null);

        CertRecord lectureCert = lectureCertificateRepository.findByStudentIdAndSupersededFalse(student.getId())
                .map(lc -> new CertRecord(
                        lc.getUploadedAt() != null ? lc.getUploadedAt().toString() : null,
                        null,
                        lc.getId()))
                .orElse(null);

        return ResponseEntity.ok(new CertLookupResult(student.getName(), fieldCert, lectureCert));
    }

    /**
     * Genuinely separate, deliberately public download route -- reuses
     * the exact same, real file-serving approach as
     * CertificationController.downloadPdf() (FileSystemResource), just
     * as its own, public endpoint rather than sharing that
     * authenticated one directly.
     */
    @GetMapping("/field-certification/{certificationId}/pdf")
    public ResponseEntity<Resource> downloadFieldCertification(@PathVariable Long certificationId) {
        Certification certification = certificationRepository.findById(certificationId)
                .orElseThrow(() -> new IllegalArgumentException("Certification not found: " + certificationId));
        return serveFile(certification.getPdfCertificateLink());
    }

    @GetMapping("/lecture-certificate/{lectureCertificateId}/pdf")
    public ResponseEntity<Resource> downloadLectureCertificate(@PathVariable Long lectureCertificateId) {
        LectureCertificate lectureCertificate = lectureCertificateRepository.findById(lectureCertificateId)
                .orElseThrow(() -> new IllegalArgumentException("Lecture certificate not found: " + lectureCertificateId));
        return serveFile(lectureCertificate.getFilePath());
    }

    private ResponseEntity<Resource> serveFile(String path) {
        if (path == null) {
            return ResponseEntity.notFound().build();
        }
        File file = new File(path);
        if (!file.exists()) {
            return ResponseEntity.notFound().build();
        }
        Resource resource = new FileSystemResource(file);
        return ResponseEntity.ok()
                .contentType(MediaType.APPLICATION_PDF)
                .header(HttpHeaders.CONTENT_DISPOSITION, "attachment; filename=\"" + file.getName() + "\"")
                .body(resource);
    }

    /**
     * Michael, 2026-09-04 -- Student.name is a single, full-name field
     * (no separate first/last columns) -- matches on the last real,
     * space-separated token, case-insensitive, rather than requiring
     * an exact substring match against the whole name.
     */
    private boolean matchesLastName(String fullName, String lastName) {
        if (fullName == null || lastName == null) {
            return false;
        }
        String[] parts = fullName.trim().split("\\s+");
        String actualLastName = parts[parts.length - 1];
        return actualLastName.equalsIgnoreCase(lastName.trim());
    }
}
