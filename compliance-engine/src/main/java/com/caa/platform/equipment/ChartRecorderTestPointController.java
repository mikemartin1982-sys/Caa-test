package com.caa.platform.equipment;

import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.web.bind.annotation.*;
import java.util.Map;

@RestController
@RequestMapping("/api/v1/chart-recorder")
public class ChartRecorderTestPointController {
    private final ChartRecorderTestPointService service;

    public ChartRecorderTestPointController(ChartRecorderTestPointService service) {
        this.service = service;
    }

    @GetMapping("/marked-point")
    @PreAuthorize("hasAnyRole('STAFF', 'COMPLIANCE_ADMINISTRATOR')")
    public ResponseEntity<Map<String, Object>> accept(@RequestParam("marked_point") String payload) {
        try {
            var receipt = service.accept(payload);
            return ResponseEntity.ok(Map.of("UniqueIdentifier", receipt.transmissionId(),
                    "status", true, "message", receipt.duplicate() ? "already received" : "received"));
        } catch (IllegalArgumentException exception) {
            return failure(HttpStatus.UNPROCESSABLE_ENTITY, payload, exception.getMessage());
        } catch (IllegalStateException exception) {
            return failure(HttpStatus.CONFLICT, payload, exception.getMessage());
        }
    }

    private ResponseEntity<Map<String, Object>> failure(HttpStatus status, String payload, String message) {
        String id = "";
        try { id = new com.fasterxml.jackson.databind.ObjectMapper().readTree(payload)
                .path("UniqueIdentifier").asText(""); } catch (Exception ignored) {}
        return ResponseEntity.status(status).body(Map.of(
                "UniqueIdentifier", id, "status", false, "message", message));
    }
}
