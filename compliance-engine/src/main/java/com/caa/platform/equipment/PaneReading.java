package com.caa.platform.equipment;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;

import java.math.BigDecimal;

/**
 * Section 4h: the actual 5-Filter procedure. 3 panes x 5 readings each,
 * low-to-high succession -- 15 rows per CalibrationRecord. Tolerance is
 * +/-3% from the pane's certified opacity value. This is the actual origin
 * of the name "5-Filter" (5 repetitions per pane, not 5 different filters).
 */
@Entity
@Table(name = "pane_readings")
@Getter
@Setter
@NoArgsConstructor
public class PaneReading {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "calibration_record_id", nullable = false)
    private CalibrationRecord calibrationRecord;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "calibration_pane_id", nullable = false)
    private CalibrationPane calibrationPane;

    /** 1-5, per pane. */
    @Column(name = "sequence_number", nullable = false)
    private Short sequenceNumber;

    /** From the Monitor display, via Chart Recorder. */
    @Column(name = "recorded_value", nullable = false, precision = 5, scale = 2)
    private BigDecimal recordedValue;

    @Column(nullable = false, precision = 5, scale = 2)
    private BigDecimal deviation;

    /** Within the +/-3% tolerance from the pane's certified opacity value. */
    @Column(name = "within_tolerance", nullable = false)
    private boolean withinTolerance;
}
