package com.caa.platform.integration.qbo;

import jakarta.persistence.AttributeConverter;
import jakarta.persistence.Converter;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.stereotype.Component;

import javax.crypto.Cipher;
import javax.crypto.spec.GCMParameterSpec;
import javax.crypto.spec.SecretKeySpec;
import java.nio.charset.StandardCharsets;
import java.security.SecureRandom;
import java.util.Base64;

/**
 * Michael, 2026-08-25 -- QBO integration, layer 2. Encrypts
 * access_token/refresh_token at rest (AES-256-GCM) -- a hard
 * requirement confirmed against Intuit's own integration guidance:
 * OAuth tokens must never be stored in plain text. @Component +
 * @Converter (not autoApply -- explicitly attached per-field via
 * @Convert on QboConnection, not silently applied to every String
 * column in the app) makes this a Spring-managed bean, so @Value
 * injection of the encryption key works the same way every other
 * secret in this project is sourced (see BrevoSyncService's own
 * @Value pattern).
 *
 * Key comes from app.qbo.token-encryption-key (QBO_TOKEN_ENCRYPTION_KEY
 * env var) -- a 32-byte, base64-encoded AES-256 key, generated via
 * `openssl rand -base64 32`. Same category of secret as the QBO client
 * ID/secret themselves: never in code, never in chat, env var only.
 *
 * Each encrypted value is stored as base64(IV || ciphertext) -- a
 * fresh, random 12-byte IV per encryption (GCM's own requirement:
 * reusing an IV with the same key breaks its security guarantee
 * entirely), prepended so decryption can recover it without a
 * separate column.
 */
@Component
@Converter
public class QboTokenEncryptionConverter implements AttributeConverter<String, String> {

    private static final String ALGORITHM = "AES/GCM/NoPadding";
    private static final int GCM_IV_LENGTH_BYTES = 12;
    private static final int GCM_TAG_LENGTH_BITS = 128;

    @Value("${app.qbo.token-encryption-key:}")
    private String base64Key;

    @Override
    public String convertToDatabaseColumn(String plainToken) {
        if (plainToken == null) {
            return null;
        }
        try {
            byte[] iv = new byte[GCM_IV_LENGTH_BYTES];
            new SecureRandom().nextBytes(iv);

            Cipher cipher = Cipher.getInstance(ALGORITHM);
            cipher.init(Cipher.ENCRYPT_MODE, secretKey(), new GCMParameterSpec(GCM_TAG_LENGTH_BITS, iv));
            byte[] ciphertext = cipher.doFinal(plainToken.getBytes(StandardCharsets.UTF_8));

            byte[] combined = new byte[iv.length + ciphertext.length];
            System.arraycopy(iv, 0, combined, 0, iv.length);
            System.arraycopy(ciphertext, 0, combined, iv.length, ciphertext.length);
            return Base64.getEncoder().encodeToString(combined);
        } catch (Exception e) {
            throw new IllegalStateException("Failed to encrypt QBO token -- check app.qbo.token-encryption-key is set.", e);
        }
    }

    @Override
    public String convertToEntityAttribute(String storedValue) {
        if (storedValue == null) {
            return null;
        }
        try {
            byte[] combined = Base64.getDecoder().decode(storedValue);
            byte[] iv = new byte[GCM_IV_LENGTH_BYTES];
            byte[] ciphertext = new byte[combined.length - GCM_IV_LENGTH_BYTES];
            System.arraycopy(combined, 0, iv, 0, iv.length);
            System.arraycopy(combined, iv.length, ciphertext, 0, ciphertext.length);

            Cipher cipher = Cipher.getInstance(ALGORITHM);
            cipher.init(Cipher.DECRYPT_MODE, secretKey(), new GCMParameterSpec(GCM_TAG_LENGTH_BITS, iv));
            return new String(cipher.doFinal(ciphertext), StandardCharsets.UTF_8);
        } catch (Exception e) {
            throw new IllegalStateException("Failed to decrypt QBO token -- check app.qbo.token-encryption-key matches the key used to encrypt it.", e);
        }
    }

    private SecretKeySpec secretKey() {
        if (base64Key == null || base64Key.isBlank()) {
            throw new IllegalStateException("QBO_TOKEN_ENCRYPTION_KEY is not set -- required before any QBO connection can be stored.");
        }
        return new SecretKeySpec(Base64.getDecoder().decode(base64Key), "AES");
    }
}
