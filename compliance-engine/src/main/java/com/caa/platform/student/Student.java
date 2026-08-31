package com.caa.platform.student;

import com.caa.platform.certification.CertificationRun;
import com.caa.platform.client.Client;
import com.caa.platform.common.AuditableEntity;
import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;

import java.time.LocalDate;

/**
 * Section 3. "Employees" managed by a Client via the Client Portal.
 * practiceRunCompleted / lectureComplete are person-level facts (not
 * per-enrollment) -- they cascade to the Client Page and every Session
 * Roster the student appears on (Section 4f).
 */
@Entity
@Table(name = "students")
@Getter
@Setter
@NoArgsConstructor
public class Student extends AuditableEntity {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    /** Used for lecture sign-in (Student ID + last name, Section 4f). */
    @Column(name = "student_number", nullable = false, unique = true)
    private String studentNumber;

    @Column(nullable = false)
    private String name;

    /** Required, need not be unique across students (Section 3). */
    @Column(nullable = false)
    private String phone;

    /** Required, need not be unique across students (Section 3). */
    @Column(nullable = false)
    private String email;

    @Column(name = "cross_provider_identifier")
    private String crossProviderIdentifier;

    /** Primary employer -- distinct from the billing client on a specific Enrollment. */
    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "employer_client_id")
    private Client employerClient;

    // --- Texas practice-run rule (Section 4) -- Texas only, person-level ---
    @Column(name = "texas_practice_run_completed", nullable = false)
    private boolean texasPracticeRunCompleted = false;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "texas_practice_run_id")
    private CertificationRun texasPracticeRun;

    // --- Lecture completion -- person-level fact (Section 4f) ---
    @Column(name = "lecture_complete", nullable = false)
    private boolean lectureComplete = false;

    @Column(name = "lecture_completion_date")
    private LocalDate lectureCompletionDate;

    /** 'caa_lecture' | 'uploaded_certificate' */
    @Column(name = "lecture_completion_source")
    private String lectureCompletionSource;

    @Column(name = "lecture_certificate_upload")
    private String lectureCertificateUpload;

    // --- Staff override flags (Section 4b) -- independent of enrollment status ---
    @Column(name = "self_paced_lecture_allowed", nullable = false)
    private boolean selfPacedLectureAllowed = true;

    @Column(name = "vr_allowed", nullable = false)
    private boolean vrAllowed = true;

    // --- Iowa disclosure (Section 4) -- self-reported, staff-checked ---
    @Column(name = "iowa_500_plume_completion", nullable = false)
    private boolean iowa500PlumeCompletion = false;

    /** 'email' | 'phone' */
    @Column(name = "preferred_contact_method")
    private String preferredContactMethod = "email";

    /**
     * Michael, 2026-08-23 -- Client Page roster rebuild. Every new
     * employee starts Active; staff toggle this off when someone
     * leaves the company, matching DIBs' own inline-editable roster
     * checkbox.
     */
    @Column(nullable = false)
    private boolean active = true;

    /**
     * Michael, 2026-08-24 -- "Combine Employee" backend. Set when this
     * record was found to be a duplicate of another Student and merged
     * away -- null means this record is not a merged duplicate. Not a
     * hard delete; matches the same soft, audit-friendly preference as
     * the active flag above, so the old record stays visible with a
     * clear pointer to where its real history now lives, instead of
     * simply vanishing.
     */
    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "merged_into_student_id")
    @com.fasterxml.jackson.annotation.JsonIgnore
    private Student mergedInto;
}
