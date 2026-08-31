package com.caa.platform.certification;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;
import org.hibernate.annotations.JdbcTypeCode;
import org.hibernate.type.SqlTypes;

/**
 * Section 3/4g: one row per point. 50 rows for an initial run (1-25 White,
 * 26-50 Black), 25 rows for a split-run retake (its own 1-25, White only).
 * trueOpacityValue and studentEstimatedOpacity both render to the nearest
 * 5% (Digital Testing / VEO form-reader convention, Section 4g) -- always
 * multiples of 5, which is also why the live color-coded UI feedback scale
 * (0/5/10/15/20+) maps exactly onto every possible deviation value.
 */
@Entity
@Table(name = "observations", uniqueConstraints = @UniqueConstraint(columnNames = {"certification_run_id", "point_number"}))
@Getter
@Setter
@NoArgsConstructor
public class Observation {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "certification_run_id", nullable = false)
    private CertificationRun certificationRun;

    /** VR: plume filename; in-person: Stacktest.net reference. */
    @Column(name = "plume_reference")
    private String plumeReference;

    @Enumerated(EnumType.STRING)
    @JdbcTypeCode(SqlTypes.NAMED_ENUM)
    @Column(nullable = false)
    private PlumeColor color;

    /** 1-25 (White) or 26-50 (Black) for a 50pt run; 1-25 for a 25pt retake. */
    @Column(name = "point_number", nullable = false)
    private Short pointNumber;

    @Column(name = "true_opacity_value", nullable = false)
    private Short trueOpacityValue;

    @Column(name = "student_estimated_opacity", nullable = false)
    private Short studentEstimatedOpacity;

    @Column(nullable = false)
    private Short deviation;

    /** deviation >= 20 -- the only per-reading failure condition (Section 3b). */
    @Column(name = "failed_reading", nullable = false)
    private boolean failedReading = false;
}
