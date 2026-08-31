package com.caa.platform.equipment;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;

import java.math.BigDecimal;
import java.time.LocalDate;
import java.time.OffsetDateTime;

/**
 * Section 4h: each Trailer has its own set of 3 glass panes with known
 * opacity values, verified/calibrated once a year by NIST. Unique
 * identifier ties to the tablet.
 */
@Entity
@Table(name = "calibration_panes")
@Getter
@Setter
@NoArgsConstructor
public class CalibrationPane {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "trailer_id", nullable = false)
    private Trailer trailer;

    @Column(name = "pane_identifier", nullable = false, unique = true)
    private String paneIdentifier;

    @Column(name = "certified_opacity_value", nullable = false, precision = 5, scale = 2)
    private BigDecimal certifiedOpacityValue;

    @Column(name = "last_nist_verification_date", nullable = false)
    private LocalDate lastNistVerificationDate;

    @Column(name = "created_at", nullable = false, updatable = false)
    private OffsetDateTime createdAt = OffsetDateTime.now();
}
