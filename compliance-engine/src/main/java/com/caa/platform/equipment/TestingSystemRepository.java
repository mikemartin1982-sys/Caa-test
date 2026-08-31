package com.caa.platform.equipment;

import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;

public interface TestingSystemRepository extends JpaRepository<TestingSystem, Long> {
    List<TestingSystem> findByTrailerId(Long trailerId);
}
