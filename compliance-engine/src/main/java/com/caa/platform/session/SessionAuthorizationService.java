package com.caa.platform.session;

import com.caa.platform.client.Client;
import com.caa.platform.staff.StaffUser;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

/**
 * Section 4: enforces the deliberate Private-vs-Semi-Private distinction.
 * This is intentionally an APPLICATION-layer rule, not a DB constraint
 * (see db/README.md) -- the schema allows either school_type to carry
 * additional authorized clients; only this service actually blocks it for
 * PRIVATE (and VTCA, which behaves identically -- see SchoolType).
 *
 * PRIVATE/VTCA: client ID only, structurally locked to that one host. No
 * outside attendance is possible -- not by staff, not by the client.
 *
 * SEMI_PRIVATE: staff have deliberately chosen this at setup, unlocking
 * the ability for the host (or staff) to add outside organizations.
 */
@Service
public class SessionAuthorizationService {

    private final SessionAuthorizedClientRepository authorizedClientRepository;

    public SessionAuthorizationService(SessionAuthorizedClientRepository authorizedClientRepository) {
        this.authorizedClientRepository = authorizedClientRepository;
    }

    /**
     * Sets the host client for a Private, Semi-Private, VTCA, or
     * Proposed session -- REPLACES any existing host rather than
     * throwing, so this now also serves "correct/change the host after
     * creation" (confirmed with Michael: staff need this directly from
     * Session Details' GENERAL section, matching DIBs' "Company Name
     * (Client)" field -- there was previously no way to do this at all
     * once a session already existed). Safe for the original
     * create()-time use case too, since a brand-new session never
     * already has a host to replace.
     */
    @Transactional
    public SessionAuthorizedClient setHost(Session session, Client hostClient, StaffUser addedBy) {
        requireSchoolType(session);
        // .flush() forces the DELETE to actually hit the database before
        // the INSERT below runs -- without it, Hibernate's default flush
        // ordering (inserts before deletes within one transaction) tries
        // the INSERT while the old host row is still technically present,
        // violating idx_one_host_per_session (confirmed live, 2026-08-16).
        authorizedClientRepository.findBySessionIdAndIsHostTrue(session.getId())
                .ifPresent(existing -> {
                    authorizedClientRepository.delete(existing);
                    authorizedClientRepository.flush();
                });
        SessionAuthorizedClient host = new SessionAuthorizedClient();
        host.setSession(session);
        host.setClient(hostClient);
        host.setHost(true);
        host.setAddedBy(addedBy);
        return authorizedClientRepository.save(host);
    }

    /**
     * Adds an outside organization to a Semi-Private session's authorized
     * list. Throws for Private sessions -- this is the actual enforcement
     * point for "Private is structurally locked, no additions by anyone."
     *
     * @param addedBy null if the host client is adding this themselves via
     *                the Client Portal (Section 4e self-service), non-null
     *                if a staff member added it.
     */
    @Transactional
    public SessionAuthorizedClient authorizeOutsideClient(Session session, Client outsideClient, StaffUser addedBy) {
        if (session.getSchoolType() != SchoolType.SEMI_PRIVATE) {
            throw new IllegalStateException(
                    "Session " + session.getId() + " is " + session.getSchoolType()
                            + " -- only Semi-Private sessions permit outside-client authorization.");
        }
        if (authorizedClientRepository.existsBySessionIdAndClientId(session.getId(), outsideClient.getId())) {
            throw new IllegalStateException("Client is already authorized on this session.");
        }
        SessionAuthorizedClient entry = new SessionAuthorizedClient();
        entry.setSession(session);
        entry.setClient(outsideClient);
        entry.setHost(false);
        entry.setAddedBy(addedBy);
        return authorizedClientRepository.save(entry);
    }

    private void requireSchoolType(Session session) {
        if (session.getSchoolType() != SchoolType.PRIVATE
                && session.getSchoolType() != SchoolType.SEMI_PRIVATE
                && session.getSchoolType() != SchoolType.VTCA
                && session.getSchoolType() != SchoolType.PROPOSED) {
            throw new IllegalStateException("Host client only applies to Private/Semi-Private/VTCA/Proposed sessions.");
        }
    }

    /**
     * Michael, 2026-08-23 -- enrollment-time check: is this client
     * actually allowed to have a student enrolled in this session?
     * PUBLIC has no client restriction at all (that's the point of
     * Public). PRIVATE/VTCA/PROPOSED are structurally locked to the one
     * host client, same enforcement as setHost()/authorizeOutsideClient()
     * above. SEMI_PRIVATE allows the host or anyone on the authorized-
     * outside-client list. A session with no host set at all (shouldn't
     * happen in practice for these types, but not assumed away) can't
     * authorize anyone.
     */
    @Transactional(readOnly = true)
    public boolean isClientAuthorizedToEnroll(Session session, Client client) {
        if (session.getSchoolType() == SchoolType.PUBLIC) {
            return true;
        }
        if (session.getSchoolType() == SchoolType.SEMI_PRIVATE) {
            return authorizedClientRepository.existsBySessionIdAndClientId(session.getId(), client.getId());
        }
        // PRIVATE / VTCA / PROPOSED -- host only, no exceptions.
        return authorizedClientRepository.findBySessionIdAndIsHostTrue(session.getId())
                .map(host -> host.getClient().getId().equals(client.getId()))
                .orElse(false);
    }
}
