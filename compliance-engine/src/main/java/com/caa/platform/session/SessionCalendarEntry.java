package com.caa.platform.session;

import com.caa.platform.common.RegionType;

import java.math.BigDecimal;
import java.time.LocalDate;

/**
 * Section 4c-adjacent: staff-facing calendar view needs a session's
 * actual scheduled date, which Session itself doesn't carry directly
 * (that lives on SessionDay, a separate table -- a session can have
 * several days, this surfaces the earliest one). Unlike the public
 * calendar (Public + published only), this covers every school type,
 * since staff need visibility into Private/Semi-Private/VR too.
 *
 * mapLat/mapLng/mapAddress ("Map It" quick-link, 2026-08-16): prefer the
 * actual Field testing-site location over the general School display
 * location (locationName/etc. above) -- Field is where staff physically
 * need to go, School is the public-facing name/address, and per Michael
 * they're often but not always the same physical site. Falls back to
 * School's GPS/address if Field isn't set for this session yet.
 *
 * Michael, 2026-08-29 -- staff calendar, Phase 1. One entry per actual
 * SessionDay now, not one per session (a multi-day session -- like
 * Houston's own 2-day example Michael confirmed -- shows a real card
 * on EVERY day it spans, not just its first). isPrimaryDay distinguishes
 * the one editable occurrence (a session's earliest day -- team/
 * equipment assignments live on Session itself, not per-day, so there's
 * only ever one real, editable set) from the faded, read-only echoes
 * shown on its later days, confirmed with Michael as showing the same
 * underlying data, not independently editable.
 *
 * Enrollment counts are explicit, separately-named fields rather than
 * generic positional slots (matching DIBs' own "same two positions,
 * different meaning depending on state" display) -- confirmed with
 * Michael: onsiteTestingEnabled is the actual signal for which set is
 * meaningful, not a fresh timezone calculation. lectureEnrolledCount/
 * fieldEnrolledCount are meaningful before testing starts; certifiedCount/
 * dncCount/dnaCount become meaningful once it has. Explicit fields (not
 * generic countLeft/countMiddle/countRight slots) mean a caller doesn't
 * have to cross-reference onsiteTestingEnabled just to know what a
 * number means at all.
 */
public record SessionCalendarEntry(
        Long id,
        SchoolType schoolType,
        String locationName,
        RegionType region,
        boolean published,
        boolean confirmed,
        boolean closedOut,
        boolean bidLost,
        boolean canceled,
        boolean vrSession,
        LocalDate date,
        boolean isPrimaryDay,
        BigDecimal mapLat,
        BigDecimal mapLng,
        String mapAddress,

        String fieldManagerInitials,
        String operatorInitials,
        String proctor1Initials,
        String proctor2Initials,
        String proctor3Initials,
        String truckShortCode,
        String trailerShortCode,

        /**
         * Michael, 2026-08-29 -- staff calendar, Phase 6 (drag-and-drop).
         * Found while building the frontend: initials/short codes above
         * are display-only strings -- there was no way to actually
         * submit a change to CalendarAssignmentService without the real
         * underlying id, since a drop target needs to know WHICH staff
         * member/truck/trailer it just received, not just their label.
         * Nullable -- a null id with a non-null initials string would be
         * a real, meaningful bug (display data with nothing to act on),
         * not an expected combination.
         */
        Long fieldManagerId,
        Long operatorId,
        Long proctor1Id,
        Long proctor2Id,
        Long proctor3Id,
        Long truckId,
        Long trailerId,

        boolean onsiteTestingEnabled,
        Integer lectureEnrolledCount,
        Integer fieldEnrolledCount,
        Integer certifiedCount,
        Integer dncCount,
        Integer dnaCount,

        String sessionInfoOwnerName,
        String confirmedComment,

        /**
         * Michael, 2026-08-29 -- staff calendar, Phase 3 (status/color
         * logic). Computed here, server-side, from the real DIBs legend
         * markup itself (not guessed at) -- textColor/backgroundColor
         * are explicit hex values the frontend just renders directly,
         * not an enum or code the frontend has to interpret into a
         * color itself. strikethrough is a single boolean covering both
         * of DIBs' own underlying mechanisms (a plain line-through for
         * Unpublished, a separate "x_out" class for Canceled/Lost Bid)
         * -- the end visual result is identical either way, so there's
         * no reason to expose which specific condition triggered it.
         */
        String textColor,
        String backgroundColor,
        boolean strikethrough
) {}
