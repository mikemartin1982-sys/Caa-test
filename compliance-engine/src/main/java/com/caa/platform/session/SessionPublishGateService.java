package com.caa.platform.session;

import org.springframework.stereotype.Service;

/**
 * Section 4c: the Publish checkbox is only available once required fields
 * are complete (Location Name, Address, Pricing, GPS Coordinates). Once
 * toggled on, it stays on through routine edits -- no need to retoggle.
 * This service only ever checks READINESS to publish; it never un-publishes
 * on its own.
 */
@Service
public class SessionPublishGateService {

    public boolean isReadyToPublish(Session session) {
        boolean hasLocation = session.getLocationName() != null && !session.getLocationName().isBlank();
        boolean hasAddress = session.getAddressStreet() != null && session.getAddressCity() != null
                && session.getAddressState() != null;
        boolean hasGps = session.getGridLat() != null && session.getGridLng() != null;

        // VR is a delivery-method modifier (applies to PUBLIC or PRIVATE),
        // not its own SchoolType -- a VR session skips both the normal
        // pricing check (its pricing lives on the Client's token block,
        // not the Session) and the location check (no physical location),
        // regardless of which underlying type it is.
        boolean hasPricing = session.isVrSession() || switch (session.getSchoolType()) {
            case PRIVATE, SEMI_PRIVATE, VTCA, PROPOSED -> session.getQuotedPrice() != null;
            case PUBLIC -> session.getFieldCertificationPrice() != null && session.getSelfPacedLecturePrice() != null;
        };
        boolean hasLocationRequirements = session.isVrSession() || (hasLocation && hasAddress && hasGps);

        return hasLocationRequirements && hasPricing;
    }

    /** Once true, publish is sticky -- this method never flips it back to false on its own. */
    public void publishIfReady(Session session) {
        if (!session.isPublished() && isReadyToPublish(session)) {
            session.setPublished(true);
        }
    }
}
