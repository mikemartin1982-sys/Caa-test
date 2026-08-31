package com.caa.platform.staff;

import com.caa.platform.client.Client;
import com.caa.platform.client.ClientRepository;
import org.springframework.http.ResponseEntity;
import org.springframework.security.core.Authentication;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

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

    private final StaffUserRepository staffUserRepository;
    private final ClientRepository clientRepository;

    public AuthController(StaffUserRepository staffUserRepository, ClientRepository clientRepository) {
        this.staffUserRepository = staffUserRepository;
        this.clientRepository = clientRepository;
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
}
