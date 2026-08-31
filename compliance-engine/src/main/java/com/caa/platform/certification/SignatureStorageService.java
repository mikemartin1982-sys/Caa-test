package com.caa.platform.certification;

import org.springframework.beans.factory.annotation.Value;
import org.springframework.stereotype.Service;

import java.io.IOException;
import java.nio.file.Files;
import java.nio.file.Path;
import java.time.OffsetDateTime;
import java.time.format.DateTimeFormatter;
import java.util.Base64;

/**
 * Saves a student's drawn (finger/stylus) signature after a passing
 * grade (Michael, 2026-08-17) -- follows the EXACT same config/storage
 * pattern as CertificatePdfService: a file path stored on the record
 * (Certification.signatureImagePath), not a DB blob.
 */
@Service
public class SignatureStorageService {

    @Value("${app.signatures.output-dir:./signatures}")
    private String outputDir;

    /**
     * @param dataUrl the canvas's data URL, e.g.
     *                "data:image/png;base64,iVBORw0KG..." -- the
     *                "data:image/png;base64," prefix is stripped before
     *                decoding.
     */
    public String save(Long certificationId, String dataUrl) throws IOException {
        String base64 = dataUrl.contains(",") ? dataUrl.substring(dataUrl.indexOf(',') + 1) : dataUrl;
        byte[] imageBytes = Base64.getDecoder().decode(base64);

        Path dir = Path.of(outputDir);
        Files.createDirectories(dir);

        String timestamp = OffsetDateTime.now().format(DateTimeFormatter.ofPattern("yyyyMMdd-HHmmss"));
        String filename = "signature-" + certificationId + "-" + timestamp + ".png";
        Path filePath = dir.resolve(filename);
        Files.write(filePath, imageBytes);

        return filePath.toString();
    }
}
