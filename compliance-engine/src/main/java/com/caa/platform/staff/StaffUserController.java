package com.caa.platform.staff;

import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.web.bind.annotation.*;

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

    public StaffUserController(StaffUserRepository staffUserRepository, PasswordEncoder passwordEncoder) {
        this.staffUserRepository = staffUserRepository;
        this.passwordEncoder = passwordEncoder;
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
}
