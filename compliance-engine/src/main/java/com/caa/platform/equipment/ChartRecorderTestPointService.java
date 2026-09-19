package com.caa.platform.equipment;

import com.caa.platform.certification.LiveTestingService;
import com.caa.platform.session.Session;
import com.caa.platform.session.SessionRepository;
import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.nio.charset.StandardCharsets;
import java.security.MessageDigest;
import java.util.HexFormat;

@Service
public class ChartRecorderTestPointService {
    private final ObjectMapper mapper;
    private final SessionRepository sessions;
    private final LiveTestingService liveTesting;
    private final ChartRecorderTestPointTransmissionRepository transmissions;

    public ChartRecorderTestPointService(ObjectMapper mapper, SessionRepository sessions,
            LiveTestingService liveTesting, ChartRecorderTestPointTransmissionRepository transmissions) {
        this.mapper = mapper;
        this.sessions = sessions;
        this.liveTesting = liveTesting;
        this.transmissions = transmissions;
    }

    public record Receipt(String transmissionId, boolean duplicate) {}

    @Transactional
    public Receipt accept(String payload) {
        try {
            JsonNode json = mapper.readTree(payload);
            String transmissionId = requiredText(json, "UniqueIdentifier");
            if (!"Test Point".equals(requiredText(json, "point_type"))) {
                throw new IllegalArgumentException("Only Test Points may be transmitted.");
            }
            long sessionId = Long.parseLong(requiredText(json, "school_public_identifier"));
            short pointNumber = number(json, "point_number");
            short opacity = (short) Math.round(json.path("percent_value").asDouble(Double.NaN));
            if (pointNumber < 1 || pointNumber > 50 || opacity < 0 || opacity > 100) {
                throw new IllegalArgumentException("Point number or opacity is outside its valid range.");
            }
            String hash = sha256(payload);
            var existing = transmissions.findByTransmissionId(transmissionId);
            if (existing.isPresent()) {
                if (!existing.get().getPayloadSha256().equals(hash)) {
                    throw new IllegalStateException("Transmission ID was reused with different data.");
                }
                return new Receipt(transmissionId, true);
            }
            Session session = sessions.findById(sessionId)
                    .orElseThrow(() -> new IllegalArgumentException("Smoke School session was not found."));
            if (session.getLiveTestRevisitPointNumber() != null) {
                throw new IllegalStateException(
                        "A revisit reuses the original true value; no tablet value is needed."
                );
            }
            Short activePoint = session.getLiveTestPointNumber();
            if (activePoint == null || activePoint.shortValue() != pointNumber) {
                throw new IllegalStateException("The transmitted point is not the active live-test point.");
            }
            if (session.getLiveTestTrueOpacity() != null) {
                throw new IllegalStateException("The active point already has a tablet value.");
            }
            liveTesting.recordTrueValue(session, opacity);
            transmissions.save(new ChartRecorderTestPointTransmission(
                    transmissionId, sessionId, pointNumber, opacity, hash));
            return new Receipt(transmissionId, false);
        } catch (RuntimeException exception) {
            throw exception;
        } catch (Exception exception) {
            throw new IllegalArgumentException("Marked-point payload is invalid.", exception);
        }
    }

    private static String requiredText(JsonNode json, String field) {
        String value = json.path(field).asText("").trim();
        if (value.isEmpty()) throw new IllegalArgumentException("Missing field: " + field);
        return value;
    }

    private static short number(JsonNode json, String field) {
        if (!json.path(field).canConvertToInt()) throw new IllegalArgumentException("Invalid field: " + field);
        return (short) json.path(field).asInt();
    }

    private static String sha256(String value) throws Exception {
        return HexFormat.of().formatHex(MessageDigest.getInstance("SHA-256")
                .digest(value.getBytes(StandardCharsets.UTF_8)));
    }
}
