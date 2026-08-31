package com.caa.platform.certification;

import com.caa.platform.common.RegionType;
import com.caa.platform.session.SessionFormat;
import org.springframework.stereotype.Service;

/**
 * Section 3b: split-run is WHITE-ONLY -- it never applies to Black.
 *
 *  - White fails + Black passes -> split-run eligible for White only.
 *  - Black fails (regardless of White) -> NO split-run option at all; a
 *    full new 50-point run is required outright, because the requirement
 *    is 50 consecutive points, and that continuity only holds when White
 *    (administered first) is retaken immediately ahead of an ALREADY
 *    PASSING Black -- never the reverse.
 *
 * Hard regional/format blocks (not staff-overridable defaults):
 *  - Western (California) and Texas: split-run never permitted, any format.
 *  - VR (EPA Alt-152-a): split-run not currently permitted, any region.
 *    This restriction should be built as a CONFIGURABLE rule (Section 3b)
 *    since Alt-152-a is being watched for revision -- represented here as
 *    a simple boolean for the scaffold; wire to the state compliance rules
 *    engine (Section 4's design note) when that's built out.
 */
@Service
public class SplitRunEligibilityService {

    // Placeholder for the Alt-152-a configurable rule (Section 3b) -- flip
    // via the Compliance Administrator role (StaffRole.COMPLIANCE_ADMINISTRATOR)
    // once EPA revises the regulation, not via a code change.
    private volatile boolean vrSplitRunPermittedByAlt152a = false;

    public enum EligibilityResult {
        ELIGIBLE_WHITE_RETAKE,
        NOT_ELIGIBLE_BLACK_FAILED,   // Black failed -- full new 50-point run required, no split-run
        NOT_ELIGIBLE_BOTH_PASSED,    // nothing to split -- run already passed
        BLOCKED_REGION,              // Western/Texas -- hard block regardless of White/Black outcome
        BLOCKED_FORMAT                // VR under current Alt-152-a state
    }

    public EligibilityResult evaluate(CertificationRun run, RegionType region, SessionFormat format) {
        if (region == RegionType.WESTERN || region == RegionType.TEXAS) {
            return EligibilityResult.BLOCKED_REGION;
        }
        if (format == SessionFormat.VR && !vrSplitRunPermittedByAlt152a) {
            return EligibilityResult.BLOCKED_FORMAT;
        }

        boolean whitePassed = Boolean.TRUE.equals(run.getWhitePass());
        boolean blackPassed = Boolean.TRUE.equals(run.getBlackPass());

        if (whitePassed && blackPassed) {
            return EligibilityResult.NOT_ELIGIBLE_BOTH_PASSED;
        }
        if (!blackPassed) {
            // Black failing -- regardless of White's result -- always requires
            // a full new 50-point run. Never eligible for split-run.
            return EligibilityResult.NOT_ELIGIBLE_BLACK_FAILED;
        }
        // Black passed, White failed -> eligible for a White-only retake.
        return EligibilityResult.ELIGIBLE_WHITE_RETAKE;
    }

    /**
     * Validates the succession requirement for a proposed retake: same
     * Session ID as the run it succeeds, no other runs in between for
     * this student/color chain. No time limit otherwise (Section 3b).
     */
    public boolean isValidSuccession(CertificationRun priorRun, CertificationRun proposedRetake) {
        if (!proposedRetake.isSplitRetake()) {
            return false;
        }
        return priorRun.getSession().getId().equals(proposedRetake.getSession().getId())
                && proposedRetake.getPriorRun() != null
                && proposedRetake.getPriorRun().getId().equals(priorRun.getId());
        // "No other runs in between" is a query-level check (verifying no
        // CertificationRun with a run_number between priorRun and
        // proposedRetake exists for this session/chain) -- implement in
        // the repository layer once the full retry-chain query is needed.
    }
}
