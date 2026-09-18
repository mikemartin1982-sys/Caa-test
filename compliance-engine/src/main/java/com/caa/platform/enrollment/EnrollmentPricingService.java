package com.caa.platform.enrollment;

import com.caa.platform.client.Client;
import com.caa.platform.session.Session;
import com.caa.platform.session.SessionDay;
import com.caa.platform.session.SessionDayRepository;
import com.caa.platform.session.SchoolType;
import org.springframework.stereotype.Service;

import java.math.BigDecimal;
import java.time.LocalDate;

/**
 * Michael, 2026-08-31 -- QBO Per-Student Invoicing. Confirmed with
 * Michael: PUBLIC sessions only -- a PRIVATE session, VR-bulk or
 * otherwise, already has its own, separate, working flat-fee invoice
 * path (QboInvoiceService.generatePrivateSessionInvoice(), one line
 * for Session.privateCost) -- found live that Session.vrSession can be
 * true on either PUBLIC or PRIVATE sessions, not Public exclusively,
 * so this guard matters, not just documentation.
 *
 * Deliberately stateless and side-effect-free -- nothing here is ever
 * stored anywhere. Confirmed with Michael: pricing inputs
 * (Client.vrPricingOverrideRate, Session.fieldCertificationPrice) can
 * change between when a student is enrolled and when an invoice
 * actually gets generated for them -- computing fresh, on demand,
 * every time a price is needed (both on the post-enrollment
 * confirmation screen and again at real invoice-generation time) means
 * it's never stale, unlike storing a locked-in number early would
 * risk.
 */
@Service
public class EnrollmentPricingService {

    private static final BigDecimal DEFAULT_VR_PRICE = new BigDecimal("250.00");
    private static final BigDecimal LECTURE_PRICE = new BigDecimal("50.00");

    /**
     * Michael, 2026-08-31 -- confirmed with Michael as hardcoded
     * constants specifically -- Session.lateFeeAmount/lateFeeDayThreshold
     * DO exist as real columns, but Michael was explicit: "late fee
     * should not be present in the Session Details" at all, so these
     * are deliberately never read here, even as a fallback.
     */
    private static final BigDecimal LATE_FEE_AMOUNT = new BigDecimal("25.00");
    private static final int LATE_FEE_DAY_THRESHOLD = 7;

    private final SessionDayRepository sessionDayRepository;

    public EnrollmentPricingService(SessionDayRepository sessionDayRepository) {
        this.sessionDayRepository = sessionDayRepository;
    }

    /**
     * The real, per-line price for this one enrollment -- $0 for an
     * outsideAttendee (confirmed with Michael as "excluded from CAA's
     * own billing" entirely) or a lecture-exempt student, never a
     * missing/null line.
     */
    public BigDecimal computePrice(Enrollment enrollment) {
        if (enrollment.isOutsideAttendee()) {
            return BigDecimal.ZERO;
        }

        Session session = enrollment.getSession();
        if (session.getSchoolType() != SchoolType.PUBLIC) {
            throw new IllegalArgumentException(
                    "EnrollmentPricingService only handles PUBLIC sessions -- session " + session.getId()
                            + " is " + session.getSchoolType() + ". Private sessions use QboInvoiceService's own flat-fee path instead.");
        }

        if (enrollment.getEnrollmentComponents() == EnrollmentComponents.LECTURE_ONLY) {
            // Michael, 2026-09-01 -- found live: this originally
            // treated Enrollment.lectureAccessGranted as the billing
            // exemption -- wrong. That field is set unconditionally
            // true for EVERY LECTURE_ONLY enrollment by
            // EnrollmentController.create() ("this student has been
            // granted access to the lecture content," nothing to do
            // with billing), so this was silently zeroing out every
            // lecture charge, not just genuine exemptions.
            //
            // Michael, 2026-09-03 -- the real, dedicated exemption
            // fields now exist -- two genuinely separate, independent
            // scopes confirmed with Michael: Client.lectureFeeExempt
            // (a client-wide exemption, e.g. a government agency
            // management has decided to offer the lecture to at no
            // charge) and Student.lectureFeeExempt (a one-off, e.g.
            // logistics resolving a technical issue for one specific
            // person). Either one being true exempts this enrollment.
            // No reason is recorded for which -- confirmed with
            // Michael as not currently tracked in DIBs either, though
            // worth revisiting if management ever wants that later.
            if (enrollment.getClient().isLectureFeeExempt() || enrollment.getStudent().isLectureFeeExempt()) {
                return BigDecimal.ZERO;
            }
            return LECTURE_PRICE;
        }

        // FIELD_ONLY
        if (session.isVrSession()) {
            Client client = enrollment.getClient();
            if (client.isVrPricingNoCost()) {
                return BigDecimal.ZERO;
            }
            return client.getVrPricingOverrideRate() != null ? client.getVrPricingOverrideRate() : DEFAULT_VR_PRICE;
        }

        return session.getFieldCertificationPrice();
    }

    /**
     * Michael, 2026-08-31 -- "7 days before the session start date,"
     * assessed against SessionDay (a Session's real start, not a
     * single flat date field on Session itself) -- confirmed with
     * Michael directly, matches SessionDayRepository's own
     * findFirstBySessionIdOrderBySessionDateAsc(), already built and
     * already used elsewhere in this project for exactly this "the
     * session's real date" purpose.
     *
     * Boundary is inclusive on purpose: enrolling exactly 7 days
     * before the start still counts as late, not just 6 days or
     * fewer -- !isBefore(), not isAfter(), since isAfter() would
     * incorrectly exclude that boundary day itself.
     */
    public boolean isLateEnrollment(Enrollment enrollment) {
        return sessionDayRepository.findFirstBySessionIdOrderBySessionDateAsc(enrollment.getSession().getId())
                .map(SessionDay::getSessionDate)
                .map(startDate -> {
                    LocalDate enrollmentDate = enrollment.getEnrollmentDate().toLocalDate();
                    return !enrollmentDate.isBefore(startDate.minusDays(LATE_FEE_DAY_THRESHOLD));
                })
                .orElse(false);
    }

    public BigDecimal lateFeeAmount() {
        return LATE_FEE_AMOUNT;
    }
}
