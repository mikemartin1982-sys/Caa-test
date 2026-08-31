package com.caa.platform.common;
 
import jakarta.persistence.Column;
import jakarta.persistence.MappedSuperclass;
import jakarta.persistence.PrePersist;
import jakarta.persistence.PreUpdate;
import lombok.Getter;
import lombok.Setter;
 
import java.time.OffsetDateTime;
 
/**
 * Shared created_at / updated_at columns, matching the pattern used across
 * most tables in the schema (see db/migrations).
 *
 * Uses plain JPA @PrePersist/@PreUpdate lifecycle callbacks rather than
 * Spring Data's @CreatedDate/@LastModifiedDate + @EnableJpaAuditing.
 * That mechanism goes through a ConversionService with a FIXED set of
 * supported target types -- LocalDateTime, LocalDate, LocalTime, Instant,
 * java.util.Date, Long -- and OffsetDateTime is NOT among them, despite
 * being a perfectly normal JPA column type. It fails at runtime, not
 * compile time, only once an entity actually gets persisted, with:
 * "Cannot convert unsupported date type java.time.LocalDateTime to
 * java.time.OffsetDateTime." Plain lifecycle callbacks sidestep the
 * conversion service entirely and match the OffsetDateTime.now() default
 * pattern already used by every other entity in this codebase that
 * doesn't extend this class (Trailer, CalibrationPane, etc.).
 */
@Getter
@Setter
@MappedSuperclass
public abstract class AuditableEntity {
 
    @Column(name = "created_at", nullable = false, updatable = false)
    private OffsetDateTime createdAt;
 
    @Column(name = "updated_at")
    private OffsetDateTime updatedAt;
 
    @PrePersist
    protected void onCreate() {
        OffsetDateTime now = OffsetDateTime.now();
        this.createdAt = now;
        this.updatedAt = now;
    }
 
    @PreUpdate
    protected void onUpdate() {
        this.updatedAt = OffsetDateTime.now();
    }
}