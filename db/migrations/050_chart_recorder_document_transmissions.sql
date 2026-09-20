-- Durable, idempotent Chart Recorder v1002 document uploads.
-- Existing historical emailed-ZIP rows remain valid with null transmission metadata.
ALTER TABLE chart_recorder_exports
    ADD COLUMN transmission_id VARCHAR(100),
    ADD COLUMN document_id VARCHAR(100),
    ADD COLUMN payload_sha256 CHAR(64),
    ADD COLUMN measurement_count INTEGER,
    ADD COLUMN interrupted BOOLEAN,
    ADD COLUMN original_filename VARCHAR(255);

CREATE UNIQUE INDEX uq_chart_recorder_exports_transmission
    ON chart_recorder_exports (transmission_id)
    WHERE transmission_id IS NOT NULL;

CREATE UNIQUE INDEX uq_chart_recorder_exports_session_payload
    ON chart_recorder_exports (session_id, payload_sha256)
    WHERE payload_sha256 IS NOT NULL;

ALTER TABLE chart_recorder_exports
    ADD CONSTRAINT chk_chart_recorder_export_measurements
    CHECK (measurement_count IS NULL OR measurement_count >= 0);
