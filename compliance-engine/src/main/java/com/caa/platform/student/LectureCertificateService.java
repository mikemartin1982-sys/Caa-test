package com.caa.platform.student;

import com.caa.platform.staff.StaffUser;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.web.multipart.MultipartFile;

import java.io.IOException;
import java.nio.file.Files;
import java.nio.file.Path;
import java.time.LocalDate;
import java.time.OffsetDateTime;
import java.time.format.DateTimeFormatter;
import java.util.List;
import java.util.Set;

/**
 * Michael, 2026-08-30 -- Lecture Certificate Upload feature.
 *
 * File storage matches BidPdfService's own, already-proven pattern
 * exactly (confirmed by reading that file directly, not assumed from
 * memory): a @Value-configured output directory with a sensible
 * relative-path fallback, a timestamp-based filename, full filesystem
 * path stored on the record.
 *
 * Confirmed with Michael: staff-only upload means the upload action
 * itself IS the approval -- this method updates Student's own
 * lectureComplete/lectureCompletionDate/lectureCompletionSource fields
 * in the SAME call, not as a separate review step.
 */
@Service
public class LectureCertificateService {

    @Value("${app.lecture-certificates.output-dir:./lecture-certificates}")
    private String outputDir;

    private static final Set<String> ALLOWED_EXTENSIONS = Set.of("pdf", "jpg", "jpeg", "png");
    private static final long MAX_FILE_SIZE_BYTES = 10L * 1024 * 1024; // 10MB, confirmed with Michael

    private final LectureCertificateRepository repository;
    private final StudentRepository studentRepository;
    private final ProviderRepository providerRepository;

    public LectureCertificateService(LectureCertificateRepository repository, StudentRepository studentRepository,
                                      ProviderRepository providerRepository) {
        this.repository = repository;
        this.studentRepository = studentRepository;
        this.providerRepository = providerRepository;
    }

    public record UploadRequest(Long studentId, LectureCertificate.Source source, Long providerId,
                                 LocalDate completionDate, MultipartFile file) {}

    /**
     * Michael, 2026-08-30 -- @Transactional: supersede-then-insert-then-
     * sync-Student needs to be all-or-nothing, matching the same
     * atomicity reasoning already established elsewhere in this project
     * (Combine Employee's own multi-step merge) -- a failure partway
     * through must not leave a superseded-but-not-replaced certificate,
     * or a new certificate saved without Student's own fields actually
     * reflecting it.
     */
    @Transactional
    public LectureCertificate upload(UploadRequest req, StaffUser uploadedBy) throws IOException {
        Student student = studentRepository.findById(req.studentId())
                .orElseThrow(() -> new IllegalArgumentException("Student not found: " + req.studentId()));

        // Michael, 2026-08-30 -- Provider is ONLY ever valid alongside
        // THIRD_PARTY, confirmed with Michael: CAA_LECTURE never needs
        // one, since every self-paced certificate bears Joseph Spivey's
        // signature -- the answer to "who taught this" is inherently
        // "us," not a lookup. Checked both directions, not just that a
        // THIRD_PARTY has one -- a CAA_LECTURE record with a provider
        // attached would be a real, meaningful data-integrity mistake,
        // not a harmless extra field.
        Provider provider = null;
        if (req.source() == LectureCertificate.Source.THIRD_PARTY) {
            if (req.providerId() == null) {
                throw new IllegalArgumentException("A provider is required for a third-party certificate.");
            }
            provider = providerRepository.findById(req.providerId())
                    .orElseThrow(() -> new IllegalArgumentException("Provider not found: " + req.providerId()));
        } else if (req.providerId() != null) {
            throw new IllegalArgumentException("A CAA self-paced lecture certificate can't have a provider attached.");
        }

        validateFile(req.file());

        // Supersede any existing active certificate for this student --
        // never deleted, never overwritten, matching the full-history
        // pattern confirmed with Michael.
        repository.findByStudentIdAndSupersededFalse(req.studentId()).ifPresent(existing -> {
            existing.setSuperseded(true);
            repository.save(existing);
        });

        String filePath = storeFile(req.studentId(), req.file());

        LectureCertificate cert = new LectureCertificate();
        cert.setStudent(student);
        cert.setSource(req.source());
        cert.setProvider(provider);
        cert.setCompletionDate(req.completionDate());
        cert.setFilePath(filePath);
        cert.setOriginalFilename(req.file().getOriginalFilename());
        cert.setUploadedBy(uploadedBy);
        cert.setUploadedAt(OffsetDateTime.now());
        cert = repository.save(cert);

        // Michael, 2026-08-30 -- confirmed: the staff upload action
        // itself is the approval, so Student's own fields update in
        // this same call, not a separate review step.
        student.setLectureComplete(true);
        student.setLectureCompletionDate(req.completionDate());
        student.setLectureCompletionSource(req.source() == LectureCertificate.Source.CAA_LECTURE ? "caa_lecture" : "uploaded_certificate");
        studentRepository.save(student);

        // Michael, 2026-08-30 -- @Transactional alone wasn't enough:
        // Spring Boot's default Hibernate/Jackson integration
        // (Hibernate6Module) deliberately does NOT force lazy proxies
        // to load during serialization -- it just outputs null for
        // anything not already initialized, regardless of whether the
        // session is still open. Explicitly initialized here, while
        // still inside the transaction, before this ever reaches
        // Jackson.
        org.hibernate.Hibernate.initialize(cert.getStudent());
        org.hibernate.Hibernate.initialize(cert.getUploadedBy());
        if (cert.getProvider() != null) {
            org.hibernate.Hibernate.initialize(cert.getProvider());
        }

        return cert;
    }

