package com.caa.platform.staff;

import com.caa.platform.client.Client;
import com.caa.platform.client.ClientRepository;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.security.core.Authentication;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import java.util.Map;

/**
 * Section 3: /auth/me exists specifically to support Laravel's login
 * flows -- given a username/password via HTTP Basic, this endpoint
 * either succeeds (200, credentials valid, here's who you are) or fails
 * (401, Spring Security rejects before this method ever runs). Laravel's
 * StaffApiUserProvider (and, for clients, an equivalent provider) uses
 * exactly that behavior to verify credentials without storing a second
 * copy of the password anywhere.
 *
 * Extended 2026-08-19 (general prospective-client account creation) to
 * cover both StaffUser and Client logins in one response, since
 * StaffUserDetailsService now authenticates both -- type discriminates
 * which set of fields is populated, since the two identities don't
 * share a shape (username/role vs. email/clientType).
 */
@RestController
@RequestMapping("/api/v1/auth")
public class AuthController {

    static final int MIN_PASSWORD_LENGTH = 8;

    private final StaffUserRepository staffUserRepository;
    private final ClientRepository clientRepository;
    private final PasswordEncoder passwordEncoder;

    public AuthController(StaffUserRepository staffUserRepository, ClientRepository clientRepository,
                          PasswordEncoder passwordEncoder) {
        this.staffUserRepository = staffUserRepository;
        this.clientRepository = clientRepository;
        this.passwordEncoder = passwordEncoder;
    }

    public record MeResponse(String type, Long id, String name, String username, StaffRole staffRole,
                              String email, com.caa.platform.client.ClientType clientType) {}

    @GetMapping("/me")
    public ResponseEntity<MeResponse> me(Authentication authentication) {
        var staff = staffUserRepository.findByUsername(authentication.getName());
        if (staff.isPresent()) {
            StaffUser s = staff.get();
            return ResponseEntity.ok(new MeResponse("STAFF", s.getId(), s.getName(), s.getUsername(), s.getRole(), null, null));
        }

        Client client = clientRepository.findByEmail(authentication.getName())
                .orElseThrow(() -> new IllegalStateException(
                        "Authenticated as '" + authentication.getName() + "' but no matching StaffUser or Client found."));

        String name = client.getClientType() == com.caa.platform.client.ClientType.INDIVIDUAL
                ? (client.getFirstName() + " " + client.getLastName())
                : client.getCompany();
        return ResponseEntity.ok(new MeResponse("CLIENT", client.getId(), name, null, null, client.getEmail(), client.getClientType()));
    }

    public record ChangePasswordRequest(String newPassword) {}

    /**
     * Michael, 2026-09-27 -- self-service "Change Password" for a
     * logged-in staff member. Laravel calls this with the staff member's
     * OWN username and CURRENT password as HTTP Basic credentials (not
     * the laravel-service account), so the current password is verified
     * by Spring Security before this method ever runs -- a wrong current
     * password is a 401 exactly like a failed login, and there's no way
     * to change anyone's password but your own. Client logins also
     * authenticate here (same UserDetailsService) and are refused: clients
     * have their own flows.
     */
    @PostMapping("/me/password")
    public ResponseEntity<?> changeOwnPassword(Authentication authentication, @RequestBody ChangePasswordRequest req) {
        var staff = staffUserRepository.findByUsername(authentication.getName());
        if (staff.isEmpty()) {
            return ResponseEntity.status(HttpStatus.FORBIDDEN)
                    .body(Map.of("error", "Only staff accounts can change their password here."));
        }
        if (req.newPassword() == null || req.newPassword().length() < MIN_PASSWORD_LENGTH) {
            return ResponseEntity.unprocessableEntity()
                    .body(Map.of("error", "New password must be at least " + MIN_PASSWORD_LENGTH + " characters."));
        }

        StaffUser s = staff.get();
        s.setPasswordHash(passwordEncoder.encode(req.newPassword()));
        staffUserRepository.save(s);
        return ResponseEntity.ok(Map.of("passwordChanged", true));
    }
}
