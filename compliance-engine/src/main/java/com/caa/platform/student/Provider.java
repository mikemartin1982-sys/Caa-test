package com.caa.platform.student;

import com.caa.platform.common.AuditableEntity;
import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;

/**
 * Michael, 2026-08-30 -- Lecture Certificate Upload feature. A
 * standalone lookup of third-party lecture providers (competitors,
 * state-driven programs) -- confirmed against real DIBs source
 * Michael shared: each entry is just a short-form name with an
 * active/inactive flag, no login or user id at all (DIBs' own screen
 * shows this literally as "n/a" per row -- these aren't staff
 * accounts, just labels).
 *
 * Deliberately its own, separate table, not sharing StaffUser's the
 * way DIBs' own source appears to (its "xx" column looks like a
 * hard-coded exclusion flag keeping these out of the real
 * instructor-assignment list) -- confirmed with Michael as the right
 * call rather than copying that coupling without noticing it.
 *
 * Only ever relevant to the uploaded/third-party lecture path --
 * CAA's own self-paced lecture never needs one of these, since every
 * self-paced certificate bears Joseph Spivey's signature, confirmed
 * with Michael. Adding a Provider is restricted to Compliance
 * Administrators (enforced in LectureCertificateController, not here
 * -- this entity has no opinion about who's allowed to create one).
 */
@Entity
@Table(name = "providers")
@Getter
@Setter
@NoArgsConstructor
public class Provider extends AuditableEntity {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Column(nullable = false)
    private String name;

    @Column(nullable = false)
    private boolean active = true;
}
