package com.caa.platform.session;

import org.springframework.stereotype.Service;

/**
 * Section 4c: "Copy Forward 6 Months," available on all school types.
 *
 * Carries forward: school type, VR-delivery flag, host/authorized-client
 * structure (handled separately by the caller, see SessionController),
 * address, GPS coordinates, Field Manager/Operator/Proctor 1-3/Truck/
 * Trailer, public-facing notes, external registration contact, and
 * pricing -- though pricing behaves differently by type: Private/
 * Semi-Private's quoted price/headcount carry forward as a reference
 * only (the actual quote regenerates at confirmation); Public's
 * management-set rates carry forward as-is.
 * Also carries forward the GENERAL section's copy-eligible fields per
 * DIBs' own "Is/Not copied on session-copy" labels (real source review,
 * 2026-08-16): session info owner, Advertise-as-Public, Staggered
 * Arrival Times, admin-only Comments2, and the full notified-clients
 * list (handled by the caller, same pattern as authorized clients).
 *
 * Resets: the date (set +6 months, staff adjusts from there), Confirmed/
 * Closed-Out/Lost-Bid status and Team Comments (a new instance starts
 * clean -- enforced simply by never copying SessionComment rows),
 * Publish status, the GENERAL section's NOT-copied fields (not-need-copy
 * override, QBO class ref, session-info-verified tracking, the internal
 * Session Log), and obviously the roster/enrollments and Session ID
 * itself (a new one is issued).
 */
@Service
public class SessionCopyForwardService {

    public Session copyForward(Session original) {
        Session copy = new Session();

        copy.setSchoolType(original.getSchoolType());
        copy.setFormat(original.getFormat());
        copy.setRegion(original.getRegion());
        copy.setVrSession(original.isVrSession());

        copy.setLocationName(original.getLocationName());
        copy.setAddressStreet(original.getAddressStreet());
        copy.setAddressCity(original.getAddressCity());
        copy.setAddressState(original.getAddressState());
        copy.setAddressZip(original.getAddressZip());
        copy.setGridLat(original.getGridLat());
        copy.setGridLng(original.getGridLng());
        copy.setFieldTimezone(original.getFieldTimezone());
        copy.setFieldContact(original.getFieldContact());
        copy.setFieldContactPhone(original.getFieldContactPhone());
        copy.setFieldFacility(original.getFieldFacility());
        copy.setFieldAddress(original.getFieldAddress());
        copy.setFieldCity(original.getFieldCity());
        copy.setFieldState(original.getFieldState());
        copy.setFieldZip(original.getFieldZip());
        copy.setFieldLat(original.getFieldLat());
        copy.setFieldLng(original.getFieldLng());
        copy.setUseClientInfo(original.isUseClientInfo());
        copy.setSchoolGeoArea(original.getSchoolGeoArea());
        // schoolUrl NOT copied -- often specific to one occurrence
        // (e.g. a fresh MS-Teams meeting link each time).

        // Bid section, copy-eligible (standing client relationship /
        // reasonable starting estimates for a recurring occurrence):
        copy.setBidNumSelfpacedLectureAttendees(original.getBidNumSelfpacedLectureAttendees());
        copy.setBidNumInpersonLectureAttendees(original.getBidNumInpersonLectureAttendees());
        copy.setBidNumFieldAttendees(original.getBidNumFieldAttendees());
        copy.setBidNeedPoUpfront(original.isBidNeedPoUpfront());
        copy.setBidNoPublicAllowed(original.isBidNoPublicAllowed());
        copy.setBidRequiresCertOfCompletion(original.isBidRequiresCertOfCompletion());
        copy.setBidNoAddons(original.isBidNoAddons());
        copy.setBidAddonsRequireChangeOrder(original.isBidAddonsRequireChangeOrder());
        // bidLunchOption/bidLunchCost removed (Michael, 2026-08-19).
        // Bid section, explicitly NOT copied -- occurrence-specific
        // negotiation notes (bidExtraDetails*/bidCaaNotes), the custom
        // email message, PO-for-invoice, revision number (starts fresh
        // at 0), and every "last generated/sent" timestamp -- a copied
        // session represents a fresh bid cycle with its own history,
        // left at defaults (null/0) rather than inheriting the
        // original's.

        // Private/Semi-Private: reference values only, not a locked quote --
        // the real quote regenerates when the copy is confirmed (Section 4c).
        copy.setQuotedPrice(original.getQuotedPrice());
        copy.setQuotedHeadcount(original.getQuotedHeadcount());
        copy.setFieldTest(original.getFieldTest());
        copy.setPrivateCost(original.getPrivateCost());

        // Public: fixed management-set rates, carry forward as-is.
        copy.setFieldCertificationPrice(original.getFieldCertificationPrice());
        copy.setSelfPacedLecturePrice(original.getSelfPacedLecturePrice());
        copy.setLateFeeAmount(original.getLateFeeAmount());
        copy.setLateFeeDayThreshold(original.getLateFeeDayThreshold());

        copy.setExternalRegistrationName(original.getExternalRegistrationName());
        copy.setExternalRegistrationPhone(original.getExternalRegistrationPhone());
        copy.setExternalRegistrationNotes(original.getExternalRegistrationNotes());
        copy.setPublicSessionNotes(original.getPublicSessionNotes());

        copy.setPoNumber(original.getPoNumber());
        copy.setNetTermsDays(original.getNetTermsDays());

        copy.setFieldManager(original.getFieldManager());
        copy.setOperator(original.getOperator());
        copy.setProctor1(original.getProctor1());
        copy.setProctor2(original.getProctor2());
        copy.setProctor3(original.getProctor3());
        copy.setTruck(original.getTruck());
        copy.setTrailer(original.getTrailer());
        // stagedLocation NOT copied -- post-session plan is re-decided
        // each time based on the NEXT session's location, not repeated.

        // GENERAL section, copy-eligible per DIBs' own labels:
        copy.setSessionInfoOwner(original.getSessionInfoOwner());
        copy.setAdvertiseSemiPrivateAsPublic(original.isAdvertiseSemiPrivateAsPublic());
        copy.setStaggeredArrivalTimes(original.isStaggeredArrivalTimes());
        copy.setAdminComments(original.getAdminComments());

        // Explicitly reset, not just left at defaults, so the intent is
        // unambiguous to anyone reading this later:
        copy.setPublished(false);
        copy.setConfirmed(false);
        copy.setClosedOut(false);
        copy.setBidLost(false);
        copy.setProctorsAssigned(1);
        copy.setCopiedFromSession(original);

        // GENERAL section, explicitly NOT copied per DIBs' own labels --
        // left at defaults (false/null), stated here for clarity:
        // notNeedCopy(Why), qboClassRefId, sessionInfoVerified(+By/+At),
        // sessionLog.

        // NOT copied, deliberately: SessionDay/dates (staff sets the new
        // date), SessionComment (Confirmed + Team Comments never copy),
        // and of course Enrollments/Roster and the Session ID itself.
        // SessionAuthorizedClient and SessionNotifiedClient rows DO carry
        // forward per Section 4c -- handled by the caller (SessionController)
        // after this new Session is persisted, since those are separate
        // tables keyed on the new ID.

        return copy;
    }
}
