package com.caa.platform.session;

import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;

public interface SessionNotifiedClientRepository extends JpaRepository<SessionNotifiedClient, Long> {
    List<SessionNotifiedClient> findBySessionId(Long sessionId);
    boolean existsBySessionIdAndClientId(Long sessionId, Long clientId);
    void deleteBySessionIdAndClientId(Long sessionId, Long clientId);
}
