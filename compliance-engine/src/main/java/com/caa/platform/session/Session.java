package com.caa.platform.session;

import com.caa.platform.common.AuditableEntity;
import com.caa.platform.common.RegionType;
import com.caa.platform.certification.PlumeColor;
import com.caa.platform.equipment.Trailer;
import com.caa.platform.equipment.Truck;
import com.caa.platform.staff.StaffUser;
import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;
import org.hibernate.annotations.JdbcTypeCode;
import org.hibernate.type.SqlTypes;

import java.math.BigDecimal;
import java.time.OffsetDateTime;

/**
 * Section 3: the central scheduling entity. No hard capacity cap (Section 4)
 * -- only the Texas proctor ratio constrains headcount, evaluated per
 * {@link StaggeredArrivalBlock}, not total enrollment.
 */
@Entity
@Table(name = "sessions")
@Getter
@Setter
@NoArgsConstructor
public class Session extends AuditableEntity {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Enumerated(EnumType.STRING)
    @JdbcTypeCode(SqlTypes.NAMED_ENUM)
    @Column(name = "school_type", nullable = false)
    private SchoolType schoolType;

    @Enumerated(EnumType.STRING)
    @JdbcTypeCode(SqlTypes.NAMED_ENUM)
    @Column(nullable = false)
    private SessionFormat format;

    // --- Region: auto-derived from address; staff can override (logged) ---
    @Enumerated(EnumType.STRING)
    @JdbcTypeCode(SqlTypes.NAMED_ENUM)
    private RegionType region;

