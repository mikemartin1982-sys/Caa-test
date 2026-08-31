package com.caa.platform.enrollment;

import com.caa.platform.client.Client;
import com.caa.platform.session.Session;
import com.caa.platform.student.Student;
import org.springframework.stereotype.Service;

/**
 * Michael, 2026-08-23 -- gates enrollment into a VR session
 * (Session.vrSession, covering both Public rolling-monthly VR and
 * Private VR set up for a specific client) to clients explicitly
 * flagged Client.vrClient. Confirmed with Michael: this is purely a
 * business/commercial gate (which service a client has signed up
 * for), not a regulatory one -- management has explicitly decided CAA
 * doesn't enforce state-by-state Alt-152-a acceptance the way DIBs'
 * older design implied; that's on the client, matching how
 * vrsmokeschool.com (a competitor) handles it with a disclaimer
 * rather than an enforced restriction.
 *
 * Deliberately narrow in scope for now -- checks the ENROLLMENT's own
 * client (who's actually paying/responsible for this specific
 * enrollment), not Student.employerClient. Those two can legitimately
 * differ (a contractor working across companies, a third party
 * covering someone's certification) -- confirmed with Michael this is
 * intentional, not a gap, and NOT something to broad-sweep-validate
 * here. The employer/billing-client relationship will matter for
 * Organization clients specifically once the Client Page's employee
 * listing makes that connection real and enforceable -- a separate,
 * later piece, not bundled into this gate.
 *
 * This is deliberately its own, reusable service rather than inline
 * logic in EnrollmentController -- the real enrollment UI/workflow
 * doesn't exist yet (every enrollment tonight has gone through raw API
 * calls), so this needs to be ready to wire into whatever's built
 * later (a Manual Enroll screen, a client self-serve portal) without
 * needing to be re-derived each time.
 *
 * Michael, 2026-08-30 -- Lecture Certificate Upload feature. Added a
 * SECOND, independent gate here, not a replacement for the one above:
 * Alt-152-A itself is federally approved, but that approval carries
 * its own built-in precondition -- every student must have a
 * completed lecture on file before attempting VR certification. This
 * is a genuinely different kind of rule than the vrClient check --
 * that one is a commercial/business decision CAA made (deliberately
 * NOT enforcing state-by-state legal acceptance, leaving that to the
 * client); this one is the federal approval's own actual condition,
 * not a business choice CAA could opt out of. Confirmed explicitly
 * with Michael this is not a reversal of the state-acceptance
 * decision -- the two rules simply never overlapped before, since
 * lecture-completion tracking didn't exist when this service was
 * first built.
 *
 * student is nullable, not required -- SessionController.list()'s own
 * real use of this (the client-facing Enroll page's session dropdown)
 * runs BEFORE a specific student is chosen, so there's genuinely
 * nothing to check yet at that point. Passing null skips this
 * particular check without skipping the commercial vrClient gate
 * above it -- once a student IS known (list()'s new optional
 * studentId param, or real enrollment submission), this same method
 * re-evaluates with the real student and enforces it.
 */
@Service
public class VrEnrollmentEligibilityService {

    public boolean isEligible(Session session, Client client, Student student) {
        if (!session.isVrSession()) {
            return true; // not a VR session at all -- nothing to gate
        }
        if (!client.isVrClient()) {
            return false; // commercial gate -- unchanged from before
        }
        if (student != null && !student.isLectureComplete()) {
            return false; // federal Alt-152-A precondition -- only checked once a student is actually known
        }
        return true;
    }
}
