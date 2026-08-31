-- ============================================================================
-- Migration 032: QuickBooks Online connection (token storage)
-- Reference: Michael, 2026-08-25 -- QBO integration, layer 2.
--
-- One company-wide connection, not per-client -- a singleton in
-- practice (the application enforces only one active row; the table
-- itself doesn't hard-constrain to one row, since a disconnect/
-- reconnect cycle is expected to insert a new row rather than update
-- in place, preserving history of past connections rather than
-- silently overwriting them).
--
-- access_token/refresh_token are stored ENCRYPTED (application-level,
-- via a JPA AttributeConverter -- see QboTokenEncryptionConverter) --
-- confirmed as a hard requirement from Intuit's own integration
-- guidance: OAuth tokens must never be stored in plain text.
-- ============================================================================

CREATE TABLE qbo_connections
(
    id                          BIGSERIAL PRIMARY KEY,
    realm_id                    VARCHAR(50)  NOT NULL,
    access_token                TEXT         NOT NULL,
    refresh_token               TEXT         NOT NULL,
    access_token_expires_at     TIMESTAMPTZ  NOT NULL,
    refresh_token_expires_at    TIMESTAMPTZ  NOT NULL,
    environment                 VARCHAR(10)  NOT NULL DEFAULT 'SANDBOX',
    active                      BOOLEAN      NOT NULL DEFAULT true,
    connected_by_staff_id       BIGINT REFERENCES staff_users(id),
    created_at                  TIMESTAMPTZ  NOT NULL DEFAULT now(),
    updated_at                  TIMESTAMPTZ  NOT NULL DEFAULT now()
);

-- Michael, 2026-08-25 -- at most one ACTIVE connection at a time,
-- enforced at the DB level (a partial unique index), not just trusted
-- to application code -- matching this project's established
-- preference for real constraints over app-level-only assumptions.
CREATE UNIQUE INDEX qbo_connections_one_active_idx ON qbo_connections (active) WHERE active = true;
