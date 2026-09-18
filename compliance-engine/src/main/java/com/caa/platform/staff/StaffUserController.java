package com.caa.platform.staff;

import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.web.bind.annotation.*;

import com.caa.platform.security.IpRateLimiter;
import jakarta.servlet.http.HttpServletRequest;

import java.util.List;
import java.util.Map;

/**
 * Section 3: StaffUser CRUD, plus a narrowly-scoped bootstrap password-set
 * endpoint (see setInitialPassword below and SecurityConfig, which permits
 * ONLY that one specific PATCH path without authentication -- everything
 * else on the API requires it, per Section 3's security note).
 */
@RestController
@RequestMapping("/api/v1/staff")
public class StaffUserController {

    private final StaffUserRepository staffUserRepository;
    private final PasswordEncoder passwordEncoder;
    private final StaffPasswordResetService passwordResetService;
    private final IpRateLimiter rateLimiter;

    public StaffUserController(StaffUserRepository staffUserRepository, PasswordEncoder passwordEncoder,
                                StaffPasswordResetService passwordResetService, IpRateLimiter rateLimiter) {
        this.staffUserRepository = staffUserRepository;
        this.passwordEncoder = passwordEncoder;
        this.passwordResetService = passwordResetService;
        this.rateLimiter = rateLimiter;
    }

    @GetMapping
    public ResponseEntity<List<StaffUser>> list() {
        return ResponseEntity.ok(staffUserRepository.findAll());
    }

    @GetMapping("/{staffId}")
    public ResponseEntity<StaffUser> get(@PathVariable Long staffId) {
        return staffUserRepository.findById(staffId)
                .map(ResponseEntity::ok)
                .orElseGet(() -> ResponseEntity.notFound().build());
    }

    public record CreateStaffUserRequest(String name, String username, StaffRole role, String password, String initials,
                                          String email, String phone, String mobilePhone, String jobTitle,
                                          Long actingStaffId) {}

    /**
     * Compliance-Administrator-only -- creating a new staff account,
     * including setting ITS role, is exactly the kind of privilege-
     * escalation risk role checks exist to prevent.
     *
     * actingStaffId is REQUIRED, not optional (2026-08-22, found live
     * during testing, fixed twice in one pass): @PreAuthorize alone
     * can't enforce this through Laravel's UI, since every Laravel-to-
     * Java call authenticates as the shared service account, never the
     * real staff member's own credentials -- Spring Security only ever
     * sees the service account, so @PreAuthorize was always evaluating
     * the WRONG principal and blocking every Compliance Administrator,
     * not just non-admins. @PreAuthorize is REMOVED here entirely, not
     * left in place -- it runs before the method body at all, so
     * keeping it would have silently prevented this fix's own check
     * from ever executing. Making actingStaffId optional was my first,
     * wrong attempt at this same fix: skipping the check when it's
     * omitted would have let ANY caller -- including the service
     * account itself with no real person behind it -- create or edit
     * staff accounts unchecked. Required, not optional, closes that.
     * Same root cause and same fix as sendingStaffId (also required)
     * on SessionController's confirmation-email/bid-generation
     * endpoints.
     */
    @PostMapping
    public ResponseEntity<?> create(@RequestBody CreateStaffUserRequest req) {
        if (req.actingStaffId() == null) {
            return ResponseEntity.status(HttpStatus.FORBIDDEN)
                    .body(Map.of("error", "actingStaffId is required -- only Compliance Administrators can create staff accounts."));
        }
        StaffUser actor = staffUserRepository.findById(req.actingStaffId()).orElse(null);
        if (actor == null || actor.getRole() != StaffRole.COMPLIANCE_ADMINISTRATOR) {
            return ResponseEntity.status(HttpStatus.FORBIDDEN)
                    .body(Map.of("error", "Only Compliance Administrators can create staff accounts."));
        }
        if (req.password() == null || req.password().isBlank()) {
            return ResponseEntity.unprocessableEntity().body(Map.of("error", "password is required"));
        }
        // Michael, 2026-08-31 -- Password Reset feature. Confirmed with
        // Michael: worth requiring an email on file at staff creation
        // specifically so a self-serve reset is always possible later
        // -- reusing the same EmailValidator already used for Student
        // email, not inventing a separate check.
        if (!com.caa.platform.common.EmailValidator.isValid(req.email())) {
            return ResponseEntity.unprocessableEntity().body(Map.of("error", "A valid email is required."));
        }
        StaffUser staff = new StaffUser();
        staff.setName(req.name());
        staff.setUsername(req.username());
        staff.setRole(req.role() != null ? req.role() : StaffRole.STAFF);
        staff.setInitials(req.initials());
        staff.setPasswordHash(passwordEncoder.encode(req.password()));
        staff.setEmail(req.email());
        staff.setPhone(req.phone());
        staff.setMobilePhone(req.mobilePhone());
        staff.setJobTitle(req.jobTitle());
        return ResponseEntity.status(HttpStatus.CREATED).body(staffUserRepository.save(staff));
    }

