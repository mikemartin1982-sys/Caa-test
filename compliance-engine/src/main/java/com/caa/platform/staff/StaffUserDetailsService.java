package com.caa.platform.staff;

import com.caa.platform.client.Client;
import com.caa.platform.client.ClientRepository;
import org.springframework.security.core.authority.SimpleGrantedAuthority;
import org.springframework.security.core.userdetails.User;
import org.springframework.security.core.userdetails.UserDetails;
import org.springframework.security.core.userdetails.UserDetailsService;
import org.springframework.security.core.userdetails.UsernameNotFoundException;
import org.springframework.stereotype.Service;

import java.util.List;

/**
 * Loads a login principal by username for Spring Security's authentication
 * -- StaffUser first (by username), falling back to Client (by email) if
 * no staff match is found (Michael, 2026-08-19: general prospective-
 * client account creation, independent of VR vs. traditional testing).
 * These are separate namespaces (staff usernames vs. client emails), so
 * trying StaffUser first and falling through is unambiguous -- a staff
 * username and a client email are never the same string in practice.
 *
 * StaffRole maps to a Spring authority (ROLE_STAFF / ROLE_COMPLIANCE_ADMINISTRATOR);
 * a Client match always gets ROLE_CLIENT, regardless of ClientType --
 * Individual vs. Organization is a business distinction for the portal
 * UI to show different things, not a security-role distinction.
 */
@Service
public class StaffUserDetailsService implements UserDetailsService {

    private final StaffUserRepository staffUserRepository;
    private final ClientRepository clientRepository;

    public StaffUserDetailsService(StaffUserRepository staffUserRepository, ClientRepository clientRepository) {
        this.staffUserRepository = staffUserRepository;
        this.clientRepository = clientRepository;
    }

    @Override
    public UserDetails loadUserByUsername(String username) throws UsernameNotFoundException {
        var staff = staffUserRepository.findByUsername(username);
        if (staff.isPresent()) {
            if (staff.get().getPasswordHash() == null) {
                // A StaffUser created before password auth existed, or via
                // direct SQL, with no password set -- fails closed rather
                // than allowing an empty-password login.
                throw new UsernameNotFoundException("Staff user has no password set: " + username);
            }
            return User.builder()
                    .username(staff.get().getUsername())
                    .password(staff.get().getPasswordHash())
                    .authorities(List.of(new SimpleGrantedAuthority("ROLE_" + staff.get().getRole().name())))
                    .build();
        }

        Client client = clientRepository.findByEmail(username)
                .orElseThrow(() -> new UsernameNotFoundException("No staff user or client with this identifier: " + username));

        if (client.getPasswordHash() == null) {
            // A Client from the OLD flow (staff-entered via inquiry
            // conversion) with no self-serve login ever set up -- fails
            // closed, same reasoning as the StaffUser case above.
            throw new UsernameNotFoundException("Client has no password set: " + username);
        }

        return User.builder()
                .username(client.getEmail())
                .password(client.getPasswordHash())
                .authorities(List.of(new SimpleGrantedAuthority("ROLE_CLIENT")))
                .build();
    }
}
