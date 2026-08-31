package com.caa.platform.staff;

import com.caa.platform.common.AuditableEntity;
import com.fasterxml.jackson.annotation.JsonIgnore;
import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;
import org.hibernate.annotations.JdbcTypeCode;
import org.hibernate.type.SqlTypes;

@Entity
@Table(name = "staff_users")
@Getter
@Setter
@NoArgsConstructor
public class StaffUser extends AuditableEntity {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Column(nullable = false)
    private String name;

    @Column(nullable = false, unique = true)
    private String username;

    /** Shorthand display (e.g. "DWM" for Derek Mason) -- not reliably derivable from name alone (middle initials, etc.), so tracked explicitly. */
    @Column(length = 5)
    private String initials;

    @Enumerated(EnumType.STRING)
    @JdbcTypeCode(SqlTypes.NAMED_ENUM)
    @Column(nullable = false)
    private StaffRole role = StaffRole.STAFF;

    /**
     * Michael, 2026-08-19 -- needed so the session confirmation email's
     * signature and reply-to (Confirm/Request-a-Change buttons) reflect
     * whichever staff member actually sent it, not a hardcoded person.
     * All four nullable -- a StaffUser created before this migration,
     * or one who just hasn't filled these in yet, shouldn't be blocked
     * from anything; SessionConfirmationEmailService falls back
     * sensibly when any of these are blank.
     */
    private String email;

    private String phone;

    @Column(name = "mobile_phone")
    private String mobilePhone;

    @Column(name = "job_title")
    private String jobTitle;

    /**
     * BCrypt hash, never the raw password. @JsonIgnore so this never
     * accidentally serializes into an API response -- worth being
     * deliberate about, since every other field on every other entity in
     * this codebase does get returned as-is.
     */
    @JsonIgnore
    @Column(name = "password_hash")
    private String passwordHash;

    public boolean isComplianceAdministrator() {
        return role == StaffRole.COMPLIANCE_ADMINISTRATOR;
    }
}
