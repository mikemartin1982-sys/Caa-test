package com.caa.platform.equipment;

import org.springframework.data.jpa.repository.JpaRepository;

import java.util.Optional;

public interface ChartRecorderExportRepository extends JpaRepository<ChartRecorderExport, Long> {
    Optional<ChartRecorderExport> findByTransmissionId(String transmissionId);
    Optional<ChartRecorderExport> findBySessionIdAndPayloadSha256(Long sessionId, String payloadSha256);
}
