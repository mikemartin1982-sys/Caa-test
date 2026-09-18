CREATE TABLE chart_recorder_test_point_transmissions (
    id BIGSERIAL PRIMARY KEY,
    transmission_id VARCHAR(100) NOT NULL UNIQUE,
    session_id BIGINT NOT NULL REFERENCES sessions(id),
    point_number SMALLINT NOT NULL CHECK (point_number BETWEEN 1 AND 50),
    true_opacity SMALLINT NOT NULL CHECK (true_opacity BETWEEN 0 AND 100),
    payload_sha256 CHAR(64) NOT NULL,
    received_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_chart_recorder_test_points_session
    ON chart_recorder_test_point_transmissions(session_id, point_number);
