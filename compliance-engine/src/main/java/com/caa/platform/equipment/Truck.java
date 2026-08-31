package com.caa.platform.equipment;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;

import java.time.OffsetDateTime;

/**
 * Section 4 TEAM: each Truck is individually identified (e.g. "Metris")
 * and typically pairs with one specific {@link Trailer} (e.g. "Metris"
 * pairs with "Abby") -- pairedTrailer is that DEFAULT/typical pairing,
 * not a hard constraint, since a session assigns Truck and Trailer
 * independently (see Session.truck / Session.trailer), matching DIBs'
 * own separate sess_truck/sess_trailer dropdowns.
 */
@Entity
@Table(name = "trucks")
@Getter
@Setter
@NoArgsConstructor
public class Truck {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Column(nullable = false, unique = true)
    private String identifier;

    /**
     * Michael, 2026-08-29 -- staff calendar, Phase 1. The calendar
     * needs a short code (e.g. "V21"), not the long identifier ("Metris")
     * used everywhere else -- confirmed as matching DIBs' own existing
     * convention exactly, same reasoning as StaffUser.initials.
     */
    @Column(name = "short_code", length = 10)
    private String shortCode;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "paired_trailer_id")
    private Trailer pairedTrailer;

    @Column(name = "created_at", nullable = false, updatable = false)
    private OffsetDateTime createdAt = OffsetDateTime.now();
}
