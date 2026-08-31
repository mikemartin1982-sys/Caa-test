package com.caa.platform.session;

import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;

public interface SessionCommentRepository extends JpaRepository<SessionComment, Long> {
    List<SessionComment> findBySessionIdOrderByCreatedAtUtc(Long sessionId);
}