    public record UpdateStaffUserRequest(String name, String initials, StaffRole role,
                                          String email, String phone, String mobilePhone, String jobTitle,
                                          Long actingStaffId) {}

    /**
     * Compliance-Administrator-only, same reasoning and same
     * actingStaffId fix as create() above (required, @PreAuthorize
     * removed entirely, not left in place) -- this can change an
     * EXISTING staff member's role, including elevating someone to
     * Compliance Administrator. General field edits for an EXISTING
     * staff record. Password changes go through the dedicated
     * endpoint below.
     */
    @PatchMapping("/{staffId}")
    public ResponseEntity<?> update(@PathVariable Long staffId, @RequestBody UpdateStaffUserRequest req) {
        if (req.actingStaffId() == null) {
            return ResponseEntity.status(HttpStatus.FORBIDDEN)
                    .body(Map.of("error", "actingStaffId is required -- only Compliance Administrators can update staff accounts."));
        }
        StaffUser actor = staffUserRepository.findById(req.actingStaffId()).orElse(null);
        if (actor == null || actor.getRole() != StaffRole.COMPLIANCE_ADMINISTRATOR) {
            return ResponseEntity.status(HttpStatus.FORBIDDEN)
                    .body(Map.of("error", "Only Compliance Administrators can update staff accounts."));
        }
        StaffUser staff = staffUserRepository.findById(staffId)
                .orElseThrow(() -> new IllegalArgumentException("Staff user not found: " + staffId));
        if (req.name() != null) staff.setName(req.name());
        if (req.initials() != null) staff.setInitials(req.initials());
        if (req.role() != null) staff.setRole(req.role());
        if (req.email() != null) staff.setEmail(req.email());
        if (req.phone() != null) staff.setPhone(req.phone());
        if (req.mobilePhone() != null) staff.setMobilePhone(req.mobilePhone());
        if (req.jobTitle() != null) staff.setJobTitle(req.jobTitle());
        return ResponseEntity.ok(staffUserRepository.save(staff));
    }

    public record SetPasswordRequest(String password) {}

    /**
     * Bootstrap-only: sets a password for a StaffUser that currently has
     * NONE (e.g. a row created via SQL or before this feature existed --
     * your existing "Derek Mason" account is exactly this case). Rejects
     * (403) if a password is already set, since changing an EXISTING
     * password should go through an authenticated flow -- not yet built
     * in this pass, and this endpoint deliberately does not become that
     * flow, to avoid it being a standing unauthenticated password-reset hole.
     *
     * SecurityConfig permits this one specific path without auth; every
     * other endpoint on the API requires it.
     */
    @PatchMapping("/{staffId}/password")
    public ResponseEntity<?> setInitialPassword(@PathVariable Long staffId, @RequestBody SetPasswordRequest req) {
        StaffUser staff = staffUserRepository.findById(staffId)
                .orElseThrow(() -> new IllegalArgumentException("Staff user not found: " + staffId));

        if (staff.getPasswordHash() != null) {
            return ResponseEntity.status(HttpStatus.FORBIDDEN)
                    .body(Map.of("error", "Password already set for this user. Changing an existing "
                            + "password requires an authenticated flow, not this bootstrap endpoint."));
        }
        if (req.password() == null || req.password().isBlank()) {
            return ResponseEntity.unprocessableEntity().body(Map.of("error", "password is required"));
        }

        staff.setPasswordHash(passwordEncoder.encode(req.password()));
        staffUserRepository.save(staff);
        return ResponseEntity.ok(Map.of("passwordSet", true));
    }

