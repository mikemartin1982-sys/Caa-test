package com.caa.platform.client;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;
import org.hibernate.annotations.JdbcTypeCode;
import org.hibernate.type.SqlTypes;

import java.time.OffsetDateTime;

/**
 * Section 3c: public "New Client Account" form submissions land here as
 * structured data. Deliberately does NOT auto-create a Client -- a
 * security decision against bot/bad-actor abuse of a public form. Staff
 * convert manually, preserving a paper trail via convertedClient.
 */
@Entity
@Table(name = "inquiries")
@Getter
@Setter
@NoArgsConstructor
public class Inquiry {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Column(nullable = false)
    private String company;

    @Column(name = "first_name")
    private String firstName;

    @Column(name = "last_name")
    private String lastName;

    private String email;
    private String phone;

    @Column(name = "company_address")
    private String companyAddress;

    private String city;

    @Column(length = 2)
    private String state;

    private String zip;

    @Column(name = "lead_source")
    private String leadSource;

    @Column(name = "pref_newsletter", nullable = false)
    private boolean prefNewsletter = false;

    @Column(name = "pref_class_confirms", nullable = false)
    private boolean prefClassConfirms = false;

    @Column(name = "pref_cert_reminders", nullable = false)
    private boolean prefCertReminders = false;

    @Column(name = "submitted_at", nullable = false)
    private OffsetDateTime submittedAt = OffsetDateTime.now();

    @Enumerated(EnumType.STRING)
    @JdbcTypeCode(SqlTypes.NAMED_ENUM)
    @Column(nullable = false)
    private InquiryStatus status = InquiryStatus.PENDING;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "converted_client_id")
    private Client convertedClient;
}
