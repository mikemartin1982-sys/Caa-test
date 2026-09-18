package com.caa.platform.client;

import org.springframework.data.jpa.repository.JpaRepository;

import java.util.Optional;

public interface ClientPasswordResetTokenRepository extends JpaRepository<ClientPasswordResetToken, Long> {
    Optional<ClientPasswordResetToken> findByTokenHash(String tokenHash);
}