    public record ForgotPasswordRequest(String username) {}

    /**
     * Michael, 2026-08-31 -- Password Reset feature. This IS the real,
     * authenticated reset flow setInitialPassword() above deliberately
     * didn't become. Unauthenticated by necessity (someone who forgot
     * their password can't authenticate first) -- permitted in
     * SecurityConfig, same narrow, method-specific style as the two
     * existing exceptions there.
     *
     * Response is deliberately generic for both a real, sent email and
     * an unknown username -- but NOT for a real username with no email
     * on file, which is shown plainly. Confirmed with Michael: this is
     * an internal, admin-only tool, not a public-facing system, so
     * that specific, honest message is more useful here than uniform
     * enumeration protection would be.
     */
    @PostMapping("/forgot-password")
    public ResponseEntity<?> forgotPassword(@RequestBody ForgotPasswordRequest req, HttpServletRequest request) {
        // Michael, 2026-09-04 -- rate limiting, confirmed with Michael:
        // 5 attempts per 15 minutes, keyed by IP. Checked first, before
        // any real work -- a rejected attempt must not itself count,
        // and must not leak any real information about the username
        // either (same generic-style response either way).
        if (!rateLimiter.allow(request.getRemoteAddr(), "staff-forgot-password")) {
            return ResponseEntity.status(HttpStatus.TOO_MANY_REQUESTS)
                    .body(Map.of("error", "Too many attempts. Please wait and try again later."));
        }

        if (req.username() == null || req.username().isBlank()) {
            return ResponseEntity.unprocessableEntity().body(Map.of("error", "username is required"));
        }

        StaffPasswordResetService.RequestResult result = passwordResetService.requestReset(req.username());

        if (result == StaffPasswordResetService.RequestResult.NO_EMAIL_ON_FILE) {
            return ResponseEntity.ok(Map.of(
                    "message", "This account has no email on file, so a reset link can't be sent. Contact a Compliance Administrator."));
        }

        // SENT and NO_SUCH_USER intentionally return the identical response.
        return ResponseEntity.ok(Map.of(
                "message", "If that username exists and has an email on file, a password reset link has been sent."));
    }

    public record ResetPasswordRequest(String token, String password) {}

    @PostMapping("/reset-password")
    public ResponseEntity<?> resetPassword(@RequestBody ResetPasswordRequest req, HttpServletRequest request) {
        // Michael, 2026-09-04 -- same rate limiting, a real, separate
        // bucket from forgot-password above (own endpointKey) -- this
        // is also where a real, brute-force attempt against the reset
        // token itself would show up, a genuinely different threat
        // from repeatedly requesting new reset emails.
        if (!rateLimiter.allow(request.getRemoteAddr(), "staff-reset-password")) {
            return ResponseEntity.status(HttpStatus.TOO_MANY_REQUESTS)
                    .body(Map.of("error", "Too many attempts. Please wait and try again later."));
        }

        if (req.token() == null || req.token().isBlank()) {
            return ResponseEntity.unprocessableEntity().body(Map.of("error", "token is required"));
        }
        if (req.password() == null || req.password().isBlank()) {
            return ResponseEntity.unprocessableEntity().body(Map.of("error", "password is required"));
        }

        boolean success = passwordResetService.completeReset(req.token(), req.password());
        if (!success) {
            return ResponseEntity.status(HttpStatus.CONFLICT)
                    .body(Map.of("error", "This reset link is invalid or has expired. Request a new one."));
        }
        return ResponseEntity.ok(Map.of("passwordReset", true));
    }
}
