package com.caa.platform.certification;

import com.caa.platform.equipment.Trailer;
import com.caa.platform.equipment.TestingSystem;
import com.caa.platform.session.Session;
import com.caa.platform.staff.StaffUser;
import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;

import java.math.BigDecimal;
import java.time.OffsetDateTime;

/**
 * Section 3b: every run is exactly 50 points (initial) or exactly 25 points
 * (a White-only split-run retake) -- never a range. A 50-point run is fixed
 * order: points 1-25 White, 26-50 Black. A 25-point retake is White only --
 * Black is NEVER split. priorRun chains a retake to the run it succeeds,
 * for the succession check (same Session, no runs in between, no time limit).
 *
 * Pass/fail per color is independent: cumulative deviation > 37 OR any
 * single reading missing by 20%+ = fail for that color. See
 * {@link Method9ScoringService} for the actual scoring logic.
 */
@Entity
@Table(name = "certification_runs")
@Getter
@Setter
@NoArgsConstructor
public class CertificationRun {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "session_id", nullable = false)
    private Session session;

    @Column(name = "run_number", nullable = false)
    private Integer runNumber;

    /** 50 (initial) or 25 (White-only split-run retake). */
    @Column(name = "point_count", nullable = false)
    private Short pointCount;

    @Column(name = "performed_at", nullable = false)
    private OffsetDateTime performedAt;

    /** Succession chain: must share the same session as the run it succeeds. */
    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "prior_run_id")
    private CertificationRun priorRun;

    // --- Chain of custody (Section 4h) ---
    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "trailer_id", nullable = false)
    private Trailer trailer;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "testing_system_id", nullable = false)
    private TestingSystem testingSystem;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "administered_by", nullable = false)
    private StaffUser administeredBy;

    // --- White result -- always present ---
    @Column(name = "white_cumulative_deviation", precision = 6, scale = 2)
    private BigDecimal whiteCumulativeDeviation;

    @Column(name = "white_failed_reading", nullable = false)
    private boolean whiteFailedReading = false;

    @Column(name = "white_pass")
    private Boolean whitePass;

    // --- Black result -- NULL when pointCount = 25 ---
    @Column(name = "black_cumulative_deviation", precision = 6, scale = 2)
    private BigDecimal blackCumulativeDeviation;

    @Column(name = "black_failed_reading", nullable = false)
    private boolean blackFailedReading = false;

    @Column(name = "black_pass")
    private Boolean blackPass;

    @Column(name = "created_at", nullable = false, updatable = false)
    private OffsetDateTime createdAt = OffsetDateTime.now();

    /**
     * Default false, matching the original batch-creation flow
     * (CertificationRunController.create()), which always scores
     * in-memory before the first save -- a run is complete/final the
     * moment it exists. Only LiveTestingService.startLiveTest() sets
     * this true, since that flow genuinely needs an unscored row to
     * exist first so incoming Observations have a real
     * certification_run_id to attach to. See migration 018 for the
     * real collision this fixed (chk_50pt_requires_both_colors assumed
     * every run was always fully scored at insert time).
     */
    @Column(name = "in_progress", nullable = false)
    private boolean inProgress = false;

    /**
     * Set when staff mark a student DNC/DNA during an unfinished live
     * test. This is intentionally distinct from a normally completed,
     * scored run: genuine observations remain for audit, but the attempt
     * is no longer active and can never be mistaken for a graded result.
     */
    @Column(name = "abandoned_at")
    private OffsetDateTime abandonedAt;

    /**
     * Set once this student explicitly attests "these answers are your
     * own, not somebody else's" (Michael, 2026-08-17), after reaching
     * their final point. Required before the Operator/Field Manager can
     * grade -- see LiveTestingService.gradeTest()'s Javadoc.
     */
    @Column(name = "final_answers_confirmed", nullable = false)
    private boolean finalAnswersConfirmed = false;

    @Column(name = "final_answers_confirmed_at")
    private OffsetDateTime finalAnswersConfirmedAt;

    /**
     * Separate from inProgress (migration 018) -- inProgress still
     * means "not yet graded/scored"; this means "all required point
     * observations are in, but maybe not yet confirmed or graded."
     * This split is what lets split-run participants (done at point
     * 25) stop being waited on for POINT-BY-POINT gating the instant
     * they finish, while remaining gradable independently of whenever
     * the full-run group finishes at 50.
     */
    @Column(name = "submissions_complete", nullable = false)
    private boolean submissionsComplete = false;

    public boolean isSplitRetake() {
        return pointCount == 25;
    }
}
