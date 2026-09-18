package com.caa.platform.equipment;

import org.springframework.data.jpa.repository.JpaRepository;
import java.util.Optional;

public interface ChartRecorderTestPointTransmissionRepository
        extends JpaRepository<ChartRecorderTestPointTransmission, Long> {
    Optional<ChartRecorderTestPointTransmission> findByTransmissionId(String transmissionId);
}
