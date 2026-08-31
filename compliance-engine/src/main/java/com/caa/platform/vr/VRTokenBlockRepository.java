package com.caa.platform.vr;

import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;

public interface VRTokenBlockRepository extends JpaRepository<VRTokenBlock, Long> {
    List<VRTokenBlock> findByClientId(Long clientId);
}
