package com.caa.platform.session;

import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;
import java.util.Optional;

public interface SessionAuthorizedClientRepository extends JpaRepository<SessionAuthorizedClient, Long> {
    List<SessionAuthorizedClient> findBySessionId(Long sessionId);
    Optional<SessionAuthorizedClient> findBySessionIdAndIsHostTrue(Long sessionId);
    boolean existsBySessionIdAndClientId(Long sessionId, Long clientId);
}
