-- ============================================================================
-- Migration 011: GENERAL Section Remaining Fields
-- Reference: real DIBs session-details source review, 2026-08-15/16.
--
-- Copy-forward behavior (Section 4c "Copy Forward 6 Months") confirmed
-- directly from DIBs' own "- Is/Not copied on session-copy -" labels:
--   NOT copied: not_need_copy(_why), qbo_class_ref_id,
--               session_info_verified(_by/_at), session_log
--   IS copied:  session_info_owner, advertise_semi_priv_as_public,
--               staggered_arrival_times, admin_comments (Comments2),
--               and the full notified-clients list
-- ============================================================================

ALTER TABLE sessions ADD COLUMN not_need_copy BOOLEAN NOT NULL DEFAULT false;
ALTER TABLE sessions ADD COLUMN not_need_copy_why TEXT;

-- QBO ClassRef ID -- auto-generated per session by QuickBooks, stored
-- once assigned (Section 7, still blocked on QBO credentials -- this is
-- just the storage slot, no generation logic yet). The display string
-- ("All:AZ:KIN260819" in DIBs) is computed from session data, not stored
-- redundantly here.
ALTER TABLE sessions ADD COLUMN qbo_class_ref_id VARCHAR(50);

ALTER TABLE sessions ADD COLUMN session_info_verified BOOLEAN NOT NULL DEFAULT false;
ALTER TABLE sessions ADD COLUMN session_info_verified_by BIGINT REFERENCES staff_users(id);
ALTER TABLE sessions ADD COLUMN session_info_verified_at TIMESTAMPTZ;
ALTER TABLE sessions ADD COLUMN session_info_owner BIGINT REFERENCES staff_users(id);

-- Semi-Private only in practice (enforced at the application layer, same
-- pattern as other school-type-specific rules -- see db/README.md).
ALTER TABLE sessions ADD COLUMN advertise_semi_priv_as_public BOOLEAN NOT NULL DEFAULT false;

ALTER TABLE sessions ADD COLUMN staggered_arrival_times BOOLEAN NOT NULL DEFAULT false;

-- Session Log: explicitly NOT copied on session-copy, and does not
-- appear on any calendar -- internal staff notes only.
ALTER TABLE sessions ADD COLUMN session_log TEXT;

-- "Comments2" in DIBs -- admin-calendar-only, distinct from the existing
-- public_session_notes column (DIBs' "Comments," which appears on the
-- public calendar and already exists in this schema).
ALTER TABLE sessions ADD COLUMN admin_comments TEXT;

CREATE INDEX idx_sessions_session_info_owner ON sessions (session_info_owner);

-- "Clients to be Notified" -- a proper relation instead of DIBs' comma-
-- separated text field, so the eventual 60-day-before-session
-- notification-email logic can query it reliably rather than parsing a
-- free-text string.
CREATE TABLE session_notified_clients (
    id           BIGSERIAL PRIMARY KEY,
    session_id   BIGINT NOT NULL REFERENCES sessions(id),
    client_id    BIGINT NOT NULL REFERENCES clients(id),
    created_at   TIMESTAMPTZ NOT NULL DEFAULT now(),
    UNIQUE (session_id, client_id)
);

CREATE INDEX idx_session_notified_clients_session ON session_notified_clients (session_id);
CREATE INDEX idx_session_notified_clients_client ON session_notified_clients (client_id);
