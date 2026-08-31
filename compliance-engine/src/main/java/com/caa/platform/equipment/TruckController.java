package com.caa.platform.equipment;

import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

import java.util.List;

/**
 * Section 4 TEAM: Trucks, each with an optional default/typical
 * pairedTrailer (see Truck's Javadoc).
 */
@RestController
@RequestMapping("/api/v1/trucks")
public class TruckController {

    private final TruckRepository truckRepository;
    private final TrailerRepository trailerRepository;

    public TruckController(TruckRepository truckRepository, TrailerRepository trailerRepository) {
        this.truckRepository = truckRepository;
        this.trailerRepository = trailerRepository;
    }

    @GetMapping
    public ResponseEntity<List<Truck>> list() {
        return ResponseEntity.ok(truckRepository.findAll());
    }

    public record CreateTruckRequest(String identifier, Long pairedTrailerId) {}

    @PostMapping
    public ResponseEntity<?> create(@RequestBody CreateTruckRequest req) {
        Truck truck = new Truck();
        truck.setIdentifier(req.identifier());
        if (req.pairedTrailerId() != null) {
            Trailer trailer = trailerRepository.findById(req.pairedTrailerId())
                    .orElseThrow(() -> new IllegalArgumentException("Trailer not found: " + req.pairedTrailerId()));
            truck.setPairedTrailer(trailer);
        }
        return ResponseEntity.status(HttpStatus.CREATED).body(truckRepository.save(truck));
    }
}
