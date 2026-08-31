package com.caa.platform.student;

import com.caa.platform.common.AuditableEntity;
import com.caa.platform.staff.StaffUser;
import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;
import org.hibernate.annotations.JdbcTypeCode;
import org.hibernate.type.SqlTypes;

import java.time.LocalDate;
import java.time.OffsetDateTime;

/**
 * Michael, 2026-08-30 -- Lecture Certificate Upload feature. One
 * generic certificate entity, not something built narrowly just for
 * uploads -- deliberate, given Student.lectureCompletionSource already
 * anticipates exactly two paths ('caa_lecture' | 'uploaded_certificate').
 * Whenever CAA's own self-paced lecture certificate generation gets
 * built (still an open, unbuilt item as of tonight), it slots into
 * this same table under Source.CAA_LECTURE rather than needing a
 * second, parallel structure.
 *
 * Confirmed with Michael: staff-only upload, no client self-upload --
 * deliberately sidesteps needing any kind of "is this a legitimate
 * certificate" validation gate entirely, since that question never
 * gets asked of a client in the first place. The staff upload action
 * itself IS the approval step; there's no separate review workflow.
 *
 * Supersede, not delete, on re-upload (confirmed with Michael,
 * matching the same full-history pattern his own real CertificationRun
 * screenshot showed) -- a full, permanent audit trail, not a record
 * that can silently vanish or get overwritten. isActive() is the one
 * true "this is the current one" check; every prior record for a
 * student stays queryable, just no longer authoritative.
 *
 * source/provider: Provider is nullable and ONLY ever set for
 * THIRD_PARTY -- confirmed with Michael, CAA_LECTURE never needs one,
 * since every self-paced certificate bears Joseph Spivey's signature
 * (the answer to "who taught this" is inherently "us," not a lookup).
 *
 * completionDate is staff-entered, not the upload timestamp -- the
 * date actually printed on the certificate itself (confirmed with
 * Michael), which is what becomes Student.lectureCompletionDate.
 */
@Entity
@Table(name = "lecture_certificates")
@Getter
@Setter
@NoArgsConstructor
public class LectureCertificate extends AuditableEntity {

    public enum Source { CAA_LECTURE, THIRD_PARTY }

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "student_id", nullable = false)
    private Student student;

    @Enumerated(EnumType.STRING)
    @JdbcTypeCode(SqlTypes.NAMED_ENUM)
    @Column(nullable = false)
    private Source source;

    /** Nullable -- only ever set when source is THIRD_PARTY. */
    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "provider_id")
    private Provider provider;

    /** The date printed on the certificate itself, staff-entered -- not the upload timestamp. */
    @Column(name = "completion_date", nullable = false)
    private LocalDate completionDate;

    @Column(name = "file_path", nullable = false)
    private String filePath;

    /** The file's real name as uploaded, for display/download -- filePath itself is an internal, timestamp-based name. */
    @Column(name = "original_filename")
    private String originalFilename;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "uploaded_by_staff_id", nullable = false)
    private StaffUser uploadedBy;

    @Column(name = "uploaded_at", nullable = false)
    private OffsetDateTime uploadedAt = OffsetDateTime.now();

    /**
     * Michael, 2026-08-30 -- true once a later upload replaces this as
     * the student's current certificate. Never deleted, never
     * overwritten -- this flag is the only thing that changes.
     */
    @Column(nullable = false)
    private boolean superseded = false;
}
