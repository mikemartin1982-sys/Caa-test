package com.caa.platform.staff;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;

import java.time.OffsetDateTime;

/**
 * Michael, 2026-08-31 -- Password Reset feature. Mirrors
 * ClientPasswordResetToken exactly, one per StaffUser reset request.
 * tokenHash only, never the raw token -- same principle as
 * StaffUser.passwordHash itself. usedAt (nullable) marks a token
 * consumed without deleting the row, for a real audit trail rather than
 * a silently vanishing one.
 */
@Entity
@Table(name = "staff_password_reset_tokens")
@Getter
@Setter
@NoArgsConstructor
public class StaffPasswordResetToken {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "staff_user_id", nullable = false)
    private StaffUser staffUser;

    @Column(name = "token_hash", nullable = false, unique = true)
    private String tokenHash;

    @Column(name = "expires_at", nullable = false)
    private OffsetDateTime expiresAt;

    @Column(name = "used_at")
    private OffsetDateTime usedAt;

    @Column(name = "created_at", nullable = false, updatable = false)
    private OffsetDateTime createdAt = OffsetDateTime.now();
}
