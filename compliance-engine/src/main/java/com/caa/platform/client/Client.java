package com.caa.platform.client;

import com.caa.platform.common.AuditableEntity;
import com.fasterxml.jackson.annotation.JsonIgnore;
import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;
import org.hibernate.annotations.JdbcTypeCode;
import org.hibernate.type.SqlTypes;

import java.math.BigDecimal;

/**
 * Section 3 / 3c. Company is the raw legal name as staff enters it;
 * recordName and portalDisplayName are auto-derived (see
 * {@link ClientNameDerivationService}) -- never set directly by callers.
 */
@Entity
@Table(name = "clients")
@Getter
@Setter
@NoArgsConstructor
public class Client extends AuditableEntity {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Column(name = "qbo_reference_id")
    private String qboReferenceId;

    @Column(nullable = false)
    private String company;

    /** Auto-derived: "{Company, suffix stripped} - {City}, {ST}" (Section 3c). */
    @Column(name = "record_name")
    private String recordName;

    /** Auto-derived: "{Company, suffix stripped}" (Section 3c). */
    @Column(name = "portal_display_name")
    private String portalDisplayName;

    @Column(name = "first_name")
    private String firstName;

    @Column(name = "last_name")
    private String lastName;

    private String address;
    private String city;

    @Column(length = 2)
    private String state;

    private String zip;
    private String phone;
    private String email;

    /** "How They Heard About CAA," captured at inquiry (Section 3c). */
    @Column(name = "lead_source")
    private String leadSource;

    @Column(name = "pref_newsletter", nullable = false)
    private boolean prefNewsletter = false;

    @Column(name = "pref_class_confirms", nullable = false)
    private boolean prefClassConfirms = false;

    @Column(name = "pref_cert_reminders", nullable = false)
    private boolean prefCertReminders = false;

    /** Gates portal eligibility to enroll employees into the Public VR Session (Section 4b). */
    @Column(name = "vr_client", nullable = false)
    private boolean vrClient = false;

    /** Custom fixed per-token rate override (Section 4b) -- nullable. */
    @Column(name = "vr_pricing_override_rate", precision = 8, scale = 2)
    private BigDecimal vrPricingOverrideRate;

    /** No-Cost/$0 flag, e.g. government agencies (Section 4b). */
    @Column(name = "vr_pricing_no_cost", nullable = false)
    private boolean vrPricingNoCost = false;

    /**
     * Separate reference from the platform's own Client ID; kept in sync
     * one-directionally, platform -> Brevo, in real time (Section 3).
     */
    @Column(name = "brevo_contact_id")
    private String brevoContactId;

    /**
     * Michael, 2026-08-19 -- general prospective-client account
     * creation, independent of VR vs. traditional testing. Bcrypt,
     * verified live via Spring Security -- follows the exact same
     * pattern as StaffUser.passwordHash. Null for clients created the
     * OLD way (staff-entered via inquiry conversion, no self-serve
     * login) -- see PortalUserDetailsService, which fails closed if
     * this is null, same as StaffUserDetailsService already does for
     * a StaffUser with no password set.
     */
    @JsonIgnore
    @Column(name = "password_hash")
    private String passwordHash;

    @Enumerated(EnumType.STRING)
    @JdbcTypeCode(SqlTypes.NAMED_ENUM)
    @Column(name = "client_type")
    private ClientType clientType;

    /**
     * Michael, 2026-08-22 -- distinct from firstName/lastName/email/phone
     * above (the primary contact who manages the training relationship).
     * This is specifically where an invoice needs to land to get paid --
     * e.g. an "ap@company.com" AP department inbox, often not a person
     * at all. Confirmed with Michael: only meaningful for Organization
     * clients; an Individual pays directly at certification time. Not
     * enforced at the entity/DB level -- the UI shows this block only
     * for ClientType.ORGANIZATION.
     */
    @Column(name = "billing_contact_name")
    private String billingContactName;

    @Column(name = "billing_email")
    private String billingEmail;

    @Column(name = "billing_phone")
    private String billingPhone;

    /**
     * Michael, 2026-09-03 -- lecture billing exemption, client-level
     * scope. Confirmed with Michael: for cases like a government
     * agency or a specific client management has decided to offer the
     * lecture to at no charge -- applies to EVERY LECTURE_ONLY
     * enrollment for this client, not a one-off. See
     * EnrollmentPricingService.computePrice() for where this is
     * actually checked; see Student's own lectureFeeExempt for the
     * separate, student-level scope (a one-off, e.g. logistics
     * resolving a technical issue for one person).
     */
    @Column(name = "lecture_fee_exempt", nullable = false)
    private boolean lectureFeeExempt = false;
}
