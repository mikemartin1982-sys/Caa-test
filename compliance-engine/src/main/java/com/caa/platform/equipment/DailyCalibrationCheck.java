package com.caa.platform.equipment;

import com.caa.platform.session.SessionDay;
import com.caa.platform.staff.StaffUser;
import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;

import java.time.LocalDate;
import java.time.OffsetDateTime;

/**
 * Section 4h: a CAA quality-assurance practice, NOT a Method requirement.
 * The Operator performs this each day before a school begins, using the
 * Tablet/Chart Recorder. Lighter-weight than {@link CalibrationRecord} --
 * deliberately not tied into the ComplianceFlag audit mechanism, since
 * there's no regulatory obligation attached to it.
 */
@Entity
@Table(name = "daily_calibration_checks")
@Getter
@Setter
@NoArgsConstructor
public class DailyCalibrationCheck {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "testing_system_id", nullable = false)
    private TestingSystem testingSystem;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "session_day_id", nullable = false)
    private SessionDay sessionDay;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "operator_id", nullable = false)
    private StaffUser operator;

    @Column(name = "check_date", nullable = false)
    private LocalDate checkDate = LocalDate.now();

    @Column(nullable = false)
    private boolean result;

    @Column(name = "created_at", nullable = false, updatable = false)
    private OffsetDateTime createdAt = OffsetDateTime.now();
}
