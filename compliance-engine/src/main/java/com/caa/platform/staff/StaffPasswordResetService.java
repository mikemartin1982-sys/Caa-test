package com.caa.platform.staff;

import com.caa.platform.common.EmailService;
import com.caa.platform.common.PasswordResetTokenUtil;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.time.OffsetDateTime;
import java.util.Optional;

/**
 * Michael, 2026-08-31 -- Password Reset feature. This is the real,
 * authenticated reset flow StaffUserController.setInitialPassword()'s
 * own Javadoc explicitly flagged as "not yet built in this pass" --
 * that endpoint stays bootstrap-only (a StaffUser with no password at
 * all), never a general reset mechanism, exactly as it already said it
 * deliberately wouldn't become.
 *
 * Mirrors ClientPasswordResetService -- kept as two separate,
 * mirrored implementations rather than one shared/polymorphic service,
 * matching this project's own established pattern for Staff/Client
 * (StaffApiUserProvider/ClientApiUserProvider, etc.). Only the raw
 * token generation/hashing (PasswordResetTokenUtil) and the actual
 * mail transport (EmailService) are genuinely shared, mechanical, and
 * type-agnostic.
 */
@Service
public class StaffPasswordResetService {

    private static final int EXPIRY_HOURS = 1;

    private final StaffUserRepository staffUserRepository;
    private final StaffPasswordResetTokenRepository tokenRepository;
    private final PasswordEncoder passwordEncoder;
    private final EmailService emailService;
    private final String appBaseUrl;

    public StaffPasswordResetService(StaffUserRepository staffUserRepository,
                                      StaffPasswordResetTokenRepository tokenRepository,
                                      PasswordEncoder passwordEncoder, EmailService emailService,
                                      @Value("${app.frontend-base-url}") String appBaseUrl) {
        this.staffUserRepository = staffUserRepository;
        this.tokenRepository = tokenRepository;
        this.passwordEncoder = passwordEncoder;
        this.emailService = emailService;
        this.appBaseUrl = appBaseUrl;
    }

    public enum RequestResult { SENT, NO_SUCH_USER, NO_EMAIL_ON_FILE }

    /**
     * Michael, 2026-08-31 -- confirmed with Michael: an internal,
     * admin-only tool, not a public-facing system with untrusted
     * users -- NO_SUCH_USER and NO_EMAIL_ON_FILE are both real,
     * distinct outcomes returned here. It's the controller's job to
     * decide how to present them (the same generic response for
     * NO_SUCH_USER as for SENT, but NO_EMAIL_ON_FILE shown plainly,
     * per Michael's own explicit request), not this service's.
     */
    @Transactional
    public RequestResult requestReset(String username) {
        // Michael, 2026-08-31 -- scaffold-scale in-memory filter, same
        // established pattern (and same explicit caveat) as
        // ClientController.search()'s own findAll()+filter -- swap for
        // a real, targeted query before production.
        Optional<StaffUser> maybeStaff = staffUserRepository.findAll().stream()
                .filter(s -> username.equals(s.getUsername()))
                .findFirst();
        if (maybeStaff.isEmpty()) {
            return RequestResult.NO_SUCH_USER;
        }
        StaffUser staff = maybeStaff.get();
        if (staff.getEmail() == null || staff.getEmail().isBlank()) {
            return RequestResult.NO_EMAIL_ON_FILE;
        }

        // Michael, 2026-08-31 -- invalidate any still-valid prior
        // tokens for this staff member before issuing a new one, so
        // only the most recent reset link is ever usable -- an
        // intercepted older email shouldn't stay valid indefinitely
        // just because a newer one was requested afterward.
        java.util.List<StaffPasswordResetToken> priorTokens = tokenRepository.findAll().stream()
                .filter(t -> t.getStaffUser().getId().equals(staff.getId()) && t.getUsedAt() == null)
                .toList();
        OffsetDateTime now = OffsetDateTime.now();
        priorTokens.forEach(t -> t.setUsedAt(now));
        tokenRepository.saveAll(priorTokens);

        String rawToken = PasswordResetTokenUtil.generateRawToken();
        StaffPasswordResetToken token = new StaffPasswordResetToken();
        token.setStaffUser(staff);
        token.setTokenHash(PasswordResetTokenUtil.hashToken(rawToken));
        token.setExpiresAt(now.plusHours(EXPIRY_HOURS));
        tokenRepository.save(token);

        String resetLink = appBaseUrl + "/admin/password/reset?token=" + rawToken;
        emailService.send(staff.getEmail(), "CAA Admin Password Reset",
                "A password reset was requested for your CAA staff account (" + staff.getUsername() + ").\n\n"
                        + "Reset your password here: " + resetLink + "\n\n"
                        + "This link expires in " + EXPIRY_HOURS + " hour and can only be used once. "
                        + "If you didn't request this, you can safely ignore this email.");

        return RequestResult.SENT;
    }

    /** Null return (rather than throwing) signals an invalid, expired, or already-used token -- all collapsed to the same outcome. */
    @Transactional
    public Boolean completeReset(String rawToken, String newPassword) {
        String tokenHash = PasswordResetTokenUtil.hashToken(rawToken);
        Optional<StaffPasswordResetToken> maybeToken = tokenRepository.findByTokenHash(tokenHash);
        if (maybeToken.isEmpty()) {
            return false;
        }
        StaffPasswordResetToken token = maybeToken.get();
        if (token.getUsedAt() != null || token.getExpiresAt().isBefore(OffsetDateTime.now())) {
            return false;
        }

        StaffUser staff = token.getStaffUser();
        staff.setPasswordHash(passwordEncoder.encode(newPassword));
        staffUserRepository.save(staff);

        token.setUsedAt(OffsetDateTime.now());
        tokenRepository.save(token);

        return true;
    }
}
