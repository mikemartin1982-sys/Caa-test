package com.caa.platform.enrollment;

import com.caa.platform.certification.CertificationRun;
import com.caa.platform.client.Client;
import com.caa.platform.session.Session;
import com.caa.platform.session.StaggeredArrivalBlock;
import com.caa.platform.student.Student;
import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;
import org.hibernate.annotations.JdbcTypeCode;
import org.hibernate.type.SqlTypes;

import java.time.OffsetDateTime;

/**
 * Section 3/4f/4g: paymentStatus lives on Session Details (also surfaced
 * on the Roster for Public/VR, Section 4f); rosterStatus drives close-out
 * (Section 4g).
 */
@Entity
@Table(name = "enrollments", uniqueConstraints = @UniqueConstraint(columnNames = {"student_id", "session_id", "enrollment_components"}))
@Getter
@Setter
@NoArgsConstructor
public class Enrollment {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "student_id", nullable = false)
    private Student student;

    /** Billing/paying entity. */
    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "client_id", nullable = false)
    private Client client;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "session_id", nullable = false)
    private Session session;

    @Column(name = "enrollment_date", nullable = false)
    private OffsetDateTime enrollmentDate = OffsetDateTime.now();

    @Enumerated(EnumType.STRING)
    @JdbcTypeCode(SqlTypes.NAMED_ENUM)
    @Column(name = "payment_status", nullable = false)
    private PaymentStatus paymentStatus = PaymentStatus.PENDING;

    @Enumerated(EnumType.STRING)
    @JdbcTypeCode(SqlTypes.NAMED_ENUM)
    @Column(name = "roster_status")
    private RosterStatus rosterStatus;

    /** Semi-Private only; excluded from CAA's own billing. */
    @Column(name = "outside_attendee", nullable = false)
    private boolean outsideAttendee = false;

    @Column(name = "lecture_access_granted", nullable = false)
    private boolean lectureAccessGranted = false;

    /**
     * Michael, 2026-08-25 -- Client Portal Enroll rebuild. Plain STRING
     * mapping (NOT the NAMED_ENUM JdbcTypeCode paymentStatus/rosterStatus
     * use above) -- those map to real Postgres native enum types
     * created via CREATE TYPE (migration 003); this column is a plain
     * VARCHAR(20) (migration 031), so NAMED_ENUM would fail looking
     * for a Postgres enum type that doesn't exist here.
     */
    @Enumerated(EnumType.STRING)
    @Column(name = "enrollment_components", nullable = false)
    private EnrollmentComponents enrollmentComponents = EnrollmentComponents.FIELD_ONLY;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "certifying_run_id")
    private CertificationRun certifyingRun;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "practice_run_id")
    private CertificationRun practiceRun;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "staggered_arrival_block_id")
    private StaggeredArrivalBlock staggeredArrivalBlock;

    @Column(name = "created_at", nullable = false, updatable = false)
    private OffsetDateTime createdAt = OffsetDateTime.now();

    @Column(name = "updated_at")
    private OffsetDateTime updatedAt = OffsetDateTime.now();
}
