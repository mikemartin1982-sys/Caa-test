package com.caa.platform.staff;

import com.caa.platform.client.ClientRepository;
import org.junit.jupiter.api.Test;
import org.springframework.http.ResponseEntity;
import org.springframework.security.authentication.UsernamePasswordAuthenticationToken;
import org.springframework.security.core.Authentication;
import org.springframework.security.crypto.password.PasswordEncoder;

import java.util.Optional;

import static org.junit.jupiter.api.Assertions.assertEquals;
import static org.mockito.ArgumentMatchers.any;
import static org.mockito.Mockito.mock;
import static org.mockito.Mockito.never;
import static org.mockito.Mockito.verify;
import static org.mockito.Mockito.when;

class AuthControllerChangePasswordTest {
    private final StaffUserRepository staffUsers = mock(StaffUserRepository.class);
    private final PasswordEncoder encoder = mock(PasswordEncoder.class);
    private final AuthController controller = new AuthController(staffUsers, mock(ClientRepository.class), encoder);

    @Test
    void staffMemberChangesOwnPassword() {
        StaffUser staff = new StaffUser();
        staff.setPasswordHash("old-hash");
        when(staffUsers.findByUsername("dmason")).thenReturn(Optional.of(staff));
        when(encoder.encode("new-password-123")).thenReturn("new-hash");

        ResponseEntity<?> response = controller.changeOwnPassword(auth("dmason"),
                new AuthController.ChangePasswordRequest("new-password-123"));

        assertEquals(200, response.getStatusCode().value());
        assertEquals("new-hash", staff.getPasswordHash());
        verify(staffUsers).save(staff);
    }

    @Test
    void tooShortPasswordIsRejectedWithoutSaving() {
        when(staffUsers.findByUsername("dmason")).thenReturn(Optional.of(new StaffUser()));

        ResponseEntity<?> response = controller.changeOwnPassword(auth("dmason"),
                new AuthController.ChangePasswordRequest("short"));

        assertEquals(422, response.getStatusCode().value());
        verify(staffUsers, never()).save(any());
    }

    @Test
    void clientLoginCannotUseStaffPasswordChange() {
        when(staffUsers.findByUsername("client@example.com")).thenReturn(Optional.empty());

        ResponseEntity<?> response = controller.changeOwnPassword(auth("client@example.com"),
                new AuthController.ChangePasswordRequest("new-password-123"));

        assertEquals(403, response.getStatusCode().value());
        verify(staffUsers, never()).save(any());
    }

    private static Authentication auth(String name) {
        return new UsernamePasswordAuthenticationToken(name, null);
    }
}
