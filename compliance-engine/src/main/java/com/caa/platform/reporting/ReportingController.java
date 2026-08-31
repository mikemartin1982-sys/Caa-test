package com.caa.platform.reporting;

import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import java.util.List;

/**
 * Backs the two reporting features that were the actual original business
 * ask -- Section 4a (session enrollment comparison) and Section 4b (VR
 * rolling certification status). Not part of the original openapi.yaml
 * contract; add /reporting/* entries there to keep the contract in sync
 * before this is considered complete.
 */
@RestController
@RequestMapping("/api/v1/reporting")
public class ReportingController {

    private final SessionComparisonService comparisonService;
    private final VRRollingStatusService vrRollingStatusService;
    private final BrevoTargetListService brevoTargetListService;

    public ReportingController(SessionComparisonService comparisonService,
                                VRRollingStatusService vrRollingStatusService,
                                BrevoTargetListService brevoTargetListService) {
        this.comparisonService = comparisonService;
        this.vrRollingStatusService = vrRollingStatusService;
        this.brevoTargetListService = brevoTargetListService;
    }

    /** Section 4a: current/upcoming Public session vs. its up to 4 most recent past sessions at the same school. */
    @GetMapping("/session-comparison/{sessionId}")
    public ResponseEntity<SessionComparisonService.ComparisonReport> sessionComparison(@PathVariable Long sessionId) {
        return ResponseEntity.ok(comparisonService.compare(sessionId));
    }

    /**
     * Michael, 2026-08-25 -- Section 4a extension: a Brevo-import-ready
     * retention-gap list for the 60/30/14-day enrollment-open campaigns
     * (see BrevoTargetListService's own docblock for the full
     * reasoning and the real Augusta_GA.xlsx sample this matches).
     * Returns both who to email AND who was excluded because they're
     * already committed elsewhere -- confirmed with Michael that the
     * underlying data must stay visible, not just silently dropped.
     */
    @GetMapping("/brevo-target-list/{sessionId}")
    public ResponseEntity<BrevoTargetListService.RetentionGapResult> brevoTargetList(@PathVariable Long sessionId) {
        return ResponseEntity.ok(brevoTargetListService.buildRetentionGapList(sessionId));
    }

    /** Section 4b: VR students anchored to their first attempt, flagged for staff review at the 7-day mark. */
    @GetMapping("/vr-rolling-status")
    public ResponseEntity<List<VRRollingStatusService.RollingStatusEntry>> vrRollingStatus() {
        return ResponseEntity.ok(vrRollingStatusService.currentRollingStatus());
    }
}