    @Column(name = "region_overridden", nullable = false)
    private boolean regionOverridden = false;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "region_override_by")
    private StaffUser regionOverrideBy;

    @Column(name = "region_override_at")
    private OffsetDateTime regionOverrideAt;

    // --- Location (Section 4d) ---
    @Column(name = "location_name")
    private String locationName;

    @Column(name = "address_street")
    private String addressStreet;

    @Column(name = "address_city")
    private String addressCity;

    @Column(name = "address_state", length = 2)
    private String addressState;

    @Column(name = "address_zip")
    private String addressZip;

    @Column(name = "grid_lat", precision = 10, scale = 6)
    private BigDecimal gridLat;

    @Column(name = "grid_lng", precision = 10, scale = 6)
    private BigDecimal gridLng;

    /** Computed on save; warn if > 0.5 mi (Section 4d). */
    @Column(name = "grid_pin_distance_miles", precision = 6, scale = 2)
    private BigDecimal gridPinDistanceMiles;

    // ---- FIELD section remaining fields (real DIBs source, 2026-08-16) ----

    /** Abbreviation only (EST/CST/MST/PST), matching DIBs' own convention. IS copied forward. */
    @Column(name = "field_timezone", length = 3)
    private String fieldTimezone;

    /** On-site contact for this field location -- distinct from externalRegistrationName (registration, not on-site logistics). IS copied forward. */
    @Column(name = "field_contact", length = 100)
    private String fieldContact;

    @Column(name = "field_contact_phone", length = 30)
    private String fieldContactPhone;

    /**
     * The actual field TESTING site -- confirmed genuinely distinct from
     * the general locationName/addressStreet/etc. fields above (which
     * represent DIBs' "School" concept: the public-facing display
     * location, used in certificates/emails/comparisons). Corrects an
     * earlier assumption here that conflated the two. IS copied forward.
     */
    @Column(name = "field_facility", length = 64)
    private String fieldFacility;

    @Column(name = "field_address", length = 63)
    private String fieldAddress;

    @Column(name = "field_city", length = 31)
    private String fieldCity;

    @Column(name = "field_state", length = 2)
    private String fieldState;

    @Column(name = "field_zip", length = 15)
    private String fieldZip;

    @Column(name = "field_lat", precision = 10, scale = 6)
    private BigDecimal fieldLat;

    @Column(name = "field_lng", precision = 10, scale = 6)
    private BigDecimal fieldLng;

    // ---- SCHOOL INFO section (real DIBs source, 2026-08-16) ----
    // Note: School Address/City/State/Zip/Lat/Long need NO new fields
    // here -- they're the existing generic addressStreet/addressCity/
    // addressState/addressZip/gridLat/gridLng above (school_name's
    // public-display semantics match locationName's real usage
    // throughout this codebase -- see certificates/emails/comparisons).

    /** When checked, school info mirrors the Client's own info rather than being entered independently. IS copied forward. */
    @Column(name = "use_client_info", nullable = false)
    private boolean useClientInfo = false;

    /** VTCA registration link or livestream (MS-Teams) meeting URL. NOT copied forward -- often specific to one occurrence. */
    @Column(name = "school_url", length = 100)
    private String schoolUrl;

    /** Optional broader geographic area, shown for Semi-Private/Proposed sessions on the public calendar. IS copied forward. */
    @Column(name = "school_geo_area", length = 100)
    private String schoolGeoArea;

    /**
     * A proper boolean, NOT DIBs' text-hack convention (typing
     * "canceled" into the school name) -- confirmed with Michael.
     * NOT copied forward (a new copy is never canceled by default).
     */
    @Column(nullable = false)
    private boolean canceled = false;

    // --- Private / Semi-Private pricing (Section 3a) ---
    @Column(name = "quoted_headcount")
    private Integer quotedHeadcount;

    /**
     * Michael, 2026-08-19 -- renamed from overageRatePerPerson. One
     * field, three uses depending on schoolType (confirmed with
     * Michael, not enforced in code -- this is a single free-entry
     * number staff set appropriately per session):
     *   - PUBLIC: the standard per-person rate (management-set,
     *     typically $275).
     *   - PRIVATE: the overage rate per person beyond the flat-rate
     *     15 covered by privateCost below.
     *   - SEMI_PRIVATE: the per-person rate WITH the discount already
     *     included by whoever sets it (e.g. $225 instead of $275) --
     *     replaces a separate discount-multiplier field (previously
     *     bidDiscountFieldForPublic, now removed) entirely.
     */
    @Column(name = "field_test", precision = 8, scale = 2)
    private BigDecimal fieldTest;

    /**
     * Michael, 2026-08-19 -- flat rate for a Private Session, covering
     * up to 15 attendees. Beyond 15, fieldTest above is the per-person
     * overage rate.
     */
    @Column(name = "private_cost", precision = 10, scale = 2)
    private BigDecimal privateCost;

    // --- Public pricing (Section 3a) -- management-set fixed rates ---
    @Column(name = "field_certification_price", precision = 8, scale = 2)
    private BigDecimal fieldCertificationPrice;

    @Column(name = "self_paced_lecture_price", precision = 8, scale = 2)
    private BigDecimal selfPacedLecturePrice;

    // Michael, 2026-08-31 -- lateFeeAmount/lateFeeDayThreshold removed
    // (migration 038) -- confirmed with Michael the late fee shouldn't
    // be present in Session Details at all; it's now assessed at
    // enrollment time via SessionDay, using fixed constants, in
    // EnrollmentPricingService instead.
    @Column(name = "external_registration_name")
    private String externalRegistrationName;

    @Column(name = "external_registration_phone")
    private String externalRegistrationPhone;

    @Column(name = "external_registration_notes")
    private String externalRegistrationNotes;

    @Column(name = "public_session_notes")
    private String publicSessionNotes;

    @Column(name = "po_number")
    private String poNumber;

    @Column(name = "net_terms_days")
    private Integer netTermsDays;

    /**
     * Section 4c: independent lifecycle flags, NOT stages of one status --
     * confirmed by real DIBs usage. A session can be published AND
     * confirmed AND closedOut simultaneously (closedOut is set well
     * after confirmation, once billing wraps up). Corrects an earlier
     * design here that wrongly consolidated these into a single
     * mutually-exclusive SessionStatus enum.
     */
    @Column(nullable = false)
    private boolean confirmed = false;

    /**
     * Required accompanying note when confirmed is set -- DIBs enforces
     * this at the UI/JS layer ("you must enter a comment... to explain"),
     * not a DB constraint here, matching the app-layer-not-DB-layer
     * pattern used elsewhere in this schema (see db/README.md).
     */
    @Column(name = "confirmed_comment", columnDefinition = "TEXT")
    private String confirmedComment;

    /**
     * Set once billing is fully wrapped up (Chasity Miranda's role) --
     * disables further edits to the session. Independent of confirmed;
     * a session is normally confirmed long before it's closed out.
     */
    @Column(name = "closed_out", nullable = false)
    private boolean closedOut = false;

    /** Section 4/Private-School-Bid: removes session from outstanding-bids list. */
    @Column(name = "bid_lost", nullable = false)
    private boolean bidLost = false;

    /** Required when bidLost is set, per DIBs' own UI enforcement -- aids future bids. */
    @Column(name = "bid_lost_reason", columnDefinition = "TEXT")
    private String bidLostReason;

    // ---- PRIVATE SCHOOL BID AND INVOICE section (real DIBs source, 2026-08-16) ----
    // Per Michael: full schema, bid generation itself doesn't need to
    // function yet. "Last Website Invoice Sent" excluded -- DIBs' own
    // tooltip confirms it's dead ("we use QBO invoices only").

    /** NOT copied forward -- occurrence-specific negotiation notes. */
    @Column(name = "bid_extra_details_lecture", columnDefinition = "TEXT")
    private String bidExtraDetailsLecture;

    @Column(name = "bid_extra_details_field", columnDefinition = "TEXT")
    private String bidExtraDetailsField;

    @Column(name = "bid_caa_notes", columnDefinition = "TEXT")
    private String bidCaaNotes;

    /** IS copied forward -- reasonable starting estimate for a recurring client's next occurrence. */
    @Column(name = "bid_num_selfpaced_lecture_attendees")
    private Integer bidNumSelfpacedLectureAttendees;

    @Column(name = "bid_num_inperson_lecture_attendees")
    private Integer bidNumInpersonLectureAttendees;

    @Column(name = "bid_num_field_attendees")
    private Integer bidNumFieldAttendees;

    // bidDiscountFieldForPublic removed (Michael, 2026-08-19) --
    // Semi-Private now sets fieldTest directly to the discounted rate
    // instead of applying a separate multiplier. See fieldTest's
    // Javadoc above.

    // Standing client billing requirements -- IS copied forward.
    @Column(name = "bid_need_po_upfront", nullable = false)
    private boolean bidNeedPoUpfront = false;

    @Column(name = "bid_no_public_allowed", nullable = false)
    private boolean bidNoPublicAllowed = false;

    @Column(name = "bid_requires_cert_of_completion", nullable = false)
    private boolean bidRequiresCertOfCompletion = false;

    @Column(name = "bid_no_addons", nullable = false)
    private boolean bidNoAddons = false;

    @Column(name = "bid_addons_require_change_order", nullable = false)
    private boolean bidAddonsRequireChangeOrder = false;

    /** IS copied forward -- often a standing preference. */
    // bidLunchOption/bidLunchCost removed (Michael, 2026-08-19) -- "we
    // have not done a lunch option at a client site since I have
    // worked here."

    /** NOT copied forward -- a new bid cycle starts fresh at 0. */
    @Column(name = "bid_revision_number", nullable = false)
    private int bidRevisionNumber = 0;

    /** NOT copied forward -- a new occurrence needs a freshly considered message, not a stale one. */
    @Column(name = "bid_email_message", columnDefinition = "TEXT")
    private String bidEmailMessage;

    /** NOT copied forward -- a new PO would need to be issued for the new occurrence. */
    @Column(name = "po_for_invoice", length = 100)
    private String poForInvoice;

    // "Last generated/sent" timestamps -- NOT copied forward. Historical
    // record of THIS session's actual bid/invoice activity; a copied
    // session hasn't had its own bid generated yet.
    @Column(name = "last_bid_generated_at")
    private OffsetDateTime lastBidGeneratedAt;

    @Column(name = "last_bid_sent_at")
    private OffsetDateTime lastBidSentAt;

    /**
     * Michael, 2026-08-19 -- e.g. "JDS-251202-R1"
     * ([StaffInitials]-[YYMMDD]-R[bidRevisionNumber]), matching the real
     * bid template's Quote Number. Set by BidPdfService.generate() each
     * time a bid is generated; NOT copied forward, since a copied
     * session hasn't had its own bid generated yet.
     */
    @Column(name = "bid_quote_number", length = 50)
    private String bidQuoteNumber;

    /**
     * Michael, 2026-08-19 -- mirrors Certification.pdfCertificateLink's
     * established pattern: file path stored on the record, served via
     * a dedicated download endpoint (see SessionController.downloadBidPdf()),
     * rather than storing PDF bytes in the database. NOT copied forward.
     */
    @Column(name = "last_bid_pdf_path", length = 500)
    private String lastBidPdfPath;

    @Column(name = "last_qbo_invoice_generated_at")
    private OffsetDateTime lastQboInvoiceGeneratedAt;

    /** An invoice NUMBER (from the QBO app URL per DIBs' own tooltip), not a timestamp. */
    @Column(name = "last_qbo_invoice_sent_number", length = 20)
    private String lastQboInvoiceSentNumber;

    /**
     * Michael, 2026-09-04 -- session close-out billing redesign. The
     * real, internal QBO Invoice Id -- genuinely different from
     * lastQboInvoiceSentNumber above (the human-facing DocNumber) --
     * needed specifically to call QBO's own real "send" endpoint
     * (/invoice/{invoiceId}/send), which requires the internal Id, not
     * the DocNumber. Confirmed dead until now -- QBO always returned
     * this at invoice-creation time, it was just never actually
     * stored on this entity before.
     */
    @Column(name = "last_qbo_invoice_id", length = 20)
    private String lastQboInvoiceId;

    /**
     * Publish gate (Section 4c): available only once required fields are
     * complete (locationName, address, pricing, GPS). Sticky once true --
     * see {@link SessionPublishGateService}.
     */
    @Column(nullable = false)
    private boolean published = false;

    @Column(name = "proctors_assigned", nullable = false)
    private int proctorsAssigned = 1;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "field_manager_id")
    private StaffUser fieldManager;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "operator_id")
    private StaffUser operator;

    // ---- TEAM section remaining fields (real DIBs source, 2026-08-16) ----

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "proctor1_id")
    private StaffUser proctor1;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "proctor2_id")
    private StaffUser proctor2;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "proctor3_id")
    private StaffUser proctor3;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "truck_id")
    private Truck truck;

    /** Session-level trailer assignment -- distinct from CertificationRun's own trailer reference (per-run, not per-session). */
    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "trailer_id")
    private Trailer trailer;

    /** Plain string for now -- Michael: "can be hashed out later... but the field should be present." NOT the structured parking/home/service/airport model DIBs uses. NOT copied forward (post-session plan is re-decided each time). */
    @Column(name = "staged_location")
    private String stagedLocation;

    // ---- Live Digital Testing state (real interaction model, Michael, 2026-08-16) ----
    // Session-level shared state: one live point/true-value shared by
    // the whole class watching the same smoke -- NOT per-CertificationRun.
    // See LiveTestingService for the actual coordination logic.

    @Column(name = "live_test_active", nullable = false)
    private boolean liveTestActive = false;

    @Column(name = "live_test_point_number")
    private Short liveTestPointNumber;

    @Enumerated(EnumType.STRING)
    @JdbcTypeCode(SqlTypes.NAMED_ENUM)
    @Column(name = "live_test_color")
    private PlumeColor liveTestColor;

    /** Set by the Operator via their tablet for the CURRENT point -- once set, students can submit their own guess against it. */
    @Column(name = "live_test_true_opacity")
    private Short liveTestTrueOpacity;

    /**
     * A field-real scenario (Michael, 2026-08-17): a student's answer on
     * a PAST point was outside allowable deviation, and the Operator
     * needs to reopen that point so they can correct it -- without
     * disrupting the live test's actual forward progress, which may be
     * many points ahead already. Deliberately separate from
     * liveTestPointNumber/Color/TrueOpacity above -- those represent
     * the real, ongoing forward progress and stay completely untouched
     * while a revisit is active. No new true value gets recorded here
     * -- the original one is already on file; see LiveTestingService's
     * revisitPoint()/endRevisit() for the full mechanic.
     */
    @Column(name = "live_test_revisit_point_number")
    private Short liveTestRevisitPointNumber;

    // ---- Digital Testing onsite stage control (real DIBs source + Michael, 2026-08-16) ----

    /** Field Manager control -- when true, students can look up this session and sign in. NOT copied forward (a fresh copy always starts closed to sign-in). */
    @Column(name = "onsite_sign_in_enabled", nullable = false)
    private boolean onsiteSignInEnabled = false;

    @Column(name = "onsite_testing_enabled", nullable = false)
    private boolean onsiteTestingEnabled = false;

    /**
     * Computed, not stored -- see OnsiteStage's Javadoc for the full
     * priority logic. Exposed to JSON automatically as "onsiteStage"
     * (standard Jackson bean-getter convention), so API consumers (the
     * student-facing onsite pages, an eventual Field Manager control
     * panel) never have to reimplement this inference themselves.
     */
    public OnsiteStage getOnsiteStage() {
        if (closedOut) return OnsiteStage.CLOSED;
        // Both true is otherwise an unused combination -- repurposed as
        // "Field Manager explicitly marked Digital Testing done,"
        // independent of the session's own Closed Out/billing-lock flag
        // above. Confirmed with Michael, 2026-08-16: these are
        // deliberately different concepts -- marking testing done here
        // must NOT touch billing lock or disable Session Details edits.
        if (onsiteSignInEnabled && onsiteTestingEnabled) return OnsiteStage.CLOSED;
        if (onsiteSignInEnabled) return OnsiteStage.SIGN_IN;
        if (onsiteTestingEnabled) return OnsiteStage.TESTING;
        return OnsiteStage.PRACTICE;
    }

    // ---- GENERAL section remaining fields (real DIBs source, 2026-08-16) ----

    /** Overrides the dashboard "needs a copy" warning. Registrar/highest-access only in DIBs; not enforced at this layer yet. NOT copied forward. */
    @Column(name = "not_need_copy", nullable = false)
    private boolean notNeedCopy = false;

    @Column(name = "not_need_copy_why", columnDefinition = "TEXT")
    private String notNeedCopyWhy;

    /** QuickBooks-assigned class reference ID (Section 7, still blocked on QBO credentials -- storage slot only). NOT copied forward. */
    @Column(name = "qbo_class_ref_id", length = 50)
    private String qboClassRefId;

    @Column(name = "session_info_verified", nullable = false)
    private boolean sessionInfoVerified = false;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "session_info_verified_by")
    private StaffUser sessionInfoVerifiedBy;

    @Column(name = "session_info_verified_at")
    private OffsetDateTime sessionInfoVerifiedAt;

    /** Staff member responsible for this session's info accuracy -- distinct from who last verified it. IS copied forward. */
    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "session_info_owner")
    private StaffUser sessionInfoOwner;

    /** Semi-Private only in practice (see db/README.md on app-layer vs DB-layer rules). IS copied forward. */
    @Column(name = "advertise_semi_priv_as_public", nullable = false)
    private boolean advertiseSemiPrivateAsPublic = false;

    /** Enables arrival-time assignment on the roster page (not yet built). IS copied forward. */
    @Column(name = "staggered_arrival_times", nullable = false)
    private boolean staggeredArrivalTimes = false;

    /** Internal staff notes -- never appears on any calendar. NOT copied forward. */
    @Column(name = "session_log", columnDefinition = "TEXT")
    private String sessionLog;

    /** "Comments2" in DIBs -- admin-calendar-only, distinct from publicSessionNotes (which appears on the public calendar). IS copied forward. */
    @Column(name = "admin_comments", columnDefinition = "TEXT")
    private String adminComments;

    /**
     * Delivery-method modifier, NOT a school type -- confirmed against
     * DIBs' real Session Type dropdown (Public/Private/Semi-Private/
     * Proposed/VTCA, no VR option at all). Applies to either PUBLIC (the
     * common case) or PRIVATE (bulk-purchase clients), per Michael's
     * description. Renamed from isPublicVrSession, whose narrower
     * "Public-only" framing didn't match reality.
     */
    @Column(name = "vr_session", nullable = false)
    private boolean vrSession = false;

    /** "Copy Forward 6 Months" provenance (Section 4c). */
    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "copied_from_session_id")
    private Session copiedFromSession;
}
