package com.caa.platform.client;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;

import java.time.OffsetDateTime;

/**
 * Michael, 2026-08-31 -- Password Reset feature. Mirrors
 * StaffPasswordResetToken exactly, one per Client reset request.
 *
 * Resolved: Client.passwordHash's own comment says it can be null for
 * a client created the old, staff-entered way -- confirmed with
 * Michael this predates our own platform's self-serve registration
 * flow (account/register), which always sets a real password
 * immediately. So a null passwordHash genuinely means "setting a
 * password for the first time," never a real reset -- see
 * ClientPasswordResetService.requestReset(), which branches the
 * email's own wording on exactly this check.
 */

@Entity
@Table(name = "client_password_reset_tokens")
@Getter
@Setter
@NoArgsConstructor
public class ClientPasswordResetToken {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "client_id", nullable = false)
    private Client client;

    @Column(name = "token_hash", nullable = false, unique = true)
    private String tokenHash;

    @Column(name = "expires_at", nullable = false)
    private OffsetDateTime expiresAt;

    @Column(name = "used_at")
    private OffsetDateTime usedAt;

    @Column(name = "created_at", nullable = false, updatable = false)
    private OffsetDateTime createdAt = OffsetDateTime.now();
}
