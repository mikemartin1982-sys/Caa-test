package com.caa.platform.equipment;

import com.caa.platform.certification.LiveTestingService;
import com.caa.platform.session.Session;
import com.caa.platform.session.SessionRepository;
import com.fasterxml.jackson.databind.ObjectMapper;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.Test;
import org.junit.jupiter.api.extension.ExtendWith;
import org.mockito.ArgumentCaptor;
import org.mockito.Mock;
import org.mockito.junit.jupiter.MockitoExtension;

import java.nio.charset.StandardCharsets;
import java.security.MessageDigest;
import java.util.HexFormat;
import java.util.Optional;

import static org.junit.jupiter.api.Assertions.*;
import static org.mockito.Mockito.*;

@ExtendWith(MockitoExtension.class)
class ChartRecorderTestPointServiceTest {
    private static final long SESSION_ID = 14L;

    @Mock private SessionRepository sessions;
    @Mock private LiveTestingService liveTesting;
    @Mock private ChartRecorderTestPointTransmissionRepository transmissions;

    private ChartRecorderTestPointService service;
    private Session session;

    @BeforeEach
    void setUp() {
        service = new ChartRecorderTestPointService(
                new ObjectMapper(), sessions, liveTesting, transmissions);
        session = new Session();
        session.setLiveTestActive(true);
        session.setLiveTestPointNumber((short) 6);
        when(transmissions.findByTransmissionId(anyString())).thenReturn(Optional.empty());
    }

    @Test
    void acceptsFirstValueForActivePoint() {
        when(sessions.findById(SESSION_ID)).thenReturn(Optional.of(session));

        var receipt = service.accept(payload("tx-1", 6, 50));

        assertEquals("tx-1", receipt.transmissionId());
        assertFalse(receipt.duplicate());
        verify(liveTesting).recordTrueValue(session, (short) 50);

        ArgumentCaptor<ChartRecorderTestPointTransmission> saved =
                ArgumentCaptor.forClass(ChartRecorderTestPointTransmission.class);
        verify(transmissions).save(saved.capture());
        assertEquals(SESSION_ID, saved.getValue().getSessionId());
        assertEquals((short) 6, saved.getValue().getPointNumber());
        assertEquals((short) 50, saved.getValue().getTrueOpacity());
    }

    @Test
    void treatsExactRetryAsAlreadyReceived() throws Exception {
        String payload = payload("tx-retry", 6, 50);
        var existing = new ChartRecorderTestPointTransmission(
                "tx-retry", SESSION_ID, (short) 6, (short) 50, sha256(payload));
        when(transmissions.findByTransmissionId("tx-retry")).thenReturn(Optional.of(existing));

        var receipt = service.accept(payload);

        assertTrue(receipt.duplicate());
        verifyNoInteractions(sessions, liveTesting);
        verify(transmissions, never()).save(any());
    }

    @Test
    void rejectsTransmissionIdReusedWithDifferentData() throws Exception {
        String original = payload("tx-reused", 6, 50);
        var existing = new ChartRecorderTestPointTransmission(
                "tx-reused", SESSION_ID, (short) 6, (short) 50, sha256(original));
        when(transmissions.findByTransmissionId("tx-reused")).thenReturn(Optional.of(existing));

        var error = assertThrows(IllegalStateException.class,
                () -> service.accept(payload("tx-reused", 6, 55)));

        assertEquals("Transmission ID was reused with different data.", error.getMessage());
        verifyNoInteractions(sessions, liveTesting);
        verify(transmissions, never()).save(any());
    }

    @Test
    void rejectsPointThatIsNotCurrentlyActive() {
        when(sessions.findById(SESSION_ID)).thenReturn(Optional.of(session));

        var error = assertThrows(IllegalStateException.class,
                () -> service.accept(payload("tx-stale", 5, 50)));

        assertEquals("The transmitted point is not the active live-test point.", error.getMessage());
        verifyNoInteractions(liveTesting);
        verify(transmissions, never()).save(any());
    }

    @Test
    void rejectsSecondValueForActivePoint() {
        session.setLiveTestTrueOpacity((short) 45);
        when(sessions.findById(SESSION_ID)).thenReturn(Optional.of(session));

        var error = assertThrows(IllegalStateException.class,
                () -> service.accept(payload("tx-overwrite", 6, 50)));

        assertEquals("The active point already has a tablet value.", error.getMessage());
        verifyNoInteractions(liveTesting);
        verify(transmissions, never()).save(any());
    }

    @Test
    void rejectsNewTabletValueWhileRevisitIsActive() {
        session.setLiveTestRevisitPointNumber((short) 2);
        when(sessions.findById(SESSION_ID)).thenReturn(Optional.of(session));

        var error = assertThrows(IllegalStateException.class,
                () -> service.accept(payload("tx-revisit", 6, 50)));

        assertEquals("A revisit reuses the original true value; no tablet value is needed.",
                error.getMessage());
        verifyNoInteractions(liveTesting);
        verify(transmissions, never()).save(any());
    }

    private static String payload(String transmissionId, int pointNumber, int opacity) {
        return """
                {"UniqueIdentifier":"%s","point_type":"Test Point",\
                "school_public_identifier":"%d","point_number":%d,"percent_value":%d}
                """.formatted(transmissionId, SESSION_ID, pointNumber, opacity);
    }

    private static String sha256(String value) throws Exception {
        return HexFormat.of().formatHex(MessageDigest.getInstance("SHA-256")
                .digest(value.getBytes(StandardCharsets.UTF_8)));
    }
}
