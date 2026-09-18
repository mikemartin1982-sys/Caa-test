package com.caa.platform.common;

import java.security.MessageDigest;
import java.security.NoSuchAlgorithmException;
import java.security.SecureRandom;
import java.util.Base64;

/**
 * Michael, 2026-08-31 -- Password Reset feature. Shared between
 * StaffPasswordResetService and ClientPasswordResetService -- the only
 * part of that feature that's genuinely, mechanically identical between
 * Staff and Client (generate random bytes, hash them), unlike the rest
 * of the feature, which stays two separate, mirrored implementations
 * per this project's established pattern (StaffApiUserProvider/
 * ClientApiUserProvider).
 *
 * SHA-256, not BCrypt -- BCrypt is deliberately slow, built to resist
 * brute-forcing a low-entropy, human-chosen password. A reset token is
 * already high-entropy (32 random bytes, never human-chosen), so it
 * doesn't need that; a fast, standard cryptographic hash is the
 * correct, standard choice here -- the same approach Laravel's own
 * built-in password-reset feature uses.
 */
public final class PasswordResetTokenUtil {

    private static final SecureRandom RANDOM = new SecureRandom();

    private PasswordResetTokenUtil() {
    }

    /** The raw token -- this is what goes in the emailed reset link, never stored anywhere. */
    public static String generateRawToken() {
        byte[] bytes = new byte[32];
        RANDOM.nextBytes(bytes);
        return Base64.getUrlEncoder().withoutPadding().encodeToString(bytes);
    }

    /** What actually gets stored -- the raw token is never persisted, matching passwordHash's own never-store-raw principle. */
    public static String hashToken(String rawToken) {
        try {
            MessageDigest digest = MessageDigest.getInstance("SHA-256");
            byte[] hash = digest.digest(rawToken.getBytes(java.nio.charset.StandardCharsets.UTF_8));
            return Base64.getEncoder().encodeToString(hash);
        } catch (NoSuchAlgorithmException e) {
            // SHA-256 is a standard JDK algorithm, guaranteed present -- this can't actually happen.
            throw new IllegalStateException("SHA-256 unavailable", e);
        }
    }
}
