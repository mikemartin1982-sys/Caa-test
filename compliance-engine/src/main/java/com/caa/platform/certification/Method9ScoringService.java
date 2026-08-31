package com.caa.platform.certification;

import org.springframework.stereotype.Service;

import java.math.BigDecimal;
import java.util.List;

/**
 * Section 3b: implements the two-condition Method 9 pass/fail rule.
 *
 * ONLY TWO WAYS TO FAIL A COLOR:
 *   1. Cumulative deviation for that color's points exceeds 37
 *   2. Any single reading in that color misses by 20% or more (failed reading)
 *
 * There is deliberately NO separate "more than 15%" failure condition --
 * 15% is a UI-only warning color (Orange) in the live feedback system, not
 * a scoring rule. 37 is an INDEPENDENT cap per color, never a combined 37
 * across both.
 *
 * This service scores from Observations already committed to a
 * CertificationRun -- it does not gate/hard-stop mid-run (Section 4g's
 * "no hard-stop during testing" requirement is a UI/workflow concern
 * upstream of this class, not something this scoring step enforces).
 */
@Service
public class Method9ScoringService {

    private static final BigDecimal CUMULATIVE_DEVIATION_CAP = new BigDecimal("37");
    private static final short FAILED_READING_THRESHOLD = 20;

    public record ColorScore(BigDecimal cumulativeDeviation, boolean anyFailedReading, boolean pass) {}

    /**
     * Scores a single color's block of Observations (25 points for White
     * in a standard or split run, or 25 points for Black in a standard run).
     */
    public ColorScore scoreColor(List<Observation> observationsForColor) {
        BigDecimal cumulative = observationsForColor.stream()
                .map(o -> BigDecimal.valueOf(o.getDeviation()))
                .reduce(BigDecimal.ZERO, BigDecimal::add);

        boolean anyFailedReading = observationsForColor.stream().anyMatch(Observation::isFailedReading);

        boolean pass = cumulative.compareTo(CUMULATIVE_DEVIATION_CAP) <= 0 && !anyFailedReading;

        return new ColorScore(cumulative, anyFailedReading, pass);
    }

    /** Computes and sets the failedReading flag for a single Observation (deviation >= 20). */
    public void scoreObservation(Observation observation) {
        short deviation = (short) Math.abs(observation.getTrueOpacityValue() - observation.getStudentEstimatedOpacity());
        observation.setDeviation(deviation);
        observation.setFailedReading(deviation >= FAILED_READING_THRESHOLD);
    }

    /**
     * Applies scoring to a full CertificationRun given its Observations.
     * whiteObservations must always be provided; blackObservations only
     * for a 50-point initial run (a 25-point split retake is White-only).
     */
    public void scoreRun(CertificationRun run, List<Observation> whiteObservations, List<Observation> blackObservations) {
        whiteObservations.forEach(this::scoreObservation);
        ColorScore white = scoreColor(whiteObservations);
        run.setWhiteCumulativeDeviation(white.cumulativeDeviation());
        run.setWhiteFailedReading(white.anyFailedReading());
        run.setWhitePass(white.pass());

        if (run.isSplitRetake()) {
            // Black is never split -- a 25-point retake has no Black block at all.
            run.setBlackCumulativeDeviation(null);
            run.setBlackFailedReading(false);
            run.setBlackPass(null);
            return;
        }

        blackObservations.forEach(this::scoreObservation);
        ColorScore black = scoreColor(blackObservations);
        run.setBlackCumulativeDeviation(black.cumulativeDeviation());
        run.setBlackFailedReading(black.anyFailedReading());
        run.setBlackPass(black.pass());
    }
}
