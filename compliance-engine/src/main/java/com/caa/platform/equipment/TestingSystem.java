package com.caa.platform.equipment;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;
import org.hibernate.annotations.JdbcTypeCode;
import org.hibernate.type.SqlTypes;

import java.time.OffsetDateTime;

/**
 * Section 4h: Primary + Secondary per Trailer. Each component has a
 * distinct identifier scheme -- Light Source: numerical, Photo Cell: alpha
 * (+ an internal Op-Amp Card with its own separate ID), Data Source:
 * 3-digit numerical, Monitor: scheme not yet specified (Section 9 open item).
 */
@Entity
@Table(name = "testing_systems")
@Getter
@Setter
@NoArgsConstructor
public class TestingSystem {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    @JoinColumn(name = "trailer_id", nullable = false)
    private Trailer trailer;

    @Enumerated(EnumType.STRING)
    @JdbcTypeCode(SqlTypes.NAMED_ENUM)
    @Column(nullable = false)
    private SystemDesignation designation;

    @Column(name = "light_source_id")
    private String lightSourceId;

    @Column(name = "photo_cell_id")
    private String photoCellId;

    /** Distinct sub-component identifier, housed inside the Photo Cell -- not the Photo Cell's own ID. */
    @Column(name = "op_amp_card_id")
    private String opAmpCardId;

    @Column(name = "data_source_id", length = 3)
    private String dataSourceId;

    /** Identifier scheme not yet specified -- Section 9 open item. */
    @Column(name = "monitor_id")
    private String monitorId;

    @Column(name = "created_at", nullable = false, updatable = false)
    private OffsetDateTime createdAt = OffsetDateTime.now();
}
