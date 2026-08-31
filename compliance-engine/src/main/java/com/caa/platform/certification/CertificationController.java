package com.caa.platform.certification;

import org.springframework.core.io.FileSystemResource;
import org.springframework.core.io.Resource;
import org.springframework.http.HttpHeaders;
import org.springframework.http.MediaType;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import java.io.File;

/**
 * Section 4g/4e: the "audit-ready certificate download" the Client
 * Portal offers. Certification.pdfCertificateLink is set by
 * CertificationDeterminationService the moment a student passes.
 */
@RestController
@RequestMapping("/api/v1/certifications")
public class CertificationController {

    private final CertificationRepository certificationRepository;

    public CertificationController(CertificationRepository certificationRepository) {
        this.certificationRepository = certificationRepository;
    }

    @GetMapping("/{certificationId}")
    public ResponseEntity<Certification> get(@PathVariable Long certificationId) {
        return certificationRepository.findById(certificationId)
                .map(ResponseEntity::ok)
                .orElseGet(() -> ResponseEntity.notFound().build());
    }

    @GetMapping("/{certificationId}/pdf")
    public ResponseEntity<Resource> downloadPdf(@PathVariable Long certificationId) {
        Certification certification = certificationRepository.findById(certificationId)
                .orElseThrow(() -> new IllegalArgumentException("Certification not found: " + certificationId));

        if (certification.getPdfCertificateLink() == null) {
            return ResponseEntity.notFound().build();
        }

        File file = new File(certification.getPdfCertificateLink());
        if (!file.exists()) {
            return ResponseEntity.notFound().build();
        }

        Resource resource = new FileSystemResource(file);
        return ResponseEntity.ok()
                .contentType(MediaType.APPLICATION_PDF)
                .header(HttpHeaders.CONTENT_DISPOSITION, "attachment; filename=\"" + file.getName() + "\"")
                .body(resource);
    }
}
