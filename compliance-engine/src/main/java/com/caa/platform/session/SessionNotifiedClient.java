package com.caa.platform.session;

import com.caa.platform.client.Client;
import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;

import java.time.OffsetDateTime;

/**
 * Section 4c "Clients to be Notified" -- a proper relation instead of
 * DIBs' comma-separated text field ("2183[Linde - Kingman AZ],
 * 3851[Honeywell - Kingman, AZ]"), so the 60-day-before-session
 * notification-email logic (not yet built) can query it reliably rather
 * than parsing free text. Confirmed "IS copied on session-copy" in
 * DIBs' own source -- see SessionCopyForwardService.
 */
@Entity
@Table(name = "session_notified_clients")
@Getter
@Setter
@NoArgsConstructor
public class SessionNotifiedClient {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "session_id", nullable = false)
    private Session session;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "client_id", nullable = false)
    private Client client;

    @Column(name = "created_at", nullable = false)
    private OffsetDateTime createdAt = OffsetDateTime.now();
}
