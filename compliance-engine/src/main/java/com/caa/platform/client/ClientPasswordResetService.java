package com.caa.platform.client;

import com.caa.platform.common.EmailService;
import com.caa.platform.common.PasswordResetTokenUtil;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.time.OffsetDateTime;
import java.util.Optional;

/**
 * Michael, 2026-08-31 -- Password Reset feature. Mirrors
 * StaffPasswordResetService -- kept as two separate implementations
 * per this project's established pattern for Staff/Client, not one
 * shared/polymorphic service.
 *
 * Confirmed with Michael: whether Client.email exists at all isn't a
 * question here the way it is for Staff -- email IS this account's
 * login identifier itself (see ClientApiUserProvider), so a Client
 * record existing at all means it has one. No NO_EMAIL_ON_FILE case;
 * always the same generic response either way (see requestReset()),
 * since email is real, sensitive, public-facing PII on this side --
 * unlike the internal, admin-only Staff side, protecting against
 * confirming whether a given email has an account here is worth
 * doing unconditionally, not case-by-case.
 */
@Service
public class ClientPasswordResetService {

    private static final int EXPIRY_HOURS = 1;

    private final ClientRepository clientRepository;
    private final ClientPasswordResetTokenRepository tokenRepository;
    private final PasswordEncoder passwordEncoder;
    private final EmailService emailService;
    private final String appBaseUrl;

    public ClientPasswordResetService(ClientRepository clientRepository,
                                       ClientPasswordResetTokenRepository tokenRepository,
                                       PasswordEncoder passwordEncoder, EmailService emailService,
                                       @Value("${app.frontend-base-url}") String appBaseUrl) {
        this.clientRepository = clientRepository;
        this.tokenRepository = tokenRepository;
        this.passwordEncoder = passwordEncoder;
        this.emailService = emailService;
        this.appBaseUrl = appBaseUrl;
    }

    /**
     * No result type to branch on, unlike StaffPasswordResetService --
     * confirmed with Michael this side always returns the same,
     * generic outcome to the caller regardless of whether the email
     * actually matches an account. Still only actually sends
     * something, and only issues a token, when a real match exists.
     */
    @Transactional
    public void requestReset(String email) {
        Optional<Client> maybeClient = clientRepository.findByEmail(email);
        if (maybeClient.isEmpty()) {
            return;
        }
        Client client = maybeClient.get();

        // Michael, 2026-08-31 -- same reasoning as
        // StaffPasswordResetService: invalidate any still-valid prior
        // tokens before issuing a new one, so only the most recent
        // reset link is ever usable.
        java.util.List<ClientPasswordResetToken> priorTokens = tokenRepository.findAll().stream()
                .filter(t -> t.getClient().getId().equals(client.getId()) && t.getUsedAt() == null)
                .toList();
        OffsetDateTime now = OffsetDateTime.now();
        priorTokens.forEach(t -> t.setUsedAt(now));
        tokenRepository.saveAll(priorTokens);

        String rawToken = PasswordResetTokenUtil.generateRawToken();
        ClientPasswordResetToken token = new ClientPasswordResetToken();
        token.setClient(client);
        token.setTokenHash(PasswordResetTokenUtil.hashToken(rawToken));
        token.setExpiresAt(now.plusHours(EXPIRY_HOURS));
        tokenRepository.save(token);

        String resetLink = appBaseUrl + "/account/password/reset?token=" + rawToken;
        // Michael, 2026-08-31 -- resolved: our platform's only path to
        // a Client record is self-serve registration (always sets a
        // real password immediately) -- the only way passwordHash is
        // ever null here is a client created the old, staff-entered
        // way, before that existed. So null genuinely means "setting a
        // password for the first time," not a real reset, and the
        // wording below branches accordingly.
        boolean settingForFirstTime = client.getPasswordHash() == null;
        String subject = settingForFirstTime ? "Set Your CAA Account Password" : "CAA Account Password Reset";
        String action = settingForFirstTime ? "set" : "reset";
        emailService.send(client.getEmail(), subject,
                "A request was made to " + action + " the password for your CAA account (" + client.getEmail() + ").\n\n"
                        + "You can " + action + " your password here: " + resetLink + "\n\n"
                        + "This link expires in " + EXPIRY_HOURS + " hour and can only be used once. "
                        + "If you didn't request this, you can safely ignore this email.");
    }

    /** Null return (rather than throwing) signals an invalid, expired, or already-used token -- all collapsed to the same outcome. */
    @Transactional
    public Boolean completeReset(String rawToken, String newPassword) {
        String tokenHash = PasswordResetTokenUtil.hashToken(rawToken);
        Optional<ClientPasswordResetToken> maybeToken = tokenRepository.findByTokenHash(tokenHash);
        if (maybeToken.isEmpty()) {
            return false;
        }
        ClientPasswordResetToken token = maybeToken.get();
        if (token.getUsedAt() != null || token.getExpiresAt().isBefore(OffsetDateTime.now())) {
            return false;
        }

        Client client = token.getClient();
        client.setPasswordHash(passwordEncoder.encode(newPassword));
        clientRepository.save(client);

        token.setUsedAt(OffsetDateTime.now());
        tokenRepository.save(token);

        return true;
    }
}
