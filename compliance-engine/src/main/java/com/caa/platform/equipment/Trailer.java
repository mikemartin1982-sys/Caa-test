package com.caa.platform.equipment;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;

import java.time.OffsetDateTime;

/**
 * Section 4h: each Trailer is individually identified (e.g. "Abby") and
 * carries an EPA Method 9 (1974) equipment set. Has one Primary and one
 * Secondary {@link TestingSystem}, and its own set of 3 {@link CalibrationPane}s.
 */
@Entity
@Table(name = "trailers")
@Getter
@Setter
@NoArgsConstructor
public class Trailer {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Column(nullable = false, unique = true)
    private String identifier;

    /**
     * Michael, 2026-08-29 -- staff calendar, Phase 1. Same as
     * Truck.shortCode -- see that field's own comment for the full
     * reasoning.
     */
    @Column(name = "short_code", length = 10)
    private String shortCode;

    @Column(name = "equipment_set_description")
    private String equipmentSetDescription;

    @Column(name = "created_at", nullable = false, updatable = false)
    private OffsetDateTime createdAt = OffsetDateTime.now();
}
