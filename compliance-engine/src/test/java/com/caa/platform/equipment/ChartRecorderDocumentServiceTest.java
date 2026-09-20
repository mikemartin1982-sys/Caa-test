package com.caa.platform.equipment;

import com.caa.platform.session.Session;
import com.caa.platform.session.SessionRepository;
import com.fasterxml.jackson.databind.ObjectMapper;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.Test;
import org.junit.jupiter.api.io.TempDir;
import org.junit.jupiter.api.extension.ExtendWith;
import org.mockito.ArgumentCaptor;
import org.mockito.Mock;
import org.mockito.junit.jupiter.MockitoExtension;
import org.springframework.test.util.ReflectionTestUtils;

import java.nio.charset.StandardCharsets;
import java.nio.file.Files;
import java.nio.file.Path;
import java.util.Optional;

import static org.junit.jupiter.api.Assertions.*;
import static org.mockito.Mockito.*;

@ExtendWith(MockitoExtension.class)
class ChartRecorderDocumentServiceTest {
    private static final long SESSION_ID = 14L;

    @Mock private SessionRepository sessions;
    @Mock private ChartRecorderExportRepository exports;
    @TempDir Path storage;

    private ChartRecorderDocumentService service;
    private Session session;

    @BeforeEach
    void setUp() {
        service = new ChartRecorderDocumentService(new ObjectMapper(), sessions, exports);
        ReflectionTestUtils.setField(service, "outputDir", storage.toString());
        session = new Session();
        session.setId(SESSION_ID);
    }

    @Test
    void storesExactV1002BytesAndMetadata() throws Exception {
        byte[] payload = document("capture-1", 2);
        when(sessions.findById(SESSION_ID)).thenReturn(Optional.of(session));
        when(exports.findByTransmissionId("tx-1")).thenReturn(Optional.empty());
        when(exports.findBySessionIdAndPayloadSha256(eq(SESSION_ID), anyString()))
                .thenReturn(Optional.empty());
        when(exports.save(any())).thenAnswer(invocation -> invocation.getArgument(0));

        var receipt = service.accept(
                SESSION_ID, "tx-1", false, "capture-1.json", payload);

        assertFalse(receipt.duplicate());
        assertEquals("capture-1", receipt.documentId());
        assertEquals(2, receipt.measurementCount());
        ArgumentCaptor<ChartRecorderExport> saved = ArgumentCaptor.forClass(ChartRecorderExport.class);
        verify(exports).save(saved.capture());
        assertArrayEquals(payload, Files.readAllBytes(Path.of(saved.getValue().getZipFileReference())));
        assertEquals("tx-1", saved.getValue().getTransmissionId());
        assertEquals("capture-1.json", saved.getValue().getOriginalFilename());
    }

    @Test
    void identicalTransmissionIsAcknowledgedWithoutWritingAgain() throws Exception {
        byte[] payload = document("capture-2", 1);
        ChartRecorderExport prior = new ChartRecorderExport();
        prior.setSession(session);
        prior.setTransmissionId("tx-retry");
        prior.setDocumentId("capture-2");
        prior.setPayloadSha256(java.util.HexFormat.of().formatHex(
                java.security.MessageDigest.getInstance("SHA-256").digest(payload)));
        prior.setMeasurementCount(1);
        prior.setInterrupted(false);
        when(exports.findByTransmissionId("tx-retry")).thenReturn(Optional.of(prior));

        var receipt = service.accept(
                SESSION_ID, "tx-retry", false, "capture-2.json", payload);

        assertTrue(receipt.duplicate());
        verifyNoInteractions(sessions);
        verify(exports, never()).save(any());
        assertEquals(0, Files.list(storage).count());
    }

    @Test
    void rejectsUnsupportedDocumentBeforeStorage() {
        byte[] payload = "{\"FileHeader\":{\"FileVersion\":999}}"
                .getBytes(StandardCharsets.UTF_8);
        when(exports.findByTransmissionId("tx-bad")).thenReturn(Optional.empty());

        IllegalArgumentException error = assertThrows(IllegalArgumentException.class,
                () -> service.accept(SESSION_ID, "tx-bad", false, null, payload));

        assertEquals("Only Chart Recorder v1002 documents are accepted.", error.getMessage());
        verifyNoInteractions(sessions);
        verify(exports, never()).save(any());
    }

    private static byte[] document(String id, int measurements) {
        StringBuilder values = new StringBuilder();
        for (int index = 0; index < measurements; index++) {
            if (index > 0) values.append(',');
            values.append("{\"RawValue\":1.0,\"DisplayValue\":50.0}");
        }
        return ("{\"FileHeader\":{\"FileVersion\":1002},"
                + "\"Session\":{\"SessionHeader\":{\"Id\":\"" + id + "\"},"
                + "\"DataRuns\":[{\"DataSets\":[{\"OpacityMeasurements\":["
                + values + "]}]}]}}")
                .getBytes(StandardCharsets.UTF_8);
    }
}
