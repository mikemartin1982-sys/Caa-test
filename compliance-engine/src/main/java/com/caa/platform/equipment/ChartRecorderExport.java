package com.caa.platform.equipment;

import com.caa.platform.session.Session;
import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;

import java.time.OffsetDateTime;

/**
 * Section 4h: the .ZIP export Chart Recorder emails at school completion.
 * Retention + verification artifact (Chasity confirms equipment/5-Filter
 * validity) + issue/delay notes ("No Issues" if none). NOT the live data
 * path -- Stacktest.net is (Section 4g). No automated parsing required;
 * this stays a manual, email-based process.
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

    /**
     * Comma-separated recipient list. At minimum Chasity Miranda; operator
     * self-sending is common practice, not required (Section 4h).
     */
    @Column(name = "recipients")
    private String recipients;

    @Column(name = "issue_delay_notes", nullable = false)
    private String issueDelayNotes = "No Issues";
}