    /** Full history, newest first -- matches Michael's own real CertificationRun screenshot ordering. */
    public List<LectureCertificate> history(Long studentId) {
        List<LectureCertificate> certs = repository.findByStudentIdOrderByUploadedAtDesc(studentId);
        // Michael, 2026-08-30 -- same reasoning as upload()'s own fix
        // above: forced here, per record, while still inside this
        // (already @Transactional-covered, via the controller) request.
        for (LectureCertificate cert : certs) {
            org.hibernate.Hibernate.initialize(cert.getStudent());
            org.hibernate.Hibernate.initialize(cert.getUploadedBy());
            if (cert.getProvider() != null) {
                org.hibernate.Hibernate.initialize(cert.getProvider());
            }
        }
        return certs;
    }

    public java.util.Optional<LectureCertificate> findById(Long certificateId) {
        return repository.findById(certificateId);
    }

    private void validateFile(MultipartFile file) {
        if (file == null || file.isEmpty()) {
            throw new IllegalArgumentException("A certificate file is required.");
        }
        if (file.getSize() > MAX_FILE_SIZE_BYTES) {
            throw new IllegalArgumentException("File exceeds the 10MB limit.");
        }
        String ext = extensionOf(file.getOriginalFilename());
        if (ext == null || !ALLOWED_EXTENSIONS.contains(ext.toLowerCase())) {
            throw new IllegalArgumentException("Only PDF, JPG, and PNG files are accepted.");
        }
    }

    private String storeFile(Long studentId, MultipartFile file) throws IOException {
        Path dir = Path.of(outputDir);
        Files.createDirectories(dir);

        String ext = extensionOf(file.getOriginalFilename());
        String timestamp = DateTimeFormatter.ofPattern("yyyyMMdd-HHmmss").format(java.time.LocalDateTime.now());
        String filename = "lecture-cert-" + studentId + "-" + timestamp + "." + ext;
        Path filePath = dir.resolve(filename);

        file.transferTo(filePath);
        return filePath.toString();
    }

    private String extensionOf(String originalFilename) {
        if (originalFilename == null || !originalFilename.contains(".")) {
            return null;
        }
        return originalFilename.substring(originalFilename.lastIndexOf('.') + 1);
    }
}
