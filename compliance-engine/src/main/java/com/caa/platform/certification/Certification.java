package com.caa.platform.certification;

import com.caa.platform.enrollment.Enrollment;
import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;

import java.math.BigDecimal;
import java.time.LocalDate;
import java.time.OffsetDateTime;

/**
 * Section 3/3b: the overall pass/fail result for an Enrollment. whiteRun /
 * blackRun point to which CertificationRun satisfied each color -- the
 * SAME run for a standard pass; TWO different runs only for a White-split-run
 * pass (Black is always satisfied by the same run that satisfies the
 * overall pass, since Black is never split).
 */
@Entity
@Table(name = "certifications")
@Getter
@Setter
@NoArgsConstructor
public class Certification {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @OneToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "enrollment_id", nullable = false, unique = true)
    private Enrollment enrollment;

    @Column(name = "issue_date")
    private LocalDate issueDate;

    @Column(name = "expiration_date")
    private LocalDate expirationDate;

    @Column(name = "pdf_certificate_link")
    private String pdfCertificateLink;

    /**
     * Drawn (finger/stylus) signature, captured after a passing grade
     * (Michael, 2026-08-17) -- follows the EXACT same pattern as
     * pdfCertificateLink above: a file path, not a DB blob.
     */
    @Column(name = "signature_image_path", length = 500)
    private String signatureImagePath;

    @Column(name = "black_cumulative_deviation", precision = 6, scale = 2)
    private BigDecimal blackCumulativeDeviation;

    @Column(name = "white_cumulative_deviation", precision = 6, scale = 2)
    private BigDecimal whiteCumulativeDeviation;

    @Column(name = "any_failed_reading", nullable = false)
    private boolean anyFailedReading = false;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "white_run_id")
    private CertificationRun whiteRun;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "black_run_id")
    private CertificationRun blackRun;

    @Column(name = "pass_fail")
    private Boolean passFail;

    @Column(name = "created_at", nullable = false, updatable = false)
    private OffsetDateTime createdAt = OffsetDateTime.now();

    @Column(name = "updated_at")
    private OffsetDateTime updatedAt = OffsetDateTime.now();

    public boolean isSplitRunResult() {
        return whiteRun != null && blackRun != null && !whiteRun.getId().equals(blackRun.getId());
    }
}
