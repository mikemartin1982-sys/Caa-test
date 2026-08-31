package com.caa.platform.integration.qbo;

import org.springframework.data.jpa.repository.JpaRepository;

import java.util.Optional;

public interface QboConnectionRepository extends JpaRepository<QboConnection, Long> {

    /** Michael, 2026-08-25 -- the one, current connection -- matches the DB's own partial unique index (at most one active=true row at a time). */
    Optional<QboConnection> findByActiveTrue();
}
