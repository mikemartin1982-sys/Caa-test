package com.caa.platform.equipment;

import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;

public interface ComplianceFlagRepository extends JpaRepository<ComplianceFlag, Long> {
    List<ComplianceFlag> findByStatus(ComplianceFlagStatus status);
    List<ComplianceFlag> findByTestingSystemId(Long testingSystemId);
}
