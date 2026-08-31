package com.caa.platform.integration.qbo;

import com.caa.platform.staff.StaffUser;
import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;

import java.time.OffsetDateTime;

/**
 * Michael, 2026-08-25 -- QBO integration, layer 2. One company-wide
 * connection (not per-client) -- confirmed a real, DB-level unique
 * partial index in migration 032 enforces at most one row with
 * active=true at a time, not just trusted to application code. A
 * disconnect/reconnect inserts a new row rather than updating in
 * place, so past connections stay visible as history rather than
 * being silently overwritten.
 *
 * access_token/refresh_token are encrypted transparently on every
 * read/write via QboTokenEncryptionConverter -- this entity's own
 * code never sees or handles plaintext tokens directly except through
 * the getters/setters, which is exactly the point of using a
 * converter here instead of manual encrypt/decrypt calls scattered
 * wherever this entity is touched.
 */
@Entity
@Table(name = "qbo_connections")
@Getter
@Setter
@NoArgsConstructor
public class QboConnection {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Column(name = "realm_id", nullable = false, length = 50)
    private String realmId;

    @Convert(converter = QboTokenEncryptionConverter.class)
    @Column(name = "access_token", nullable = false, columnDefinition = "TEXT")
    private String accessToken;

    @Convert(converter = QboTokenEncryptionConverter.class)
    @Column(name = "refresh_token", nullable = false, columnDefinition = "TEXT")
    private String refreshToken;

    @Column(name = "access_token_expires_at", nullable = false)
    private OffsetDateTime accessTokenExpiresAt;

    @Column(name = "refresh_token_expires_at", nullable = false)
    private OffsetDateTime refreshTokenExpiresAt;

    /** 'SANDBOX' | 'PRODUCTION' -- determines which QBO API base URL this connection's calls use. */
    @Column(name = "environment", nullable = false, length = 10)
    private String environment = "SANDBOX";

    @Column(nullable = false)
    private boolean active = true;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "connected_by_staff_id")
    private StaffUser connectedByStaff;

    @Column(name = "created_at", nullable = false, updatable = false)
    private OffsetDateTime createdAt = OffsetDateTime.now();

    @Column(name = "updated_at", nullable = false)
    private OffsetDateTime updatedAt = OffsetDateTime.now();

    @PreUpdate
    void onUpdate() {
        this.updatedAt = OffsetDateTime.now();
    }

    /** True once the access token has actually expired -- callers use this to decide whether a refresh is needed before making a QBO API call. */
    public boolean isAccessTokenExpired() {
        return accessTokenExpiresAt != null && OffsetDateTime.now().isAfter(accessTokenExpiresAt);
    }
}
