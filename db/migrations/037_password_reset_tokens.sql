-- ============================================================================
-- Migration 037: Password Reset -- Staff and Client
-- Reference: Michael, 2026-08-31.
--
-- Two separate, mirrored tables, not one shared table with a type
-- discriminator -- matches this project's own, already-established
-- pattern of keeping Staff and Client as parallel, mirrored
-- implementations rather than one polymorphic one (StaffApiUserProvider/
-- ClientApiUserProvider, StaffAuthController/ClientAuthController).
--
-- token_hash, never the raw token -- same principle as passwordHash
-- itself never storing a raw password. used_at (nullable) marks a token
-- consumed without deleting the row, so there's a real audit trail of
-- reset activity, not just a vanishing, unaccountable row.
-- ============================================================================

CREATE TABLE staff_password_reset_tokens (
    id BIGSERIAL PRIMARY KEY,
    staff_user_id BIGINT NOT NULL REFERENCES staff_users(id),
    token_hash VARCHAR(255) NOT NULL UNIQUE,
    expires_at TIMESTAMPTZ NOT NULL,
    used_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX idx_staff_password_reset_tokens_staff_user_id ON staff_password_reset_tokens (staff_user_id);

CREATE TABLE client_password_reset_tokens (
    id BIGSERIAL PRIMARY KEY,
    client_id BIGINT NOT NULL REFERENCES clients(id),
    token_hash VARCHAR(255) NOT NULL UNIQUE,
    expires_at TIMESTAMPTZ NOT NULL,
    used_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
CREATE INDEX idx_client_password_reset_tokens_client_id ON client_password_reset_tokens (client_id);
