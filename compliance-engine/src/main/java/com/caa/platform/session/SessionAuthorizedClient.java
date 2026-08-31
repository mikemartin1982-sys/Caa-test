package com.caa.platform.session;

import com.caa.platform.client.Client;
import com.caa.platform.staff.StaffUser;
import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;

import java.time.OffsetDateTime;

/**
 * Section 3 / 4: the shared Private/Semi-Private/VTCA mechanism. The
 * isHost=true entry is created when staff enter a Client ID. For
 * {@link SchoolType#PRIVATE} and {@link SchoolType#VTCA} (structurally
 * identical), this must be the ONLY row -- enforced in
 * {@link SessionAuthorizationService}, not at the DB layer, since it's a
 * business/UX rule rather than a structural one (see db/README.md).
 */
@Entity
@Table(name = "session_authorized_clients")
@Getter
@Setter
@NoArgsConstructor
public class SessionAuthorizedClient {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "session_id", nullable = false)
    private Session session;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "client_id", nullable = false)
    private Client client;

    @Column(name = "is_host", nullable = false)
    private boolean isHost = false;

    /** Null if added by the client themselves via the Client Portal. */
    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "added_by")
    private StaffUser addedBy;

    @Column(name = "added_at", nullable = false)
    private OffsetDateTime addedAt = OffsetDateTime.now();
}
