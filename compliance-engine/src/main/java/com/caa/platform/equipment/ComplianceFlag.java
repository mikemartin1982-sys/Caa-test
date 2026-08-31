package com.caa.platform.equipment;

import com.caa.platform.certification.CertificationRun;
import com.caa.platform.staff.StaffUser;
import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;
import org.hibernate.annotations.JdbcTypeCode;
import org.hibernate.type.SqlTypes;

import java.time.OffsetDateTime;
import java.util.HashSet;
import java.util.Set;

/**
 * Section 4h: raised when a CertificationRun happens under an expired or
 * failed 5-Filter. Does NOT block testing in real time -- flagged for
 * audit review, must be resolved once staff are aware. Tracked to
 * completion via the status lifecycle, not a passive one-time notice.
 */
@Entity
@Table(name = "compliance_flags")
@Getter
@Setter
@NoArgsConstructor
public class ComplianceFlag {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "testing_system_id", nullable = false)
    private TestingSystem testingSystem;

    /** '5filter_expired' | '5filter_failed' */
    @Column(nullable = false)
    private String reason;

    @Column(name = "raised_at", nullable = false)
    private OffsetDateTime raisedAt = OffsetDateTime.now();

    @Enumerated(EnumType.STRING)
    @JdbcTypeCode(SqlTypes.NAMED_ENUM)
    @Column(nullable = false)
    private ComplianceFlagStatus status = ComplianceFlagStatus.OPEN;

    @Column(name = "resolution_notes")
    private String resolutionNotes;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "resolved_by")
    private StaffUser resolvedBy;

    @Column(name = "resolved_at")
    private OffsetDateTime resolvedAt;

    /** Runs administered while the calibration was expired/failed. */
    @ManyToMany
    @JoinTable(
            name = "compliance_flag_runs",
            joinColumns = @JoinColumn(name = "compliance_flag_id"),
            inverseJoinColumns = @JoinColumn(name = "certification_run_id")
    )
    private Set<CertificationRun> affectedRuns = new HashSet<>();
}
