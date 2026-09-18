package com.caa.platform.equipment;

import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

import java.math.BigDecimal;
import java.time.LocalDate;
import java.util.List;

/**
 * Section 4h: Trailers and their 3 CalibrationPanes each.
 */
@RestController
@RequestMapping("/api/v1/trailers")
public class TrailerController {

    private final TrailerRepository trailerRepository;
    private final CalibrationPaneRepository calibrationPaneRepository;

    public TrailerController(TrailerRepository trailerRepository, CalibrationPaneRepository calibrationPaneRepository) {
        this.trailerRepository = trailerRepository;
        this.calibrationPaneRepository = calibrationPaneRepository;
    }

    @GetMapping
    public ResponseEntity<List<Trailer>> list() {
        return ResponseEntity.ok(trailerRepository.findAll());
    }

    public record CreateTrailerRequest(String identifier, String equipmentSetDescription) {}

    @PostMapping
    public ResponseEntity<Trailer> create(@RequestBody CreateTrailerRequest req) {
        Trailer trailer = new Trailer();
        trailer.setIdentifier(req.identifier());
        trailer.setEquipmentSetDescription(req.equipmentSetDescription());
        return ResponseEntity.status(HttpStatus.CREATED).body(trailerRepository.save(trailer));
    }

    @GetMapping("/{trailerId}/panes")
    public ResponseEntity<List<CalibrationPane>> listPanes(@PathVariable Long trailerId) {
        return ResponseEntity.ok(calibrationPaneRepository.findByTrailerId(trailerId));
    }

    public record CreatePaneRequest(String paneIdentifier, BigDecimal certifiedOpacityValue, LocalDate lastNistVerificationDate) {}

    /** Section 4h: each Trailer has its own set of 3 panes, NIST-verified annually. */
    @PostMapping("/{trailerId}/panes")
    public ResponseEntity<CalibrationPane> createPane(@PathVariable Long trailerId, @RequestBody CreatePaneRequest req) {
        Trailer trailer = trailerRepository.findById(trailerId)
                .orElseThrow(() -> new IllegalArgumentException("Trailer not found: " + trailerId));

        CalibrationPane pane = new CalibrationPane();
        pane.setTrailer(trailer);
        pane.setPaneIdentifier(req.paneIdentifier());
        pane.setCertifiedOpacityValue(req.certifiedOpacityValue());
        pane.setLastNistVerificationDate(req.lastNistVerificationDate());
        return ResponseEntity.status(HttpStatus.CREATED).body(calibrationPaneRepository.save(pane));
    }

    public record UpdatePaneRequest(String paneIdentifier, BigDecimal certifiedOpacityValue, LocalDate lastNistVerificationDate) {}

    /**
     * Michael, 2026-08-31 -- confirmed with Michael: panes get
     * re-certified annually, and being able to directly edit a pane's
     * details is the priority here, not building an internal history
     * of past values -- the real, authoritative paper trail already
     * exists outside this system (physical NIST documentation), so a
     * simple, direct update is correct, not a supersede-and-keep-
     * history pattern like Lecture Certificate Upload.
     */
    @PatchMapping("/panes/{paneId}")
    public ResponseEntity<?> updatePane(@PathVariable Long paneId, @RequestBody UpdatePaneRequest req) {
        CalibrationPane pane = calibrationPaneRepository.findById(paneId)
                .orElseThrow(() -> new IllegalArgumentException("Calibration pane not found: " + paneId));

        if (req.paneIdentifier() != null) {
            if (calibrationPaneRepository.existsByPaneIdentifierAndIdNot(req.paneIdentifier(), paneId)) {
                return ResponseEntity.status(HttpStatus.CONFLICT)
                        .body(java.util.Map.of("error", "A calibration pane with this identifier already exists."));
            }
            pane.setPaneIdentifier(req.paneIdentifier());
        }
        if (req.certifiedOpacityValue() != null) pane.setCertifiedOpacityValue(req.certifiedOpacityValue());
        if (req.lastNistVerificationDate() != null) pane.setLastNistVerificationDate(req.lastNistVerificationDate());
        return ResponseEntity.ok(calibrationPaneRepository.save(pane));
    }
}
