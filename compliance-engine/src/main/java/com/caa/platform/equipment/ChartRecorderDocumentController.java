package com.caa.platform.equipment;

import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestHeader;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import java.io.IOException;
import java.util.Map;

@RestController
@RequestMapping("/api/v1/chart-recorder")
public class ChartRecorderDocumentController {
    private final ChartRecorderDocumentService documents;

    public ChartRecorderDocumentController(ChartRecorderDocumentService documents) {
        this.documents = documents;
    }

    @PostMapping(value = "/sessions/{sessionId}/documents", consumes = "application/json",
            produces = "application/json")
    @PreAuthorize("hasAnyRole('STAFF', 'COMPLIANCE_ADMINISTRATOR')")
    public ResponseEntity<?> upload(@PathVariable Long sessionId,
            @RequestHeader("X-Chart-Recorder-Transmission-Id") String transmissionId,
            @RequestHeader(value = "X-Chart-Recorder-Interrupted", defaultValue = "false") boolean interrupted,
            @RequestHeader(value = "X-Chart-Recorder-Filename", required = false) String filename,
            @RequestBody byte[] payload) {
        try {
            return ResponseEntity.ok(documents.accept(
                    sessionId, transmissionId, interrupted, filename, payload));
        } catch (IllegalArgumentException exception) {
            return ResponseEntity.badRequest().body(Map.of("error", exception.getMessage()));
        } catch (IllegalStateException exception) {
            return ResponseEntity.status(HttpStatus.CONFLICT)
                    .body(Map.of("error", exception.getMessage()));
        } catch (IOException exception) {
            return ResponseEntity.status(HttpStatus.INTERNAL_SERVER_ERROR)
                    .body(Map.of("error", "Could not retain the Chart Recorder document."));
        }
    }
}
