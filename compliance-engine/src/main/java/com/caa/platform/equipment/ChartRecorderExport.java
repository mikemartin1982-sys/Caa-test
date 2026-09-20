package com.caa.platform.equipment;

import com.caa.platform.session.Session;
import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;
import org.hibernate.annotations.JdbcTypeCode;
import org.hibernate.type.SqlTypes;

import java.time.OffsetDateTime;

/**
 * Immutable Chart Recorder export retained against its Smoke School session.
 * Older rows describe the historical emailed ZIP workflow. Tablet document
 * uploads additionally populate the transmission, document, checksum, and
 * measurement fields so retries can be acknowledged without storing a second
 * copy of the same exact artifact.
 */
@Entity
@Table(name = "chart_recorder_exports")
@Getter
@Setter
@NoArgsConstructor
public class ChartRecorderExport {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "session_id", nullable = false)
    private Session session;

    @Column(name = "generated_at", nullable = false)
    private OffsetDateTime generatedAt = OffsetDateTime.now();

    @Column(name = "zip_file_reference")
    private String zipFileReference;

    @Column(name = "transmission_id", unique = true, length = 100)
    private String transmissionId;

    @Column(name = "document_id", length = 100)
    private String documentId;

    @Column(name = "payload_sha256", columnDefinition = "char(64)")
    @JdbcTypeCode(SqlTypes.CHAR)
    private String payloadSha256;

    @Column(name = "measurement_count")
    private Integer measurementCount;

    @Column(name = "interrupted")
    private Boolean interrupted;

    @Column(name = "original_filename", length = 255)
    private String originalFilename;

    /**
     * Comma-separated recipient list. At minimum Chasity Miranda; operator
     * self-sending is common practice, not required (Section 4h).
     */
    @Column(name = "recipients")
    private String recipients;

    @Column(name = "issue_delay_notes", nullable = false)
    private String issueDelayNotes = "No Issues";
}
