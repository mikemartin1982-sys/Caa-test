package com.caa.platform.enrollment;

import org.springframework.stereotype.Service;

import java.util.List;

/**
 * Section 4g: two independent triggers.
 *  - Per-student certification document: AUTOMATIC, the instant an
 *    Enrollment's rosterStatus flips to CERTIFIED. No staff action, no
 *    waiting on the rest of the roster.
 *  - Client summary email: MANUAL, via "Send Summary Email" -- only
 *    active once every roster entry on the session has reached a
 *    terminal status (CERTIFIED/DNC/DNA). ARR alone is not terminal.
 */
@Service
public class SessionCloseOutService {

    /** Whether the "Send Summary Email" button should be enabled for this session's roster. */
    public boolean isSummaryEmailReady(List<Enrollment> sessionRoster) {
        if (sessionRoster.isEmpty()) {
            return false;
        }
        return sessionRoster.stream()
                .allMatch(e -> e.getRosterStatus() != null && e.getRosterStatus().isTerminal());
    }

    /** True the instant this specific enrollment should trigger its per-student certification email. */
    public boolean shouldSendCertificationDocument(Enrollment enrollment) {
        return enrollment.getRosterStatus() == RosterStatus.CERTIFIED;
    }
}
