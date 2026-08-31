package com.caa.platform.student;

import com.caa.platform.staff.StaffUser;
import com.caa.platform.staff.StaffUserRepository;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.multipart.MultipartFile;

import java.io.IOException;
import java.time.LocalDate;
import java.util.List;
import java.util.Map;

/**
 * Michael, 2026-08-30 -- Lecture Certificate Upload feature.
 *
 * Java's own API has no session/auth layer -- Laravel handles that,
 * then calls this server-to-server -- so every endpoint here that
 * needs to know WHO is acting takes an explicit staffUserId, resolved
 * to a real StaffUser here, matching the same established pattern
 * BidPdfService.generate() already uses (a StaffUser sender passed in
 * by the caller, not determined internally).
 */
@RestController
public class LectureCertificateController {

    private final LectureCertificateService certificateService;
    private final StaffUserRepository staffUserRepository;
    private final ProviderRepository providerRepository;

    public LectureCertificateController(LectureCertificateService certificateService,
                                         StaffUserRepository staffUserRepository,
                                         ProviderRepository providerRepository) {
        this.certificateService = certificateService;
        this.staffUserRepository = staffUserRepository;
        this.providerRepository = providerRepository;
    }

    private StaffUser resolveStaff(Long staffUserId) {
        return staffUserRepository.findById(staffUserId)
                .orElseThrow(() -> new IllegalArgumentException("Staff user not found: " + staffUserId));
    }

    /**
     * Michael, 2026-08-30 -- staff-only upload, confirmed with Michael:
     * deliberately sidesteps needing any "is this a legitimate
     * certificate" validation gate, since a client never touches this
     * at all. Multipart, not JSON -- the actual file plus its metadata
     * in one request.
     */
    @PostMapping("/api/v1/students/{studentId}/lecture-certificate")
    @org.springframework.transaction.annotation.Transactional
    public ResponseEntity<?> upload(@PathVariable Long studentId,
                                     @RequestParam LectureCertificate.Source source,
                                     @RequestParam(required = false) Long providerId,
                                     @RequestParam LocalDate completionDate,
                                     @RequestParam MultipartFile file,
                                     @RequestParam Long staffUserId) {
        try {
            StaffUser staff = resolveStaff(staffUserId);
            LectureCertificateService.UploadRequest req = new LectureCertificateService.UploadRequest(
                    studentId, source, providerId, completionDate, file);
            LectureCertificate cert = certificateService.upload(req, staff);
            return ResponseEntity.status(HttpStatus.CREATED).body(cert);
        } catch (IllegalArgumentException e) {
            return ResponseEntity.status(HttpStatus.CONFLICT).body(Map.of("error", e.getMessage()));
        } catch (IOException e) {
            return ResponseEntity.status(HttpStatus.INTERNAL_SERVER_ERROR)
                    .body(Map.of("error", "The file could not be saved: " + e.getMessage()));
        }
    }

    /** Full history, newest first -- supersede-not-delete means every past certificate stays visible here. */
    @GetMapping("/api/v1/students/{studentId}/lecture-certificates")
    @org.springframework.transaction.annotation.Transactional(readOnly = true)
    public ResponseEntity<List<LectureCertificate>> history(@PathVariable Long studentId) {
        return ResponseEntity.ok(certificateService.history(studentId));
    }

    /**
     * Michael, 2026-08-30 -- the actual file bytes, not just the
     * record -- filePath is a real filesystem path, not something a
     * browser can reach directly. Confirmed with Michael: visible to
     * clients, students, and staff, so no role check here beyond
     * whatever Laravel's own auth middleware already covers before
     * this is ever called.
     */
    @GetMapping("/api/v1/lecture-certificates/{certificateId}/download")
    public ResponseEntity<?> download(@PathVariable Long certificateId) {
        return certificateService.findById(certificateId)
                .map(cert -> {
                    java.io.File file = new java.io.File(cert.getFilePath());
                    if (!file.exists()) {
                        return ResponseEntity.status(HttpStatus.NOT_FOUND)
                                .body(Map.of("error", "This certificate's file is missing from storage."));
                    }
                    org.springframework.core.io.Resource resource = new org.springframework.core.io.FileSystemResource(file);
                    String filename = cert.getOriginalFilename() != null ? cert.getOriginalFilename() : file.getName();
                    return ResponseEntity.ok()
                            .header("Content-Disposition", "attachment; filename=\"" + filename + "\"")
                            .body(resource);
                })
                .orElseGet(() -> ResponseEntity.notFound().build());
    }

    @GetMapping("/api/v1/providers")
    public ResponseEntity<List<Provider>> listProviders(@RequestParam(required = false, defaultValue = "true") boolean activeOnly) {
        return ResponseEntity.ok(activeOnly ? providerRepository.findByActiveTrue() : providerRepository.findAll());
    }

    public record CreateProviderRequest(String name, Long staffUserId) {}

    /**
     * Michael, 2026-08-30 -- confirmed with Michael: additions
     * restricted to Compliance Administrators (or Derek/Joe, who can
     * decide their own role designation -- both already covered here
     * once they're actually flagged COMPLIANCE_ADMINISTRATOR, since
     * that's a real, existing role on StaffUser).
     */
    @PostMapping("/api/v1/providers")
    public ResponseEntity<?> createProvider(@RequestBody CreateProviderRequest req) {
        StaffUser staff = resolveStaff(req.staffUserId());
        if (!staff.isComplianceAdministrator()) {
            return ResponseEntity.status(HttpStatus.FORBIDDEN)
                    .body(Map.of("error", "Only Compliance Administrators can add a new provider."));
        }
        if (req.name() == null || req.name().isBlank()) {
            return ResponseEntity.status(HttpStatus.CONFLICT).body(Map.of("error", "A provider name is required."));
        }

        Provider provider = new Provider();
        provider.setName(req.name());
        provider.setActive(true);
        return ResponseEntity.status(HttpStatus.CREATED).body(providerRepository.save(provider));
    }
}
