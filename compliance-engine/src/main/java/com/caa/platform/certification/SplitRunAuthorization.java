package com.caa.platform.certification;

import com.caa.platform.common.RegionType;
import com.caa.platform.session.SessionFormat;
import com.caa.platform.staff.StaffUser;
import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;
import org.hibernate.annotations.JdbcTypeCode;
import org.hibernate.type.SqlTypes;

import java.time.OffsetDateTime;

/**
 * Section 3b: only ever applies to a White failure -- Black is never
 * split-eligible. IMMUTABLE once saved (no update/delete exposed). Toggle
 * must be disabled for Western (CA) / Texas regions, or VR format (EPA
 * Alt-152-a) -- see {@link SplitRunEligibilityService}.
 */
@Entity
@Table(name = "split_run_authorizations")
@Getter
@Setter
@NoArgsConstructor
public class SplitRunAuthorization {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "certification_id", nullable = false)
    private Certification certification;

    @Column(nullable = false)
    private boolean allowed;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "authorized_by", nullable = false)
    private StaffUser authorizedBy;

    @Column(name = "authorized_at", nullable = false)
    private OffsetDateTime authorizedAt = OffsetDateTime.now();

    @Enumerated(EnumType.STRING)
    @JdbcTypeCode(SqlTypes.NAMED_ENUM)
    @Column(name = "region_at_time")
    private RegionType regionAtTime;

    @Enumerated(EnumType.STRING)
    @JdbcTypeCode(SqlTypes.NAMED_ENUM)
    @Column(name = "format_at_time")
    private SessionFormat formatAtTime;
}
