package com.caa.platform.staff;

import org.springframework.data.jpa.repository.JpaRepository;

import java.util.Optional;

public interface StaffPasswordResetTokenRepository extends JpaRepository<StaffPasswordResetToken, Long> {
    Optional<StaffPasswordResetToken> findByTokenHash(String tokenHash);
}
