package com.caa.platform.config;

import jakarta.servlet.http.HttpServletResponse;
import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Configuration;
import org.springframework.security.config.annotation.method.configuration.EnableMethodSecurity;
import org.springframework.security.config.annotation.web.builders.HttpSecurity;
import org.springframework.security.config.annotation.web.configuration.EnableWebSecurity;
import org.springframework.security.config.http.SessionCreationPolicy;
import org.springframework.security.crypto.bcrypt.BCryptPasswordEncoder;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.security.web.SecurityFilterChain;

/**
 * HTTP Basic auth for the whole API, backed by StaffUser (see
 * StaffUserDetailsService). Stateless -- no session cookies, since every
 * client here (Laravel, or a future admin SPA) is expected to send
 * credentials on every request rather than maintain a server-side session.
 *
 * This closes the "wide open API" gap flagged repeatedly once this
 * started running on real infrastructure -- previously every endpoint
 * was reachable with zero authentication.
 *
 * @EnableMethodSecurity (2026-08-18, real gap found via an external
 * code review) -- StaffRole (STAFF / COMPLIANCE_ADMINISTRATOR) has
 * correctly mapped to a Spring authority in StaffUserDetailsService
 * since it was built, but nothing ever enforced it -- every endpoint
 * here only required .authenticated(), so any authenticated staff
 * member could call anything regardless of role. This annotation is
 * what makes @PreAuthorize("hasRole(...)") on individual controller
 * methods actually take effect (see StaffUserController's create()/
 * update()) -- without it, those annotations are silently ignored.
 */
@Configuration
@EnableWebSecurity
@EnableMethodSecurity
public class SecurityConfig {

    @Bean
    public PasswordEncoder passwordEncoder() {
        return new BCryptPasswordEncoder();
    }

    @Bean
    public SecurityFilterChain filterChain(HttpSecurity http) throws Exception {
        http
                .csrf(csrf -> csrf.disable()) // stateless API, no cookie-based session to protect
                .sessionManagement(session -> session.sessionCreationPolicy(SessionCreationPolicy.STATELESS))
                .authorizeHttpRequests(auth -> auth
                        // Bootstrap only -- sets a password for a StaffUser that has
                        // NONE yet (see StaffUserController.setInitialPassword). Rejects
                        // in the controller itself if a password is already set, so
                        // this narrow permitAll doesn't become a standing reset hole.
                        .requestMatchers(org.springframework.http.HttpMethod.PATCH, "/api/v1/staff/*/password").permitAll()
                        // Public, self-serve account creation (Michael, 2026-08-19)
                        // -- general prospective-client registration, independent of
                        // VR vs. traditional testing. Deliberately its own narrow
                        // rule, not a broad prefix -- everything else under
                        // /api/v1/clients/** stays authenticated.
                        .requestMatchers(org.springframework.http.HttpMethod.POST, "/api/v1/clients/register").permitAll()
                        .anyRequest().authenticated()
                )
                .httpBasic(basic -> {})
                .exceptionHandling(ex -> ex
                        // Spring's default AccessDeniedHandler doesn't return JSON --
                        // Laravel's error handling (ComplianceEngineForbiddenException)
                        // expects the same {"error": "..."} shape every other endpoint
                        // uses, so a role check failing here would otherwise surface as
                        // a confusing, un-parseable response on the Laravel side.
                        .accessDeniedHandler((request, response, accessDeniedException) -> {
                            response.setStatus(HttpServletResponse.SC_FORBIDDEN);
                            response.setContentType("application/json");
                            response.getWriter().write("{\"error\":\"You don't have permission to do this.\"}");
                        })
                );

        return http.build();
    }
}
