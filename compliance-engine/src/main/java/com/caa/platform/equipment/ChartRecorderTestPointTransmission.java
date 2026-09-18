package com.caa.platform.equipment;

import jakarta.persistence.*;
import org.hibernate.annotations.JdbcTypeCode;
import org.hibernate.type.SqlTypes;
import java.time.OffsetDateTime;

@Entity
@Table(name = "chart_recorder_test_point_transmissions")
public class ChartRecorderTestPointTransmission {
    @Id @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;
    @Column(name = "transmission_id", nullable = false, unique = true, length = 100)
    private String transmissionId;
    @Column(name = "session_id", nullable = false)
    private Long sessionId;
    @Column(name = "point_number", nullable = false)
    private Short pointNumber;
    @Column(name = "true_opacity", nullable = false)
    private Short trueOpacity;
    @Column(name = "payload_sha256", nullable = false, columnDefinition = "char(64)")
    @JdbcTypeCode(SqlTypes.CHAR)
    private String payloadSha256;
    @Column(name = "received_at", nullable = false)
    private OffsetDateTime receivedAt;

    protected ChartRecorderTestPointTransmission() {}

    public ChartRecorderTestPointTransmission(String transmissionId, Long sessionId,
            short pointNumber, short trueOpacity, String payloadSha256) {
        this.transmissionId = transmissionId;
        this.sessionId = sessionId;
        this.pointNumber = pointNumber;
        this.trueOpacity = trueOpacity;
        this.payloadSha256 = payloadSha256;
        this.receivedAt = OffsetDateTime.now();
    }

    public String getTransmissionId() { return transmissionId; }
    public Long getSessionId() { return sessionId; }
    public Short getPointNumber() { return pointNumber; }
    public Short getTrueOpacity() { return trueOpacity; }
    public String getPayloadSha256() { return payloadSha256; }
}
