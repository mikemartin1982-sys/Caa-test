package com.caa.platform.equipment;

import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;

public interface MaintenanceEventRepository extends JpaRepository<MaintenanceEvent, Long> {
    List<MaintenanceEvent> findByTestingSystemIdOrderByEventDateDesc(Long testingSystemId);
}
