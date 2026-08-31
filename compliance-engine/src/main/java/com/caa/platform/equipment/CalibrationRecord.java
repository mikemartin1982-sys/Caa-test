package com.caa.platform.equipment;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;
import org.hibernate.annotations.JdbcTypeCode;
import org.hibernate.type.SqlTypes;

import java.time.OffsetDateTime;

/**
 * The "5-Filter" (Section 4h). Required every 6 months, or immediately on
 * a Significant Repair/Replace event. Tracks per-parameter results for the
 * six EPA Method 9 Section 3.1.2 criteria -- any one parameter failing
 * invalidates the whole 5-Filter even if others pass. Sourced from
 * Stacktest.net (this platform reads it, does not originate it).
 */
@Entity
@Table(name = "calibration_records")
@Getter
@Setter
@NoArgsConstructor
public class CalibrationRecord {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "testing_system_id", nullable = false)
    private TestingSystem testingSystem;

    @Column(name = "performed_at", nullable = false)
    private OffsetDateTime performedAt;

    @Enumerated(EnumType.STRING)
    @JdbcTypeCode(SqlTypes.NAMED_ENUM)
    @Column(name = "trigger_reason", nullable = false)
    private CalibrationTrigger triggerReason;

    // EPA Method 9 Section 3.1.2 parameters -- each independently pass/fail
    @Column(name = "light_source_voltage_pass")
    private Boolean lightSourceVoltagePass;          // incandescent lamp +/-5% of nominal rated voltage

    @Column(name = "photocell_spectral_response_pass")
    private Boolean photocellSpectralResponsePass;    // photopic response, +/-3% opacity

    @Column(name = "angle_of_view_pass")
    private Boolean angleOfViewPass;                  // 15 deg maximum total angle

    @Column(name = "angle_of_projection_pass")
    private Boolean angleOfProjectionPass;             // 15 deg maximum total angle

    @Column(name = "calibration_error_pass")
    private Boolean calibrationErrorPass;               // zero/span drift max +/-1% opacity over 30 min

    @Column(name = "response_time_pass")
    private Boolean responseTimePass;                    // +/-5 seconds

    @Column(name = "overall_pass", nullable = false)
    private boolean overallPass;

    @Column(name = "expires_at")
    private OffsetDateTime expiresAt;   // performedAt + 6 months, computed at write time

    @Column(name = "created_at", nullable = false, updatable = false)
    private OffsetDateTime createdAt = OffsetDateTime.now();
}
