package com.caa.platform.equipment;

import com.caa.platform.session.Session;
import com.caa.platform.session.SessionRepository;
import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.io.IOException;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.StandardCopyOption;
import java.security.MessageDigest;
import java.time.OffsetDateTime;
import java.util.HexFormat;

/** Stores an exact, validated v1002 document and safely acknowledges retries. */
@Service
public class ChartRecorderDocumentService {
    static final int MAX_DOCUMENT_BYTES = 25 * 1024 * 1024;

    private final ObjectMapper mapper;
    private final SessionRepository sessions;
    private final ChartRecorderExportRepository exports;

    @Value("${app.chart-recorder-documents.output-dir:./chart-recorder-documents}")
    private String outputDir;

    public ChartRecorderDocumentService(ObjectMapper mapper, SessionRepository sessions,
            ChartRecorderExportRepository exports) {
        this.mapper = mapper;
        this.sessions = sessions;
        this.exports = exports;
    }

    public record Receipt(String transmissionId, String documentId, String sha256,
                          int measurementCount, boolean interrupted, boolean duplicate) {}

    @Transactional
    public Receipt accept(Long sessionId, String transmissionId, boolean interrupted,
            String originalFilename, byte[] payload) throws IOException {
        requireIdentifier(transmissionId, "Transmission ID");
        if (payload == null || payload.length == 0) {
            throw new IllegalArgumentException("A Chart Recorder document is required.");
        }
        if (payload.length > MAX_DOCUMENT_BYTES) {
            throw new IllegalArgumentException("Chart Recorder document exceeds the 25 MB limit.");
        }

        String hash = sha256(payload);
        var priorTransmission = exports.findByTransmissionId(transmissionId);
        if (priorTransmission.isPresent()) {
            ChartRecorderExport prior = priorTransmission.get();
            if (!prior.getSession().getId().equals(sessionId)
                    || !hash.equals(prior.getPayloadSha256())) {
                throw new IllegalStateException("Transmission ID was already used for a different document.");
            }
            return receipt(prior, true);
        }

        JsonNode root;
        try {
            root = mapper.readTree(payload);
        } catch (Exception exception) {
            throw new IllegalArgumentException("Document is not valid JSON.");
        }
        JsonNode fileHeader = requiredObject(root, "FileHeader");
        if (fileHeader.path("FileVersion").asInt(-1) != 1002) {
            throw new IllegalArgumentException("Only Chart Recorder v1002 documents are accepted.");
        }
        JsonNode sessionNode = requiredObject(root, "Session");
        JsonNode sessionHeader = requiredObject(sessionNode, "SessionHeader");
        String documentId = sessionHeader.path("Id").asText("").trim();
        requireIdentifier(documentId, "Document ID");
        int measurementCount = measurementCount(sessionNode);

        Session session = sessions.findById(sessionId)
                .orElseThrow(() -> new IllegalArgumentException("Smoke School session not found: " + sessionId));
        var priorPayload = exports.findBySessionIdAndPayloadSha256(sessionId, hash);
        if (priorPayload.isPresent()) {
            return receipt(priorPayload.get(), true);
        }

        Path directory = Path.of(outputDir).toAbsolutePath().normalize();
        Files.createDirectories(directory);
        String safeDocumentId = documentId.replaceAll("[^A-Za-z0-9._-]", "_");
        String filename = "session-" + sessionId + "-" + safeDocumentId + "-"
                + hash.substring(0, 12) + ".json";
        Path destination = directory.resolve(filename).normalize();
        if (!destination.getParent().equals(directory)) {
            throw new IllegalArgumentException("Document ID cannot be used as a filename.");
        }
        Path temporary = Files.createTempFile(directory, "chart-recorder-", ".pending");
        try {
            Files.write(temporary, payload);
            try {
                Files.move(temporary, destination, StandardCopyOption.ATOMIC_MOVE);
            } catch (java.nio.file.AtomicMoveNotSupportedException exception) {
                Files.move(temporary, destination);
            }
        } finally {
            Files.deleteIfExists(temporary);
        }

        ChartRecorderExport export = new ChartRecorderExport();
        export.setSession(session);
        export.setGeneratedAt(OffsetDateTime.now());
        export.setZipFileReference(destination.toString());
        export.setTransmissionId(transmissionId);
        export.setDocumentId(documentId);
        export.setPayloadSha256(hash);
        export.setMeasurementCount(measurementCount);
        export.setInterrupted(interrupted);
        export.setOriginalFilename(cleanFilename(originalFilename, documentId + ".json"));
        export.setIssueDelayNotes(interrupted ? "Interrupted capture" : "No Issues");
        return receipt(exports.save(export), false);
    }

    private static JsonNode requiredObject(JsonNode parent, String name) {
        JsonNode child = parent == null ? null : parent.get(name);
        if (child == null || !child.isObject()) {
            throw new IllegalArgumentException("Document is missing object: " + name);
        }
        return child;
    }

    private static int measurementCount(JsonNode sessionNode) {
        int total = 0;
        JsonNode runs = sessionNode.path("DataRuns");
        if (!runs.isArray()) {
            throw new IllegalArgumentException("Document is missing DataRuns.");
        }
        for (JsonNode run : runs) {
            JsonNode dataSets = run.path("DataSets");
            if (!dataSets.isArray()) continue;
            for (JsonNode dataSet : dataSets) {
                JsonNode measurements = dataSet.path("OpacityMeasurements");
                if (measurements.isArray()) total += measurements.size();
            }
        }
        return total;
    }

    private static void requireIdentifier(String value, String label) {
        if (value == null || value.isBlank() || value.length() > 100
                || !value.matches("[A-Za-z0-9._:-]+")) {
            throw new IllegalArgumentException(label + " is missing or invalid.");
        }
    }

    private static String cleanFilename(String value, String fallback) {
        if (value == null || value.isBlank()) return fallback;
        String name = Path.of(value).getFileName().toString();
        return name.length() <= 255 ? name : fallback;
    }

    private static String sha256(byte[] payload) {
        try {
            return HexFormat.of().formatHex(MessageDigest.getInstance("SHA-256").digest(payload));
        } catch (Exception exception) {
            throw new IllegalStateException("SHA-256 is unavailable.", exception);
        }
    }

    private static Receipt receipt(ChartRecorderExport export, boolean duplicate) {
        return new Receipt(export.getTransmissionId(), export.getDocumentId(),
                export.getPayloadSha256(), export.getMeasurementCount(),
                Boolean.TRUE.equals(export.getInterrupted()), duplicate);
    }
}
