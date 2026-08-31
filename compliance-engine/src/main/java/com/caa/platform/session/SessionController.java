package com.caa.platform.session;

import com.caa.platform.client.Client;
import com.caa.platform.client.ClientRepository;
import com.caa.platform.common.RegionType;
import com.caa.platform.enrollment.Enrollment;
import com.caa.platform.enrollment.EnrollmentComponents;
import com.caa.platform.enrollment.EnrollmentRepository;
import com.caa.platform.enrollment.RosterService;
import com.caa.platform.enrollment.RosterStatus;
import com.caa.platform.enrollment.SessionCloseOutService;
import com.caa.platform.enrollment.VrEnrollmentEligibilityService;
import com.caa.platform.staff.StaffUser;
import com.caa.platform.staff.StaffUserRepository;
import com.caa.platform.student.StudentRepository;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.data.jpa.domain.Specification;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.web.bind.annotation.*;

import java.time.LocalDate;
import java.time.LocalTime;
import java.time.OffsetDateTime;
import java.util.List;
import java.util.Map;

/**
 * Backs the /sessions endpoints in api-contract/openapi.yaml.
 */
@RestController
@RequestMapping("/api/v1/sessions")
public class SessionController {

    private static final Logger log = LoggerFactory.getLogger(SessionController.class);

    private final SessionRepository sessionRepository;
    private final SessionAuthorizedClientRepository authorizedClientRepository;
    private final SessionCommentRepository sessionCommentRepository;
    private final ClientRepository clientRepository;
    private final StaffUserRepository staffUserRepository;
    private final SessionAuthorizationService authorizationService;
    private final SessionPublishGateService publishGateService;
    private final SessionCopyForwardService copyForwardService;
    private final EnrollmentRepository enrollmentRepository;
    private final SessionCloseOutService closeOutService;
    private final RosterService rosterService;
    private final SessionDayRepository sessionDayRepository;
    private final SessionNotifiedClientRepository notifiedClientRepository;
    private final com.caa.platform.equipment.TruckRepository truckRepository;
    private final com.caa.platform.equipment.TrailerRepository trailerRepository;
    private final SessionConfirmationEmailService confirmationEmailService;
    private final BidPdfService bidPdfService;
    private final SessionPoResolutionService poResolutionService;
    private final VrEnrollmentEligibilityService vrEligibilityService;
    private final com.caa.platform.integration.qbo.QboClassSyncService qboClassSyncService;
    private final com.caa.platform.integration.qbo.QboInvoiceService qboInvoiceService;
    private final CalendarAssignmentService calendarAssignmentService;
    private final StudentRepository studentRepository;

    public SessionController(SessionRepository sessionRepository,
                              SessionAuthorizedClientRepository authorizedClientRepository,
                              SessionCommentRepository sessionCommentRepository,
                              ClientRepository clientRepository,
                              StaffUserRepository staffUserRepository,
                              SessionAuthorizationService authorizationService,
                              SessionPublishGateService publishGateService,
                              SessionCopyForwardService copyForwardService,
                              EnrollmentRepository enrollmentRepository,
                              SessionCloseOutService closeOutService,
                              RosterService rosterService,
                              SessionDayRepository sessionDayRepository,
                              SessionNotifiedClientRepository notifiedClientRepository,
                              com.caa.platform.equipment.TruckRepository truckRepository,
                              com.caa.platform.equipment.TrailerRepository trailerRepository,
                              SessionConfirmationEmailService confirmationEmailService,
                              BidPdfService bidPdfService,
                              SessionPoResolutionService poResolutionService,
                              VrEnrollmentEligibilityService vrEligibilityService,
                              com.caa.platform.integration.qbo.QboClassSyncService qboClassSyncService,
                              com.caa.platform.integration.qbo.QboInvoiceService qboInvoiceService,
                              CalendarAssignmentService calendarAssignmentService,
                              StudentRepository studentRepository) {
        this.sessionRepository = sessionRepository;
        this.authorizedClientRepository = authorizedClientRepository;
        this.sessionCommentRepository = sessionCommentRepository;
        this.clientRepository = clientRepository;
        this.staffUserRepository = staffUserRepository;
        this.authorizationService = authorizationService;
        this.publishGateService = publishGateService;
        this.copyForwardService = copyForwardService;
        this.enrollmentRepository = enrollmentRepository;
        this.closeOutService = closeOutService;
        this.rosterService = rosterService;
        this.sessionDayRepository = sessionDayRepository;
        this.notifiedClientRepository = notifiedClientRepository;
        this.truckRepository = truckRepository;
        this.trailerRepository = trailerRepository;
        this.confirmationEmailService = confirmationEmailService;
        this.bidPdfService = bidPdfService;
        this.poResolutionService = poResolutionService;
        this.vrEligibilityService = vrEligibilityService;
        this.qboClassSyncService = qboClassSyncService;
        this.qboInvoiceService = qboInvoiceService;
        this.calendarAssignmentService = calendarAssignmentService;
        this.studentRepository = studentRepository;
    }

    /**
     * GET /sessions -- filterable by schoolType/region/published (Section
     * 4e Public Calendar and admin lists).
     *
     * Michael, 2026-08-24 -- added the optional clientId param for the
     * client-facing enrollment flow: "which sessions can this specific
     * client actually enroll an employee into." When provided, this
     * reuses the exact same two checks enrollment submission itself
     * already enforces -- SessionAuthorizationService.isClientAuthorizedToEnroll()
     * (Public/Private/Semi-Private/VTCA/Proposed visibility) and
     * VrEnrollmentEligibilityService.isEligible() (the vrClient gate) --
     * so a session can never appear in this list and then be rejected
     * on submit; the same rule is the same rule in both places, not two
     * separate implementations that could drift apart.
     *
     * Also forces published=true internally whenever clientId is given,
     * REGARDLESS of the published param -- an unpublished session must
     * never be client-visible under any circumstance, not left to
     * whichever caller remembers to pass the right flag.
     */
    /**
     * Michael, 2026-08-29 -- performance audit finding: this endpoint
     * was loading EVERY session ever created via findAll() on every
     * single call -- including from the client-facing Enroll page's
     * session dropdown, meaning every client loading that page
     * triggered a full-table scan. The schoolType/region/published
     * filters are now pushed down to a real, indexed database query
     * (a Specification, since these three optional filters combine in
     * every possible way -- cleaner than writing out each combination
     * as its own derived query method). Only what survives THAT
     * narrower query then goes through the per-client authorization
     * checks below, which genuinely can't be pushed into the database
     * (real business logic, not a column comparison) -- but those now
     * run against a small, already-filtered set instead of every
     * session ever created. Same results as before, purely faster.
     */
    /**
     * Michael, 2026-08-30 -- Lecture Certificate Upload feature: added
     * optional studentId. VrEnrollmentEligibilityService now also
     * checks lecture completion, which is per-STUDENT, not per-client
     * -- this endpoint runs before a specific student is chosen (the
     * client is still just browsing which sessions exist at all), so
     * there's genuinely nothing to check until the client-facing UI
     * knows who's being enrolled. Confirmed with Michael: rather than
     * accept that gap (a VR session could show as valid here, then get
     * rejected on actual submission for a reason this listing never
     * caught), the UI re-queries this same endpoint with studentId
     * once an employee is picked, so the same rule applies consistently
     * in both places -- matching this method's own existing principle
     * for the other two checks.
     */
    @GetMapping
    @Transactional(readOnly = true)
    public ResponseEntity<List<Session>> list(@RequestParam(required = false) SchoolType schoolType,
                                               @RequestParam(required = false) RegionType region,
                                               @RequestParam(required = false) Boolean published,
                                               @RequestParam(required = false) Long clientId,
                                               @RequestParam(required = false) Long studentId) {
        Client client = null;
        if (clientId != null) {
            client = clientRepository.findById(clientId)
                    .orElseThrow(() -> new IllegalArgumentException("Client not found: " + clientId));
        }
        final Client finalClient = client;

        com.caa.platform.student.Student student = null;
        if (studentId != null) {
            student = studentRepository.findById(studentId)
                    .orElseThrow(() -> new IllegalArgumentException("Student not found: " + studentId));
        }
        final com.caa.platform.student.Student finalStudent = student;

        // Michael, 2026-08-29 -- same rule as before: an unpublished
        // session must never be client-visible under any circumstance,
        // regardless of what the published param says -- now enforced
        // by narrowing the actual database query itself, not just a
        // post-fetch filter that a future refactor could accidentally
        // drop.
        final Boolean effectivePublished = finalClient != null ? Boolean.TRUE : published;

        Specification<Session> spec = Specification.where(null);
        if (schoolType != null) {
            spec = spec.and((root, query, cb) -> cb.equal(root.get("schoolType"), schoolType));
        }
        if (region != null) {
            spec = spec.and((root, query, cb) -> cb.equal(root.get("region"), region));
        }
        if (effectivePublished != null) {
            spec = spec.and((root, query, cb) -> cb.equal(root.get("published"), effectivePublished));
        }

        List<Session> results = sessionRepository.findAll(spec).stream()
                .filter(s -> finalClient == null || authorizationService.isClientAuthorizedToEnroll(s, finalClient))
                .filter(s -> finalClient == null || vrEligibilityService.isEligible(s, finalClient, finalStudent))
                .toList();
        return ResponseEntity.ok(results);
    }

    /**
     * GET /sessions/calendar -- staff-facing calendar (all school types,
     * not just Public+published like the public calendar).
     *
     * Michael, 2026-08-29 -- staff calendar, Phase 1. Now date-range
     * scoped (startDate/endDate required) -- returns one entry per
     * actual SessionDay in range, not one per session, so a multi-day
     * session shows a real card on every day it spans (confirmed with
     * Michael via the real Houston, TX example: 2 days, 2 cards).
     * isPrimaryDay is computed against each session's TRUE earliest
     * date globally, not just the earliest one falling within this
     * particular range -- a session spanning a month boundary must
     * never have a later month's first visible day mistaken for its
     * real, editable occurrence.
     *
     * Also now returns per-day staff availability alongside the
     * session cards -- "busy" is anyone assigned as fieldManager/
     * operator/proctor1-3 (team roles only, not truck/trailer) on ANY
     * session with a SessionDay that date; "available" is everyone
     * else. Wrapped together in one CalendarResponse since the UI
     * needs both to render a single day cell (the session cards AND
     * that day's Avail: list).
     */
    /**
     * Michael, 2026-08-29 -- staff calendar, Phase 6 (drag-and-drop).
     * A real id, not just the display initials -- the Avail list is the
     * one place staff actually get dragged FROM, so this is the one
     * spot that genuinely needs the id to submit a change with, unlike
     * busyStaffInitials/doubleBookedStaffInitials below, which stay
     * plain display strings since they're never a drag source.
     */
    public record AvailableStaffMember(Long id, String initials) {}

    /**
     * Michael, 2026-08-31 -- staff calendar, Truck/Trailer drag-and-drop.
     * Mirrors the staff availability pattern exactly (id+shortCode for
     * dragging, plain shortCode strings for busy/double-booked, since
     * those are never drag sources) -- same reasoning, same shape.
     * Confirmed with Michael: no hard enforcement here either, matching
     * staff -- a truck/trailer can only physically be in one place at a
     * time, which makes this arguably MORE worth flagging visually than
     * staff, but it's still purely a visual prompt, never a block (a
     * truck could legitimately serve a morning session at one location
     * then an afternoon session elsewhere the same day).
     */
    public record AvailableEquipmentItem(Long id, String shortCode) {}
    public record EquipmentAvailability(List<AvailableEquipmentItem> available, List<String> busyShortCodes,
                                          List<String> doubleBookedShortCodes) {}

    public record DailyAvailability(LocalDate date, List<AvailableStaffMember> availableStaff, List<String> busyStaffInitials,
                                     List<String> doubleBookedStaffInitials, EquipmentAvailability trucks, EquipmentAvailability trailers) {}
    public record CalendarResponse(List<SessionCalendarEntry> entries, List<DailyAvailability> availability) {}

    @GetMapping("/calendar")
    @Transactional(readOnly = true)
    public ResponseEntity<CalendarResponse> calendar(@RequestParam LocalDate startDate,
                                                       @RequestParam LocalDate endDate,
                                                       @RequestParam(required = false) SchoolType schoolType) {
        List<SessionDay> daysInRange = sessionDayRepository.findBySessionDateBetween(startDate, endDate).stream()
                .filter(d -> schoolType == null || d.getSession().getSchoolType() == schoolType)
                .toList();

        List<Long> sessionIds = daysInRange.stream().map(d -> d.getSession().getId()).distinct().toList();

        // True earliest date per session, GLOBALLY -- not just within
        // this range -- so isPrimaryDay is correct even for a session
        // whose real first day falls in an earlier, unrequested month.
        java.util.Map<Long, LocalDate> trueEarliestDates = sessionDayRepository.earliestDatesBySessionId(sessionIds);

        java.util.Map<Long, List<Enrollment>> enrollmentsBySession = enrollmentRepository.findBySessionIdIn(sessionIds).stream()
                .collect(java.util.stream.Collectors.groupingBy(e -> e.getSession().getId()));

        List<SessionCalendarEntry> entries = daysInRange.stream()
                .map(d -> buildCalendarEntry(d, trueEarliestDates, enrollmentsBySession))
                .sorted((a, b) -> a.date().compareTo(b.date()))
                .toList();

        List<DailyAvailability> availability = buildDailyAvailability(daysInRange, startDate, endDate);

        return ResponseEntity.ok(new CalendarResponse(entries, availability));
    }

    /**
     * Michael, 2026-08-29 -- staff calendar, Phase 4. Delta-based --
     * only actual staged changes are submitted, not DIBs' own
     * full-state resubmit of every session on screen (confirmed with
     * Michael: rare for two staff to drag-and-drop simultaneously, but
     * not unusual for several to be editing different sessions at the
     * same time -- a full-snapshot resubmit could silently overwrite
     * someone else's unrelated, concurrent edit). Best-effort, not
     * atomic -- see CalendarAssignmentService's own docblock for the
     * full reasoning on why each change gets its own, fully
     * independent transaction rather than sharing one across the
     * whole batch.
     */
    @PostMapping("/calendar/assignments")
    public ResponseEntity<List<CalendarAssignmentService.AssignmentChangeResult>> saveCalendarAssignments(
            @RequestBody List<CalendarAssignmentService.AssignmentChange> changes) {
        List<CalendarAssignmentService.AssignmentChangeResult> results = changes.stream()
                .map(calendarAssignmentService::applyChange)
                .toList();
        return ResponseEntity.ok(results);
    }

    private SessionCalendarEntry buildCalendarEntry(SessionDay day, java.util.Map<Long, LocalDate> trueEarliestDates,
                                                      java.util.Map<Long, List<Enrollment>> enrollmentsBySession) {
        Session s = day.getSession();
        MapTarget mt = resolveMapTarget(s);
        boolean isPrimaryDay = day.getSessionDate().equals(trueEarliestDates.get(s.getId()));

        List<Enrollment> enrollments = enrollmentsBySession.getOrDefault(s.getId(), List.of());
        Integer lectureCount = null, fieldCount = null, certified = null, dnc = null, dna = null;
        if (s.isOnsiteTestingEnabled()) {
            certified = (int) enrollments.stream().filter(e -> e.getRosterStatus() == RosterStatus.CERTIFIED).count();
            dnc = (int) enrollments.stream().filter(e -> e.getRosterStatus() == RosterStatus.DNC).count();
            dna = (int) enrollments.stream().filter(e -> e.getRosterStatus() == RosterStatus.DNA).count();
        } else {
            lectureCount = (int) enrollments.stream().filter(e -> e.getEnrollmentComponents() == EnrollmentComponents.LECTURE_ONLY).count();
            fieldCount = (int) enrollments.stream().filter(e -> e.getEnrollmentComponents() == EnrollmentComponents.FIELD_ONLY).count();
        }

        return new SessionCalendarEntry(
                s.getId(), s.getSchoolType(), s.getLocationName(), s.getRegion(),
                s.isPublished(), s.isConfirmed(), s.isClosedOut(), s.isBidLost(), s.isCanceled(), s.isVrSession(),
                day.getSessionDate(), isPrimaryDay,
                mt.lat(), mt.lng(), mt.address(),
                staffInitials(s.getFieldManager()), staffInitials(s.getOperator()),
                staffInitials(s.getProctor1()), staffInitials(s.getProctor2()), staffInitials(s.getProctor3()),
                truckShortCode(s.getTruck()), trailerShortCode(s.getTrailer()),
                staffId(s.getFieldManager()), staffId(s.getOperator()),
                staffId(s.getProctor1()), staffId(s.getProctor2()), staffId(s.getProctor3()),
                s.getTruck() != null ? s.getTruck().getId() : null,
                s.getTrailer() != null ? s.getTrailer().getId() : null,
                s.isOnsiteTestingEnabled(), lectureCount, fieldCount, certified, dnc, dna,
                s.getSessionInfoOwner() != null ? s.getSessionInfoOwner().getName() : null,
                s.getConfirmedComment(),
                calendarTextColor(s), calendarBackgroundColor(s), calendarStrikethrough(s));
    }

    private Long staffId(StaffUser staff) {
        return staff != null ? staff.getId() : null;
    }

    /**
     * Michael, 2026-08-29 -- staff calendar, Phase 3. Reverse-engineered
     * directly from the real DIBs legend markup, not guessed at.
     * bidLost is checked FIRST and explicitly, not derived from
     * schoolType -- confirmed as its own distinct rule in the legend
     * (Lost Bid happens to share Proposed's color, but is called out
     * separately, not merely inherited).
     */
    private String calendarTextColor(Session s) {
        if (s.isBidLost()) {
            return "#CC5500";
        }
        return switch (s.getSchoolType()) {
            case PUBLIC -> "#A52A2A";
            case PRIVATE -> "#0000FF";
            case SEMI_PRIVATE -> "#800080";
            case PROPOSED -> "#CC5500";
            case VTCA -> "#006400";
            // Michael, 2026-08-29 -- defensive default, not a real
            // legend entry: couldn't verify SchoolType's complete enum
            // definition against the actual compiler in this sandbox
            // (reset, no Java toolchain available). Black is a neutral,
            // clearly-not-matching-anything-else fallback should a 6th
            // value ever exist -- worth flagging if this default ever
            // actually fires in practice, since it means a real school
            // type has no defined color at all yet.
            default -> "#000000";
        };
    }

    /**
     * Michael, 2026-08-29 -- staff calendar, Phase 3. Priority order
     * confirmed explicitly with Michael: Closed-out wins over
     * everything else, regardless of school type, VR, or confirmed
     * status -- checked first, unconditionally. The three "Publ..."
     * backgrounds are Public-only, matching the legend's own labeling
     * ("Publ & Confirmed", not just "Confirmed") -- Private/Semi-
     * Private/Proposed/VTCA never get a confirmed-driven background at
     * all, only their plain text color.
     */
    private String calendarBackgroundColor(Session s) {
        if (s.isClosedOut()) {
            return "#FFFF00";
        }
        if (s.getSchoolType() == SchoolType.PUBLIC) {
            if (s.isVrSession() && s.isConfirmed()) {
                return "#FF7FFF";
            }
            if (s.isConfirmed()) {
                return "#B3FFB3";
            }
            return "#FFD090";
        }
        return null;
    }

    /**
     * Michael, 2026-08-29 -- staff calendar, Phase 3. Covers both of
     * DIBs' own underlying mechanisms (a plain line-through for
     * Unpublished, a separate "x_out" class for Canceled/Lost Bid) as
     * one boolean -- the end visual result is identical strikethrough
     * text either way, so the frontend doesn't need to know which
     * specific condition triggered it.
     */
    private boolean calendarStrikethrough(Session s) {
        return !s.isPublished() || s.isCanceled() || s.isBidLost();
    }

    private String staffInitials(StaffUser staff) {
        return staff != null ? staff.getInitials() : null;
    }

    private String truckShortCode(com.caa.platform.equipment.Truck truck) {
        return truck != null ? truck.getShortCode() : null;
    }

    private String trailerShortCode(com.caa.platform.equipment.Trailer trailer) {
        return trailer != null ? trailer.getShortCode() : null;
    }

    /**
     * Michael, 2026-08-29 -- "busy" is team-role assignment only
     * (fieldManager/operator/proctor1-3) -- truck/trailer are
     * equipment, not staff, and don't belong on a staff availability
     * list. Every staff member not busy on a given date is available
     * that date -- there's no separate "time off"/leave concept on
     * StaffUser yet, so this is the full, real rule today, not a
     * simplification of a richer one.
     */
    /**
     * Michael, 2026-08-29 -- staff calendar, Phase 4. Confirmed with
     * Michael: no hard enforcement at all -- some sessions can
     * legitimately double-book (a morning session at one location, an
     * afternoon session at another), and staff judgment decides
     * whether that's fine. This is purely a visual prompt (matching
     * DIBs' own orange-box treatment), never a block -- so what's
     * actually needed is COUNTING how many times each staff member is
     * assigned on a given day, not just whether they're busy at all.
     * A Set here would silently collapse a real double-booking into a
     * single, indistinguishable entry -- switched to counting so a
     * second assignment on the same day is actually detectable.
     */
    private List<DailyAvailability> buildDailyAvailability(List<SessionDay> daysInRange, LocalDate startDate, LocalDate endDate) {
        List<StaffUser> allStaff = staffUserRepository.findAll().stream()
                .filter(s -> s.getInitials() != null)
                .toList();
        List<com.caa.platform.equipment.Truck> allTrucks = truckRepository.findAll().stream()
                .filter(t -> t.getShortCode() != null)
                .toList();
        List<com.caa.platform.equipment.Trailer> allTrailers = trailerRepository.findAll().stream()
                .filter(t -> t.getShortCode() != null)
                .toList();

        java.util.Map<LocalDate, java.util.Map<String, Integer>> assignmentCountsByDate = new java.util.HashMap<>();
        java.util.Map<LocalDate, java.util.Map<String, Integer>> truckCountsByDate = new java.util.HashMap<>();
        java.util.Map<LocalDate, java.util.Map<String, Integer>> trailerCountsByDate = new java.util.HashMap<>();
        for (SessionDay d : daysInRange) {
            Session s = d.getSession();
            java.util.Map<String, Integer> counts = assignmentCountsByDate.computeIfAbsent(d.getSessionDate(), k -> new java.util.HashMap<>());
            for (StaffUser member : java.util.Arrays.asList(s.getFieldManager(), s.getOperator(), s.getProctor1(), s.getProctor2(), s.getProctor3())) {
                if (member != null && member.getInitials() != null) {
                    counts.merge(member.getInitials(), 1, Integer::sum);
                }
            }
            if (s.getTruck() != null && s.getTruck().getShortCode() != null) {
                truckCountsByDate.computeIfAbsent(d.getSessionDate(), k -> new java.util.HashMap<>())
                        .merge(s.getTruck().getShortCode(), 1, Integer::sum);
            }
            if (s.getTrailer() != null && s.getTrailer().getShortCode() != null) {
                trailerCountsByDate.computeIfAbsent(d.getSessionDate(), k -> new java.util.HashMap<>())
                        .merge(s.getTrailer().getShortCode(), 1, Integer::sum);
            }
        }

        List<DailyAvailability> result = new java.util.ArrayList<>();
        for (LocalDate d = startDate; !d.isAfter(endDate); d = d.plusDays(1)) {
            java.util.Map<String, Integer> counts = assignmentCountsByDate.getOrDefault(d, java.util.Map.of());
            List<String> busy = List.copyOf(counts.keySet());
            List<AvailableStaffMember> available = allStaff.stream()
                    .filter(s -> !counts.containsKey(s.getInitials()))
                    .map(s -> new AvailableStaffMember(s.getId(), s.getInitials()))
                    .toList();
            List<String> doubleBooked = counts.entrySet().stream()
                    .filter(e -> e.getValue() > 1)
                    .map(java.util.Map.Entry::getKey)
                    .toList();

            EquipmentAvailability trucks = buildEquipmentAvailability(
                    truckCountsByDate.getOrDefault(d, java.util.Map.of()), allTrucks,
                    com.caa.platform.equipment.Truck::getId, com.caa.platform.equipment.Truck::getShortCode);
            EquipmentAvailability trailers = buildEquipmentAvailability(
                    trailerCountsByDate.getOrDefault(d, java.util.Map.of()), allTrailers,
                    com.caa.platform.equipment.Trailer::getId, com.caa.platform.equipment.Trailer::getShortCode);

            result.add(new DailyAvailability(d, available, busy, doubleBooked, trucks, trailers));
        }
        return result;
    }

    /**
     * Michael, 2026-08-31 -- shared between Truck and Trailer -- same
     * available/busy/double-booked logic, just parameterized over which
     * equipment type and how to read its id/shortCode, rather than
     * duplicating the same counting logic twice.
     */
    private <T> EquipmentAvailability buildEquipmentAvailability(java.util.Map<String, Integer> counts, List<T> allEquipment,
                                                                    java.util.function.Function<T, Long> idFn,
                                                                    java.util.function.Function<T, String> shortCodeFn) {
        List<String> busy = List.copyOf(counts.keySet());
        List<AvailableEquipmentItem> available = allEquipment.stream()
                .filter(e -> !counts.containsKey(shortCodeFn.apply(e)))
                .map(e -> new AvailableEquipmentItem(idFn.apply(e), shortCodeFn.apply(e)))
                .toList();
        List<String> doubleBooked = counts.entrySet().stream()
                .filter(e -> e.getValue() > 1)
                .map(java.util.Map.Entry::getKey)
                .toList();
        return new EquipmentAvailability(available, busy, doubleBooked);
    }

    /**
     * "Map It" quick-link support (dashboard Today/Tomorrow, 2026-08-16):
     * prefers the actual Field testing-site location over the general
     * School display location, since that's where staff physically need
     * to go -- falls back to GPS-less address strings, then to nothing
     * if neither location is filled in yet for this session.
     */
    private record MapTarget(java.math.BigDecimal lat, java.math.BigDecimal lng, String address) {}

    private MapTarget resolveMapTarget(Session s) {
        if (s.getFieldLat() != null && s.getFieldLng() != null) {
            return new MapTarget(s.getFieldLat(), s.getFieldLng(), null);
        }
        if (s.getGridLat() != null && s.getGridLng() != null) {
            return new MapTarget(s.getGridLat(), s.getGridLng(), null);
        }
        String fieldAddr = formatAddress(s.getFieldAddress(), s.getFieldCity(), s.getFieldState(), s.getFieldZip());
        if (fieldAddr != null) return new MapTarget(null, null, fieldAddr);
        String schoolAddr = formatAddress(s.getAddressStreet(), s.getAddressCity(), s.getAddressState(), s.getAddressZip());
        if (schoolAddr != null) return new MapTarget(null, null, schoolAddr);
        return new MapTarget(null, null, null);
    }

    private String formatAddress(String street, String city, String state, String zip) {
        if (street == null && city == null) return null;
        StringBuilder sb = new StringBuilder();
        if (street != null) sb.append(street).append(", ");
        if (city != null) sb.append(city).append(", ");
        if (state != null) sb.append(state).append(" ");
        if (zip != null) sb.append(zip);
        return sb.toString().trim().replaceAll(",$", "");
    }

    public record SessionCreateRequest(SchoolType schoolType, SessionFormat format, Long hostClientId,
                                        String locationName, String addressStreet, String addressCity,
                                        String addressState, String addressZip) {}

    /**
     * POST /sessions. For Private/Semi-Private/VTCA, hostClientId is
     * required and establishes the SessionAuthorizedClient host entry
     * (Section 4) -- VTCA behaves structurally like Private (see
     * SchoolType). For Public/VR, hostClientId must be omitted -- open
     * enrollment.
     */
    @PostMapping
    @Transactional
    public ResponseEntity<?> create(@RequestBody SessionCreateRequest req) {
        boolean needsHost = req.schoolType() == SchoolType.PRIVATE
                || req.schoolType() == SchoolType.SEMI_PRIVATE
                || req.schoolType() == SchoolType.VTCA
                || req.schoolType() == SchoolType.PROPOSED;
        if (needsHost && req.hostClientId() == null) {
            return ResponseEntity.unprocessableEntity()
                    .body(Map.of("error", "Private/Semi-Private/VTCA/Proposed sessions require a hostClientId."));
        }
        if (!needsHost && req.hostClientId() != null) {
            return ResponseEntity.unprocessableEntity()
                    .body(Map.of("error", "Public sessions must not specify a hostClientId -- open enrollment (Section 4)."));
        }

        Session session = new Session();
        session.setSchoolType(req.schoolType());
        session.setFormat(req.format());
        session.setLocationName(req.locationName());
        session.setAddressStreet(req.addressStreet());
        session.setAddressCity(req.addressCity());
        session.setAddressState(req.addressState());
        session.setAddressZip(req.addressZip());
        Session saved = sessionRepository.save(session);

        if (needsHost) {
            Client host = clientRepository.findById(req.hostClientId())
                    .orElseThrow(() -> new IllegalArgumentException("Client not found: " + req.hostClientId()));
            authorizationService.setHost(saved, host, null);
        }

        return ResponseEntity.status(HttpStatus.CREATED).body(saved);
    }

    /**
     * Force-initializes every lazy @ManyToOne on Session while a
     * transaction is still open, so serialization succeeds afterward
     * (open-in-view is disabled, so a mere @Transactional on the calling
     * method isn't enough on its own -- see get()'s Javadoc for the full
     * explanation). Shared here since both get() and update() return the
     * raw entity and hit the exact same problem.
     */
    private void initializeSessionLazyRelations(Session s) {
        org.hibernate.Hibernate.initialize(s.getFieldManager());
        org.hibernate.Hibernate.initialize(s.getOperator());
        org.hibernate.Hibernate.initialize(s.getProctor1());
        org.hibernate.Hibernate.initialize(s.getProctor2());
        org.hibernate.Hibernate.initialize(s.getProctor3());
        org.hibernate.Hibernate.initialize(s.getTruck());
        org.hibernate.Hibernate.initialize(s.getTrailer());
        org.hibernate.Hibernate.initialize(s.getSessionInfoOwner());
        org.hibernate.Hibernate.initialize(s.getSessionInfoVerifiedBy());
        org.hibernate.Hibernate.initialize(s.getRegionOverrideBy());
        org.hibernate.Hibernate.initialize(s.getCopiedFromSession());
    }

    /**
     * @Transactional + initializeSessionLazyRelations() -- this endpoint
     * returns the raw entity (not a DTO), and with open-in-view
     * disabled, merely adding @Transactional here does NOT keep the
     * Hibernate session open through Jackson's serialization (which
     * happens via Spring's message converter AFTER this method returns
     * and its transaction closes) -- same root cause as the earlier
     * SessionAuthorizedClient null-serialization bug. Every lazy
     * @ManyToOne on Session was silently serializing as null on every
     * single GET, even when correctly saved in the database -- caught
     * 2026-08-16 when Field Manager/Operator changes appeared to save
     * (200 OK, correct DB row) but never showed up on re-fetch.
     */
    @GetMapping("/{sessionId}")
    @Transactional(readOnly = true)
    public ResponseEntity<Session> get(@PathVariable Long sessionId) {
        return sessionRepository.findById(sessionId)
                .map(s -> {
                    initializeSessionLazyRelations(s);
                    return s;
                })
                .map(ResponseEntity::ok)
                .orElseGet(() -> ResponseEntity.notFound().build());
    }

    public record SessionUpdateRequest(
            String locationName, String addressStreet, String addressCity, String addressState, String addressZip,
            java.math.BigDecimal gridLat, java.math.BigDecimal gridLng,
            java.math.BigDecimal quotedPrice, Integer quotedHeadcount, java.math.BigDecimal fieldTest, java.math.BigDecimal privateCost,
            java.math.BigDecimal fieldCertificationPrice, java.math.BigDecimal selfPacedLecturePrice,
            java.math.BigDecimal lateFeeAmount, Integer lateFeeDayThreshold,
            String externalRegistrationName, String externalRegistrationPhone, String externalRegistrationNotes,
            String publicSessionNotes, String poNumber, Integer netTermsDays,
            SchoolType schoolType,
            Boolean confirmed, String confirmedComment, Boolean closedOut,
            Boolean bidLost, String bidLostReason,
            Boolean notNeedCopy, String notNeedCopyWhy, String qboClassRefId,
            Long sessionInfoOwnerId, Boolean vrSession,
            Boolean advertiseSemiPrivateAsPublic, Boolean staggeredArrivalTimes,
            String sessionLog, String adminComments,
            String fieldTimezone, String fieldContact, String fieldContactPhone,
            Long fieldManagerId, Long operatorId,
            Long proctor1Id, Long proctor2Id, Long proctor3Id,
            Long truckId, Long trailerId, String stagedLocation,
            String fieldFacility, String fieldAddress, String fieldCity, String fieldState, String fieldZip,
            java.math.BigDecimal fieldLat, java.math.BigDecimal fieldLng,
            Boolean useClientInfo, String schoolUrl, String schoolGeoArea, Boolean canceled,
            String bidExtraDetailsLecture, String bidExtraDetailsField, String bidCaaNotes,
            Integer bidNumSelfpacedLectureAttendees, Integer bidNumInpersonLectureAttendees, Integer bidNumFieldAttendees,
            Boolean bidNeedPoUpfront, Boolean bidNoPublicAllowed, Boolean bidRequiresCertOfCompletion,
            Boolean bidNoAddons, Boolean bidAddonsRequireChangeOrder,
            Integer bidRevisionNumber, String bidEmailMessage, String poForInvoice,
            String lastQboInvoiceSentNumber,
            Boolean onsiteSignInEnabled, Boolean onsiteTestingEnabled
    ) {}

    /**
     * PATCH /sessions/{id} -- general field edits (location, GPS, pricing,
     * notes, etc.), distinct from the dedicated action endpoints above
     * (publish, comments, copy-forward). Only non-null fields in the
     * request are applied, so callers can send a partial update.
     *
     * confirmed/closedOut/bidLost are independent flags here, not stages
     * of one status (Section 4c) -- see Session's Javadoc.
     *
     * schoolType is settable here, but this does NOT auto-manage the
     * host-client relationship (SessionAuthorizedClient) -- changing
     * TO a host-requiring type (Private/Semi-Private/VTCA/Proposed)
     * does not create one, and changing AWAY from one does not remove
     * the existing host. That reconciliation still has to happen
     * manually via the Authorized Clients endpoints. Flagged here as a
     * known gap, not silently ignored.
     *
     * sessionInfoVerified is deliberately NOT settable here -- it needs
     * verifiedBy/verifiedAt set together, server-side, so it has its own
     * dedicated endpoint (POST .../verify-info) rather than risking a
     * client sending verified=true without the accompanying audit trail.
     */
    @PatchMapping("/{sessionId}")
    @Transactional
    public ResponseEntity<Session> update(@PathVariable Long sessionId, @RequestBody SessionUpdateRequest req) {
        Session session = sessionRepository.findById(sessionId)
                .orElseThrow(() -> new IllegalArgumentException("Session not found: " + sessionId));

        if (req.schoolType() != null) session.setSchoolType(req.schoolType());
        if (req.locationName() != null) session.setLocationName(req.locationName());
        if (req.addressStreet() != null) session.setAddressStreet(req.addressStreet());
        if (req.addressCity() != null) session.setAddressCity(req.addressCity());
        if (req.addressState() != null) session.setAddressState(req.addressState());
        if (req.addressZip() != null) session.setAddressZip(req.addressZip());
        if (req.gridLat() != null) session.setGridLat(req.gridLat());
        if (req.gridLng() != null) session.setGridLng(req.gridLng());
        if (req.quotedPrice() != null) session.setQuotedPrice(req.quotedPrice());
        if (req.quotedHeadcount() != null) session.setQuotedHeadcount(req.quotedHeadcount());
        if (req.fieldTest() != null) session.setFieldTest(req.fieldTest());
        if (req.privateCost() != null) session.setPrivateCost(req.privateCost());
        if (req.fieldCertificationPrice() != null) session.setFieldCertificationPrice(req.fieldCertificationPrice());
        if (req.selfPacedLecturePrice() != null) session.setSelfPacedLecturePrice(req.selfPacedLecturePrice());
        if (req.lateFeeAmount() != null) session.setLateFeeAmount(req.lateFeeAmount());
        if (req.lateFeeDayThreshold() != null) session.setLateFeeDayThreshold(req.lateFeeDayThreshold());
        if (req.externalRegistrationName() != null) session.setExternalRegistrationName(req.externalRegistrationName());
        if (req.externalRegistrationPhone() != null) session.setExternalRegistrationPhone(req.externalRegistrationPhone());
        if (req.externalRegistrationNotes() != null) session.setExternalRegistrationNotes(req.externalRegistrationNotes());
        if (req.publicSessionNotes() != null) session.setPublicSessionNotes(req.publicSessionNotes());
        if (req.poNumber() != null) session.setPoNumber(req.poNumber());
        if (req.netTermsDays() != null) session.setNetTermsDays(req.netTermsDays());
        if (req.confirmed() != null) session.setConfirmed(req.confirmed());
        if (req.confirmedComment() != null) session.setConfirmedComment(req.confirmedComment());
        if (req.closedOut() != null) session.setClosedOut(req.closedOut());
        if (req.bidLost() != null) session.setBidLost(req.bidLost());
        if (req.bidLostReason() != null) session.setBidLostReason(req.bidLostReason());
        if (req.notNeedCopy() != null) session.setNotNeedCopy(req.notNeedCopy());
        if (req.notNeedCopyWhy() != null) session.setNotNeedCopyWhy(req.notNeedCopyWhy());
        if (req.qboClassRefId() != null) session.setQboClassRefId(req.qboClassRefId());
        if (req.sessionInfoOwnerId() != null) {
            StaffUser owner = staffUserRepository.findById(req.sessionInfoOwnerId())
                    .orElseThrow(() -> new IllegalArgumentException("Staff user not found: " + req.sessionInfoOwnerId()));
            session.setSessionInfoOwner(owner);
        }
        if (req.vrSession() != null) session.setVrSession(req.vrSession());
        if (req.advertiseSemiPrivateAsPublic() != null) session.setAdvertiseSemiPrivateAsPublic(req.advertiseSemiPrivateAsPublic());
        if (req.staggeredArrivalTimes() != null) session.setStaggeredArrivalTimes(req.staggeredArrivalTimes());
        if (req.sessionLog() != null) session.setSessionLog(req.sessionLog());
        if (req.adminComments() != null) session.setAdminComments(req.adminComments());
        if (req.fieldTimezone() != null) session.setFieldTimezone(req.fieldTimezone());
        if (req.fieldContact() != null) session.setFieldContact(req.fieldContact());
        if (req.fieldContactPhone() != null) session.setFieldContactPhone(req.fieldContactPhone());
        if (req.fieldManagerId() != null) {
            session.setFieldManager(staffUserRepository.findById(req.fieldManagerId())
                    .orElseThrow(() -> new IllegalArgumentException("Staff user not found: " + req.fieldManagerId())));
        }
        if (req.operatorId() != null) {
            session.setOperator(staffUserRepository.findById(req.operatorId())
                    .orElseThrow(() -> new IllegalArgumentException("Staff user not found: " + req.operatorId())));
        }
        if (req.proctor1Id() != null) {
            session.setProctor1(staffUserRepository.findById(req.proctor1Id())
                    .orElseThrow(() -> new IllegalArgumentException("Staff user not found: " + req.proctor1Id())));
        }
        if (req.proctor2Id() != null) {
            session.setProctor2(staffUserRepository.findById(req.proctor2Id())
                    .orElseThrow(() -> new IllegalArgumentException("Staff user not found: " + req.proctor2Id())));
        }
        if (req.proctor3Id() != null) {
            session.setProctor3(staffUserRepository.findById(req.proctor3Id())
                    .orElseThrow(() -> new IllegalArgumentException("Staff user not found: " + req.proctor3Id())));
        }
        if (req.truckId() != null) {
            session.setTruck(truckRepository.findById(req.truckId())
                    .orElseThrow(() -> new IllegalArgumentException("Truck not found: " + req.truckId())));
        }
        if (req.trailerId() != null) {
            session.setTrailer(trailerRepository.findById(req.trailerId())
                    .orElseThrow(() -> new IllegalArgumentException("Trailer not found: " + req.trailerId())));
        }
        if (req.stagedLocation() != null) session.setStagedLocation(req.stagedLocation());
        if (req.fieldFacility() != null) session.setFieldFacility(req.fieldFacility());
        if (req.fieldAddress() != null) session.setFieldAddress(req.fieldAddress());
        if (req.fieldCity() != null) session.setFieldCity(req.fieldCity());
        if (req.fieldState() != null) session.setFieldState(req.fieldState());
        if (req.fieldZip() != null) session.setFieldZip(req.fieldZip());
        if (req.fieldLat() != null) session.setFieldLat(req.fieldLat());
        if (req.fieldLng() != null) session.setFieldLng(req.fieldLng());
        if (req.useClientInfo() != null) session.setUseClientInfo(req.useClientInfo());
        if (req.schoolUrl() != null) session.setSchoolUrl(req.schoolUrl());
        if (req.schoolGeoArea() != null) session.setSchoolGeoArea(req.schoolGeoArea());
        if (req.canceled() != null) session.setCanceled(req.canceled());
        if (req.bidExtraDetailsLecture() != null) session.setBidExtraDetailsLecture(req.bidExtraDetailsLecture());
        if (req.bidExtraDetailsField() != null) session.setBidExtraDetailsField(req.bidExtraDetailsField());
        if (req.bidCaaNotes() != null) session.setBidCaaNotes(req.bidCaaNotes());
        if (req.bidNumSelfpacedLectureAttendees() != null) session.setBidNumSelfpacedLectureAttendees(req.bidNumSelfpacedLectureAttendees());
        if (req.bidNumInpersonLectureAttendees() != null) session.setBidNumInpersonLectureAttendees(req.bidNumInpersonLectureAttendees());
        if (req.bidNumFieldAttendees() != null) session.setBidNumFieldAttendees(req.bidNumFieldAttendees());
        // bidDiscountFieldForPublic removed (Michael, 2026-08-19) -- see Session.fieldTest's Javadoc.
        if (req.bidNeedPoUpfront() != null) session.setBidNeedPoUpfront(req.bidNeedPoUpfront());
        if (req.bidNoPublicAllowed() != null) session.setBidNoPublicAllowed(req.bidNoPublicAllowed());
        if (req.bidRequiresCertOfCompletion() != null) session.setBidRequiresCertOfCompletion(req.bidRequiresCertOfCompletion());
        if (req.bidNoAddons() != null) session.setBidNoAddons(req.bidNoAddons());
        if (req.bidAddonsRequireChangeOrder() != null) session.setBidAddonsRequireChangeOrder(req.bidAddonsRequireChangeOrder());
        // bidLunchOption/bidLunchCost removed (Michael, 2026-08-19).
        if (req.bidRevisionNumber() != null) session.setBidRevisionNumber(req.bidRevisionNumber());
        if (req.bidEmailMessage() != null) session.setBidEmailMessage(req.bidEmailMessage());
        if (req.poForInvoice() != null) session.setPoForInvoice(req.poForInvoice());
        if (req.lastQboInvoiceSentNumber() != null) session.setLastQboInvoiceSentNumber(req.lastQboInvoiceSentNumber());
        if (req.onsiteSignInEnabled() != null) session.setOnsiteSignInEnabled(req.onsiteSignInEnabled());
        if (req.onsiteTestingEnabled() != null) session.setOnsiteTestingEnabled(req.onsiteTestingEnabled());

        Session saved = sessionRepository.save(session);
        initializeSessionLazyRelations(saved);
        return ResponseEntity.ok(saved);
    }

    /**
     * POST /sessions/{id}/verify-info -- sets sessionInfoVerified=true
     * together with verifiedBy/verifiedAt, server-side, in one atomic
     * action. Kept separate from the general PATCH endpoint above so a
     * caller can never set verified=true without the accompanying audit
     * trail (Section 4c, "Last changed by... Date...").
     */
    public record VerifyInfoRequest(Long verifiedByStaffId) {}

    @PostMapping("/{sessionId}/verify-info")
    @Transactional
    public ResponseEntity<Session> verifyInfo(@PathVariable Long sessionId, @RequestBody VerifyInfoRequest req) {
        Session session = sessionRepository.findById(sessionId)
                .orElseThrow(() -> new IllegalArgumentException("Session not found: " + sessionId));
        StaffUser verifier = staffUserRepository.findById(req.verifiedByStaffId())
                .orElseThrow(() -> new IllegalArgumentException("Staff user not found: " + req.verifiedByStaffId()));

        session.setSessionInfoVerified(true);
        session.setSessionInfoVerifiedBy(verifier);
        session.setSessionInfoVerifiedAt(OffsetDateTime.now());

        Session saved = sessionRepository.save(session);
        initializeSessionLazyRelations(saved);
        return ResponseEntity.ok(saved);
    }

    /**
     * Section 4c "Clients to be Notified" -- add/remove endpoints for the
     * SessionNotifiedClient relation (replaces DIBs' comma-separated text
     * field with a proper relation -- see SessionNotifiedClient's Javadoc).
     */
    public record NotifyClientRequest(Long clientId) {}

    @PostMapping("/{sessionId}/notified-clients")
    public ResponseEntity<?> addNotifiedClient(@PathVariable Long sessionId, @RequestBody NotifyClientRequest req) {
        if (notifiedClientRepository.existsBySessionIdAndClientId(sessionId, req.clientId())) {
            return ResponseEntity.status(HttpStatus.CONFLICT)
                    .body(Map.of("error", "Client " + req.clientId() + " is already on the notify list for session " + sessionId + "."));
        }
        Session session = sessionRepository.findById(sessionId)
                .orElseThrow(() -> new IllegalArgumentException("Session not found: " + sessionId));
        Client client = clientRepository.findById(req.clientId())
                .orElseThrow(() -> new IllegalArgumentException("Client not found: " + req.clientId()));

        SessionNotifiedClient entry = new SessionNotifiedClient();
        entry.setSession(session);
        entry.setClient(client);
        return ResponseEntity.status(HttpStatus.CREATED).body(notifiedClientRepository.save(entry));
    }

    /**
     * Michael, 2026-08-19, found live during testing -- Add correctly
     * created the record, but the client relationship is lazy
     * (SessionNotifiedClient.client, FetchType.LAZY) and this endpoint
     * had no @Transactional at all, so it serialized as null once
     * outside the transaction -- Laravel's "trying to access array
     * offset on value of type null" was a direct symptom of that null,
     * not a Laravel-side bug. Same root cause class as several other
     * fixes tonight (StaffPrincipal, ClientPrincipal, the confirmation
     * email, the comments list) -- a missing @Transactional letting a
     * lazy relationship leak out unresolved.
     */
    @GetMapping("/{sessionId}/notified-clients")
    @Transactional(readOnly = true)
    public ResponseEntity<List<SessionNotifiedClient>> listNotifiedClients(@PathVariable Long sessionId) {
        List<SessionNotifiedClient> entries = notifiedClientRepository.findBySessionId(sessionId);
        entries.forEach(e -> org.hibernate.Hibernate.initialize(e.getClient()));
        return ResponseEntity.ok(entries);
    }

    @DeleteMapping("/{sessionId}/notified-clients/{clientId}")
    @Transactional
    public ResponseEntity<Void> removeNotifiedClient(@PathVariable Long sessionId, @PathVariable Long clientId) {
        notifiedClientRepository.deleteBySessionIdAndClientId(sessionId, clientId);
        return ResponseEntity.noContent().build();
    }

    @GetMapping("/{sessionId}/publish-readiness")
    public ResponseEntity<Map<String, Boolean>> publishReadiness(@PathVariable Long sessionId) {
        Session session = sessionRepository.findById(sessionId)
                .orElseThrow(() -> new IllegalArgumentException("Session not found: " + sessionId));
        return ResponseEntity.ok(Map.of("readyToPublish", publishGateService.isReadyToPublish(session)));
    }

    /**
     * Section 4c: toggles Publish on, once ready. Sticky once true --
     * this endpoint never un-publishes; it only ever moves false -> true.
     *
     * Michael, 2026-08-25 -- QBO Class sync now fires here too,
     * confirmed as the real, actual trigger point ("once a date is
     * set, and Publish is hit and saved, it locks in the QBO
     * reference"). Deliberately NON-BLOCKING: if the QBO sync fails
     * (no active connection, a transient API error, etc.), the
     * session still publishes -- this is flagged as a real design
     * choice, not a silent assumption, since it means publishing (a
     * core, everyday operational action) is never held hostage by an
     * unrelated accounting-system availability issue. A failed sync
     * just leaves qboClassRefId unset, retryable later via
     * syncClassToQbo() below.
     */
    @org.springframework.web.bind.annotation.PatchMapping("/{sessionId}/publish")
    @Transactional
    public ResponseEntity<?> publish(@PathVariable Long sessionId) {
        Session session = sessionRepository.findById(sessionId)
                .orElseThrow(() -> new IllegalArgumentException("Session not found: " + sessionId));

        if (!publishGateService.isReadyToPublish(session)) {
            return ResponseEntity.unprocessableEntity()
                    .body(Map.of("error", "Session is not ready to publish -- required fields incomplete."));
        }
        publishGateService.publishIfReady(session);
        Session saved = sessionRepository.save(session);

        try {
            String qboClassRefId = syncQboClass(saved);
            saved.setQboClassRefId(qboClassRefId);
        } catch (Exception e) {
            // Michael, 2026-08-25 -- caught, not silently -- see this
            // method's own docblock for the full reasoning on why
            // publish itself must never fail because of this. Logged
            // at WARN, not swallowed entirely: a session with an
            // unset qboClassRefId after publish is a real, visible
            // gap someone should notice and retry via
            // syncClassToQbo(), not something that disappears without
            // a trace.
            log.warn("QBO Class sync failed for session {} during publish -- session was still published successfully; retry via /sessions/{}/sync-qbo-class once resolved.",
                    sessionId, sessionId, e);
        }

        initializeSessionLazyRelations(saved);
        return ResponseEntity.ok(saved);
    }

    /**
     * Michael, 2026-08-25 -- resolves the effective city/state before
     * handing off to QboClassSyncService: Session.useClientInfo was
     * already a real field (set/updated elsewhere in this file) but,
     * found live while wiring this up, was never actually READ
     * anywhere to resolve the effective address -- a real, pre-existing
     * gap, not something introduced here. When true, the host client's
     * own city/state are used instead of Session's own (frequently
     * blank in that case) address fields.
     */
    private String syncQboClass(Session session) {
        String cityName;
        String stateCode;

        if (session.isUseClientInfo()) {
            Client host = authorizedClientRepository.findBySessionIdAndIsHostTrue(session.getId())
                    .map(SessionAuthorizedClient::getClient)
                    .orElse(null);
            cityName = host != null ? host.getCity() : null;
            stateCode = host != null ? host.getState() : null;
        } else {
            cityName = session.getAddressCity();
            stateCode = session.getAddressState();
        }

        LocalDate earliestDate = sessionDayRepository.findFirstBySessionIdOrderBySessionDateAsc(session.getId())
                .map(SessionDay::getSessionDate)
                .orElse(null);

        return qboClassSyncService.syncClass(session, earliestDate, cityName, stateCode);
    }

    /**
     * Michael, 2026-08-25 -- manual retry/on-demand trigger, matching
     * ClientController.syncQbo()'s own shape and reasoning -- useful
     * if the automatic sync during publish() failed (no connection at
     * the time, etc.) and needs to be run again once QBO is reachable,
     * without needing to somehow "re-publish" a session that's already
     * published.
     */
    @PostMapping("/{sessionId}/sync-qbo-class")
    @Transactional
    public ResponseEntity<?> syncClassToQbo(@PathVariable Long sessionId) {
        Session session = sessionRepository.findById(sessionId)
                .orElseThrow(() -> new IllegalArgumentException("Session not found: " + sessionId));
        String qboClassRefId = syncQboClass(session);
        return ResponseEntity.ok(Map.of("synced", true, "qboClassRefId", qboClassRefId != null ? qboClassRefId : ""));
    }

    /**
     * Michael, 2026-08-25 -- Section 4c reporting extension ("session
     * linking"): the raw Session.copiedFromSession relationship
     * already existed and already carried this data -- but Session's
     * own default JSON serialization has no safeguard on it at all
     * (no @JsonIgnoreProperties), so a session copied forward
     * repeatedly over years (a recurring annual Public session, say)
     * would serialize an increasingly deep, fully-nested chain of
     * whole Session objects every time it's fetched. Not an infinite
     * loop -- the chain is finite -- but a real, growing inefficiency.
     * This returns just the minimal fields the UI actually needs for
     * a "copied from" link, without touching the existing Session
     * response shape at all (an additive, non-breaking change).
     */
    public record CopiedFromSummary(Long id, String locationName, java.time.LocalDate date) {}

    @GetMapping("/{sessionId}/copied-from")
    @Transactional(readOnly = true)
    public ResponseEntity<CopiedFromSummary> copiedFrom(@PathVariable Long sessionId) {
        Session session = sessionRepository.findById(sessionId)
                .orElseThrow(() -> new IllegalArgumentException("Session not found: " + sessionId));

        Session source = session.getCopiedFromSession();
        if (source == null) {
            return ResponseEntity.ok(null);
        }

        java.time.LocalDate sourceDate = sessionDayRepository.findFirstBySessionIdOrderBySessionDateAsc(source.getId())
                .map(SessionDay::getSessionDate)
                .orElse(null);

        return ResponseEntity.ok(new CopiedFromSummary(source.getId(), source.getLocationName(), sourceDate));
    }

    /**
     * Michael, 2026-08-25 -- QBO integration, layer 6. Today's scope
     * only: PRIVATE sessions, one flat-fee line -- see
     * QboInvoiceService's own docblock for the full reasoning.
     * Deliberately a real, unhandled exception on failure (not caught
     * and logged like the publish() Class sync) -- generating an
     * invoice is a manual, deliberate staff action; unlike publish(),
     * there's no reason to make it succeed silently through an
     * accounting failure.
     */
    @PostMapping("/{sessionId}/generate-qbo-invoice")
    public ResponseEntity<?> generateQboInvoice(@PathVariable Long sessionId) {
        Map<String, Object> invoice = qboInvoiceService.generatePrivateSessionInvoice(sessionId);
        return ResponseEntity.ok(invoice);
    }

    /**
     * Flat DTO instead of the raw entity -- SessionAuthorizedClient.client/
     * session are LAZY, and serializing the raw entity directly (as this
     * endpoint originally did) produced "client": null / "session": null
     * in the real response (confirmed live, 2026-08-16) -- the
     * Hibernate6Module fix from earlier correctly prevents a crash on an
     * uninitialized proxy, but that just means the data silently goes
     * missing instead. Field named "host" (not "isHost") to match what
     * Jackson actually produces for a boolean getter named isHost() --
     * confirmed against the real live response, since the OpenAPI docs'
     * claim of "isHost" turned out to be wrong too.
     */
    public record AuthorizedClientEntry(Long id, Long clientId, boolean host, Long addedByStaffId, OffsetDateTime addedAt) {
        static AuthorizedClientEntry from(SessionAuthorizedClient e) {
            return new AuthorizedClientEntry(
                    e.getId(),
                    e.getClient() != null ? e.getClient().getId() : null,
                    e.isHost(),
                    e.getAddedBy() != null ? e.getAddedBy().getId() : null,
                    e.getAddedAt());
        }
    }

    /**
     * @Transactional -- builds AuthorizedClientEntry DTOs by calling
     * .getClient() on freshly-fetched (lazy) entities; same class of fix
     * as SessionController.roster() -- see that comment for the full
     * explanation of why this is required, not just nice-to-have.
     */
    @GetMapping("/{sessionId}/authorized-clients")
    @Transactional(readOnly = true)
    public ResponseEntity<List<AuthorizedClientEntry>> listAuthorizedClients(@PathVariable Long sessionId) {
        return ResponseEntity.ok(authorizedClientRepository.findBySessionId(sessionId).stream()
                .map(AuthorizedClientEntry::from)
                .toList());
    }

    public record SetHostRequest(Long clientId, Long addedByStaffId) {}

    /**
     * Sets/replaces the host client on an existing Private/Semi-Private/
     * VTCA/Proposed session -- the actual way to set a Client for a
     * Private-type session, matching DIBs' "Company Name (Client)"
     * field in GENERAL (Michael, 2026-08-16). Rejected (409) for
     * Public/school types that don't take a host at all, via
     * SessionAuthorizationService's requireSchoolType check.
     */
    @PutMapping("/{sessionId}/host")
    public ResponseEntity<?> setHost(@PathVariable Long sessionId, @RequestBody SetHostRequest req) {
        Session session = sessionRepository.findById(sessionId)
                .orElseThrow(() -> new IllegalArgumentException("Session not found: " + sessionId));
        Client client = clientRepository.findById(req.clientId())
                .orElseThrow(() -> new IllegalArgumentException("Client not found: " + req.clientId()));
        StaffUser addedBy = req.addedByStaffId() != null
                ? staffUserRepository.findById(req.addedByStaffId()).orElse(null)
                : null;

        try {
            SessionAuthorizedClient host = authorizationService.setHost(session, client, addedBy);
            return ResponseEntity.ok(AuthorizedClientEntry.from(host));
        } catch (IllegalStateException e) {
            return ResponseEntity.status(HttpStatus.CONFLICT).body(Map.of("error", e.getMessage()));
        }
    }

    /**
     * Staff-triggered confirmation email to the session's host client
     * (Michael, 2026-08-19) -- exact wording confirmed with Michael, sent
     * from a button in Session Details' Bid area. NOT automatic, unlike
     * the certification email -- this only fires when a staff member
     * explicitly clicks the button. Requires a host client to already be
     * set (see setHost() above) with an email address, and at least one
     * SessionDay already scheduled.
     *
     * sendingStaffId (same day, real correction from Michael): Laravel-
     * to-Java calls always authenticate as the shared service account,
     * never the individual staff member's own credentials -- so Java
     * has no way to know who's really acting just from the request's
     * auth headers. Laravel must explicitly say who's sending, same
     * pattern as addedByStaffId on setHost() above. "Wouldn't want
     * someone else's confirmations coming to me -- it breaks the
     * chain" -- the sending staff member's own email is required for
     * the mailto reply-to to actually work, so a staff account with no
     * email on file is rejected with a clear error rather than
     * silently sending from nobody.
     */
    public record SendConfirmationEmailRequest(Long sendingStaffId) {}

    @PostMapping("/{sessionId}/send-confirmation-email")
    @Transactional
    public ResponseEntity<?> sendConfirmationEmail(@PathVariable Long sessionId, @RequestBody SendConfirmationEmailRequest req) {
        Session session = sessionRepository.findById(sessionId)
                .orElseThrow(() -> new IllegalArgumentException("Session not found: " + sessionId));

        SessionAuthorizedClient host = authorizedClientRepository.findBySessionIdAndIsHostTrue(sessionId)
                .orElse(null);
        if (host == null) {
            return ResponseEntity.status(HttpStatus.CONFLICT)
                    .body(Map.of("error", "No host client is set for this session -- set one before sending a confirmation email."));
        }
        Client hostClient = host.getClient();
        if (hostClient.getEmail() == null || hostClient.getEmail().isBlank()) {
            return ResponseEntity.status(HttpStatus.CONFLICT)
                    .body(Map.of("error", "The host client has no email address on file."));
        }

        if (req.sendingStaffId() == null) {
            return ResponseEntity.status(HttpStatus.CONFLICT)
                    .body(Map.of("error", "sendingStaffId is required -- the confirmation email's signature and reply-to must reflect who's actually sending it."));
        }
        StaffUser sender = staffUserRepository.findById(req.sendingStaffId()).orElse(null);
        if (sender == null) {
            return ResponseEntity.status(HttpStatus.CONFLICT)
                    .body(Map.of("error", "Sending staff user not found: " + req.sendingStaffId()));
        }
        if (sender.getEmail() == null || sender.getEmail().isBlank()) {
            return ResponseEntity.status(HttpStatus.CONFLICT)
                    .body(Map.of("error", "Your staff account has no email address on file -- set one before sending a confirmation email, so replies come back to you."));
        }

        LocalDate sessionDate = sessionDayRepository.findFirstBySessionIdOrderBySessionDateAsc(sessionId)
                .map(SessionDay::getSessionDate)
                .orElse(null);
        if (sessionDate == null) {
            return ResponseEntity.status(HttpStatus.CONFLICT)
                    .body(Map.of("error", "This session has no scheduled day yet -- add at least one date before sending a confirmation email."));
        }

        try {
            confirmationEmailService.sendConfirmationEmail(hostClient, session, sessionDate, sender);

            // Michael, 2026-08-22: log this in the existing Confirmation
            // & Team Comments section (reusing the CONFIRMATION comment
            // type, which already existed for exactly this purpose)
            // rather than building a separate tracking mechanism --
            // author/timestamp are already exactly what's needed to
            // show who sent it and when. Only logged on an actual
            // successful send; a failed attempt below never reaches
            // this line.
            SessionComment logEntry = new SessionComment();
            logEntry.setSession(session);
            logEntry.setCommentType(SessionCommentType.CONFIRMATION);
            logEntry.setText("Confirmation email sent to " + hostClient.getEmail() + ".");
            logEntry.setAuthor(sender);
            logEntry.setCreatedAtUtc(java.time.OffsetDateTime.now());
            sessionCommentRepository.save(logEntry);

            return ResponseEntity.ok(Map.of("sent", true, "to", hostClient.getEmail()));
        } catch (jakarta.mail.MessagingException e) {
            return ResponseEntity.status(HttpStatus.INTERNAL_SERVER_ERROR)
                    .body(Map.of("error", "Failed to send confirmation email: " + e.getMessage()));
        }
    }

    /**
     * Michael, 2026-08-19 -- matches a real bid template
     * (last_bid_sent.docx) he provided. Same per-sender pattern as
     * sendConfirmationEmail() above -- Laravel must tell Java who's
     * actually generating it, since the Laravel-to-Java connection
     * always authenticates as the shared service account. Increments
     * bidRevisionNumber BEFORE generating, so the PDF's own printed
     * Quote Number ("...-R2") always matches what actually got saved,
     * even if this is a re-generation. Requires a host client, a
     * scheduled day, and privateCost/fieldTest on file -- otherwise the
     * Cost section would show "$0" or blank, silently wrong rather
     * than a caught, explained error. selfPacedLecturePrice is
     * deliberately NOT required (Michael, 2026-08-19) -- defaults to
     * $50 via BidPdfService when unset, overridable later once the
     * Client Page/Employees feature exists.
     */
    public record GenerateBidRequest(Long sendingStaffId) {}

    @PostMapping("/{sessionId}/generate-bid")
    @Transactional
    public ResponseEntity<?> generateBid(@PathVariable Long sessionId, @RequestBody GenerateBidRequest req) {
        Session session = sessionRepository.findById(sessionId)
                .orElseThrow(() -> new IllegalArgumentException("Session not found: " + sessionId));

        SessionAuthorizedClient host = authorizedClientRepository.findBySessionIdAndIsHostTrue(sessionId)
                .orElse(null);
        if (host == null) {
            return ResponseEntity.status(HttpStatus.CONFLICT)
                    .body(Map.of("error", "No host client is set for this session -- set one before generating a bid."));
        }
        Client hostClient = host.getClient();

        SessionDay sessionDay = sessionDayRepository.findFirstBySessionIdOrderBySessionDateAsc(sessionId)
                .orElse(null);
        if (sessionDay == null) {
            return ResponseEntity.status(HttpStatus.CONFLICT)
                    .body(Map.of("error", "This session has no scheduled day yet -- add at least one date before generating a bid."));
        }

        List<String> missingPricing = new java.util.ArrayList<>();
        if (session.getPrivateCost() == null) missingPricing.add("Private Cost");
        if (session.getFieldTest() == null) missingPricing.add("Field Test");
        // selfPacedLecturePrice deliberately NOT required (Michael,
        // 2026-08-19) -- "that by default is $50 regardless. We will
        // set up and override later when we build the Client Page with
        // Employees." BidPdfService falls back to $50 when this is null.
        if (!missingPricing.isEmpty()) {
            return ResponseEntity.status(HttpStatus.CONFLICT)
                    .body(Map.of("error", "Missing pricing before a bid can be generated: " + String.join(", ", missingPricing) + "."));
        }

        if (req.sendingStaffId() == null) {
            return ResponseEntity.status(HttpStatus.CONFLICT)
                    .body(Map.of("error", "sendingStaffId is required -- the bid's signature and contact info must reflect who's actually generating it."));
        }
        StaffUser sender = staffUserRepository.findById(req.sendingStaffId()).orElse(null);
        if (sender == null) {
            return ResponseEntity.status(HttpStatus.CONFLICT)
                    .body(Map.of("error", "Sending staff user not found: " + req.sendingStaffId()));
        }

        session.setBidRevisionNumber(session.getBidRevisionNumber() + 1);

        try {
            bidPdfService.generate(session, hostClient, sessionDay, sender);
            session.setLastBidGeneratedAt(java.time.OffsetDateTime.now());
            sessionRepository.save(session);
            return ResponseEntity.ok(Map.of("quoteNumber", session.getBidQuoteNumber(), "revision", session.getBidRevisionNumber()));
        } catch (java.io.IOException e) {
            return ResponseEntity.status(HttpStatus.INTERNAL_SERVER_ERROR)
                    .body(Map.of("error", "Failed to generate bid PDF: " + e.getMessage()));
        }
    }

    /**
     * Mirrors CertificationController.downloadPdf()'s established
     * pattern -- file path stored on the record, served here as a raw
     * PDF resource.
     */
    @GetMapping("/{sessionId}/bid-pdf")
    public ResponseEntity<org.springframework.core.io.Resource> downloadBidPdf(@PathVariable Long sessionId) {
        Session session = sessionRepository.findById(sessionId)
                .orElseThrow(() -> new IllegalArgumentException("Session not found: " + sessionId));

        if (session.getLastBidPdfPath() == null) {
            return ResponseEntity.notFound().build();
        }
        java.io.File file = new java.io.File(session.getLastBidPdfPath());
        if (!file.exists()) {
            return ResponseEntity.notFound().build();
        }

        org.springframework.core.io.Resource resource = new org.springframework.core.io.FileSystemResource(file);
        return ResponseEntity.ok()
                .contentType(org.springframework.http.MediaType.APPLICATION_PDF)
                .header(org.springframework.http.HttpHeaders.CONTENT_DISPOSITION, "attachment; filename=\"" + file.getName() + "\"")
                .body(resource);
    }

    /**
     * Michael, 2026-08-23 -- shows which PO would actually apply to
     * this session (its own poForInvoice if set, otherwise the host
     * client's Persistent PO expiring soonest) WITHOUT generating an
     * invoice -- there's no real QuickBooks connection to build actual
     * invoicing against yet. See SessionPoResolutionService's own
     * Javadoc for the full precedence rules confirmed with Michael.
     * @Transactional to safely resolve host.getClient() -- a lazy
     * relationship.
     */
    @GetMapping("/{sessionId}/resolved-po")
    @Transactional(readOnly = true)
    public ResponseEntity<SessionPoResolutionService.ResolvedPo> getResolvedPo(@PathVariable Long sessionId) {
        Session session = sessionRepository.findById(sessionId)
                .orElseThrow(() -> new IllegalArgumentException("Session not found: " + sessionId));
        return ResponseEntity.ok(poResolutionService.resolve(session));
    }

    public record AuthorizeClientRequest(Long clientId, Long addedByStaffId) {}

    /**
     * Section 4: rejected (409) if the session is Private -- enforcement
     * happens in {@link SessionAuthorizationService}, not here.
     */
    @PostMapping("/{sessionId}/authorized-clients")
    public ResponseEntity<?> authorizeClient(@PathVariable Long sessionId, @RequestBody AuthorizeClientRequest req) {
        Session session = sessionRepository.findById(sessionId)
                .orElseThrow(() -> new IllegalArgumentException("Session not found: " + sessionId));
        Client client = clientRepository.findById(req.clientId())
                .orElseThrow(() -> new IllegalArgumentException("Client not found: " + req.clientId()));
        StaffUser addedBy = req.addedByStaffId() != null
                ? staffUserRepository.findById(req.addedByStaffId()).orElse(null)
                : null;

        try {
            SessionAuthorizedClient result = authorizationService.authorizeOutsideClient(session, client, addedBy);
            return ResponseEntity.status(HttpStatus.CREATED).body(AuthorizedClientEntry.from(result));
        } catch (IllegalStateException ex) {
            // Session is Private, or client already authorized -- Section 4's hard rule.
            return ResponseEntity.status(HttpStatus.CONFLICT).body(Map.of("error", ex.getMessage()));
        }
    }

    @GetMapping("/{sessionId}/summary-email-readiness")
    public ResponseEntity<Map<String, Boolean>> summaryEmailReadiness(@PathVariable Long sessionId) {
        List<Enrollment> roster = enrollmentRepository.findBySessionId(sessionId);
        return ResponseEntity.ok(Map.of("ready", closeOutService.isSummaryEmailReady(roster)));
    }

    /**
     * Section 4g: 409 if the roster isn't fully resolved yet -- mirrors
     * the same enforcement pattern as the Private-session 409 above,
     * rather than silently sending an incomplete summary.
     */
    @PostMapping("/{sessionId}/send-summary-email")
    public ResponseEntity<?> sendSummaryEmail(@PathVariable Long sessionId) {
        List<Enrollment> roster = enrollmentRepository.findBySessionId(sessionId);
        if (!closeOutService.isSummaryEmailReady(roster)) {
            return ResponseEntity.status(HttpStatus.CONFLICT)
                    .body(Map.of("error", "Roster is not fully resolved -- every entry needs a terminal status (CERTIFIED/DNC/DNA)."));
        }
        // Actual email dispatch (Brevo/SMTP) is a downstream integration
        // concern, not implemented in this scaffold pass.
        return ResponseEntity.ok(Map.of("sent", true));
    }

    // -----------------------------------------------------------------
    // Session Days -- Section 3/4d, multi-day scheduling. Missing until
    // now: SessionDayRepository was only ever used for READING dates
    // (calendar, comparisons) -- there was no way to actually GIVE a
    // session a date at all, meaning every session created through the
    // API has always shown as "Unscheduled." Confirmed as a real gap
    // while testing the dashboard's Today/Tomorrow feature, 2026-08-16.
    // -----------------------------------------------------------------

    public record SessionDayRequest(Integer dayNumber, LocalDate sessionDate, LocalTime startTime, LocalTime endTime) {}

    @PostMapping("/{sessionId}/days")
    public ResponseEntity<SessionDay> addDay(@PathVariable Long sessionId, @RequestBody SessionDayRequest req) {
        Session session = sessionRepository.findById(sessionId)
                .orElseThrow(() -> new IllegalArgumentException("Session not found: " + sessionId));
        SessionDay day = new SessionDay();
        day.setSession(session);
        day.setDayNumber(req.dayNumber() != null ? req.dayNumber() : 1);
        day.setSessionDate(req.sessionDate());
        day.setStartTime(req.startTime() != null ? req.startTime() : LocalTime.of(8, 0));
        day.setEndTime(req.endTime() != null ? req.endTime() : LocalTime.of(17, 0));
        return ResponseEntity.status(HttpStatus.CREATED).body(sessionDayRepository.save(day));
    }

    @GetMapping("/{sessionId}/days")
    public ResponseEntity<List<SessionDay>> listDays(@PathVariable Long sessionId) {
        return ResponseEntity.ok(sessionDayRepository.findBySessionIdOrderByDayNumber(sessionId));
    }

    /**
     * Michael, 2026-08-19, found live during testing -- days could be
     * added but never removed. Validates the day actually belongs to
     * THIS session (not just any day by id) before deleting -- a real
     * data-integrity check, not just a formality, given dayId alone
     * would otherwise let a caller delete a day under the wrong
     * session's URL. @Transactional here on principle -- day.getSession()
     * is a lazy relationship, and the exact same access pattern caused
     * a real LazyInitializationException elsewhere in this file earlier
     * today (see sendConfirmationEmail() above).
     */
    @DeleteMapping("/{sessionId}/days/{dayId}")
    @Transactional
    public ResponseEntity<?> deleteDay(@PathVariable Long sessionId, @PathVariable Long dayId) {
        SessionDay day = sessionDayRepository.findById(dayId)
                .orElseThrow(() -> new IllegalArgumentException("Session day not found: " + dayId));
        if (!day.getSession().getId().equals(sessionId)) {
            return ResponseEntity.status(HttpStatus.CONFLICT)
                    .body(Map.of("error", "Session day " + dayId + " does not belong to session " + sessionId));
        }
        sessionDayRepository.delete(day);
        return ResponseEntity.noContent().build();
    }

    // -----------------------------------------------------------------
    // Session Comments -- Section 4c. Write-once: no PATCH/DELETE exists
    // for this resource anywhere in the app, deliberately.
    // -----------------------------------------------------------------

    /**
     * Michael, 2026-08-19, found live during testing -- the comment
     * text saved correctly, but the display showed "TEAM_COMMENT --
     * ," with nothing between the dash and comma. Root cause: this
     * endpoint was returning the raw SessionComment entity, which has
     * `author` (a StaffUser relationship) and `createdAtUtc`, but the
     * view has always expected `authorUsername` and `createdAtCentral`
     * -- neither of which ever actually existed anywhere. This bug
     * predates today's Session Details reordering entirely; it was
     * just never exercised until now. @Transactional here specifically
     * to safely access the lazy `author` relationship -- the exact
     * same class of bug (LazyInitializationException) hit multiple
     * times elsewhere in this file tonight.
     */
    public record CommentResponse(Long id, SessionCommentType commentType, String text,
                                   String authorInitials, String createdAtCentral) {}

    @GetMapping("/{sessionId}/comments")
    @Transactional(readOnly = true)
    public ResponseEntity<List<CommentResponse>> listComments(@PathVariable Long sessionId) {
        java.time.format.DateTimeFormatter centralFormat = java.time.format.DateTimeFormatter.ofPattern("M/d/yyyy h:mm a");
        java.time.ZoneId central = java.time.ZoneId.of("America/Chicago");

        List<CommentResponse> comments = sessionCommentRepository.findBySessionIdOrderByCreatedAtUtc(sessionId).stream()
                .map(c -> new CommentResponse(
                        c.getId(),
                        c.getCommentType(),
                        c.getText(),
                        // Michael, 2026-08-19 -- initials (e.g. "DWM"),
                        // not the login username, for a more natural
                        // display in the comment thread. Falls back to
                        // username if a staff member hasn't set their
                        // initials yet, rather than showing blank.
                        c.getAuthor().getInitials() != null ? c.getAuthor().getInitials() : c.getAuthor().getUsername(),
                        c.getCreatedAtUtc().atZoneSameInstant(central).format(centralFormat)))
                .toList();
        return ResponseEntity.ok(comments);
    }

    public record AddCommentRequest(SessionCommentType commentType, String text, Long authorId) {}

    @PostMapping("/{sessionId}/comments")
    public ResponseEntity<SessionComment> addComment(@PathVariable Long sessionId, @RequestBody AddCommentRequest req) {
        Session session = sessionRepository.findById(sessionId)
                .orElseThrow(() -> new IllegalArgumentException("Session not found: " + sessionId));
        StaffUser author = staffUserRepository.findById(req.authorId())
                .orElseThrow(() -> new IllegalArgumentException("Staff user not found: " + req.authorId()));

        SessionComment comment = new SessionComment();
        comment.setSession(session);
        comment.setCommentType(req.commentType());
        comment.setText(req.text());
        comment.setAuthor(author);
        comment.setCreatedAtUtc(OffsetDateTime.now());

        return ResponseEntity.status(HttpStatus.CREATED).body(sessionCommentRepository.save(comment));
    }

    // -----------------------------------------------------------------
    // Copy Forward 6 Months -- Section 4c
    // -----------------------------------------------------------------

    @PostMapping("/{sessionId}/copy-forward")
    @Transactional
    public ResponseEntity<Session> copyForward(@PathVariable Long sessionId) {
        Session original = sessionRepository.findById(sessionId)
                .orElseThrow(() -> new IllegalArgumentException("Session not found: " + sessionId));
        // savedCopy.copiedFromSession references this same object -- if
        // Jackson recursively serializes into it, its own lazy fields
        // need to be initialized too, or that nested object would have
        // the identical null-field problem this whole fix addresses.
        initializeSessionLazyRelations(original);

        Session copy = copyForwardService.copyForward(original);
        Session savedCopy = sessionRepository.save(copy);

        // SessionAuthorizedClient rows carry forward per Section 4c --
        // handled here since it's a separate table keyed on the new ID.
        for (SessionAuthorizedClient original_ac : authorizedClientRepository.findBySessionId(sessionId)) {
            SessionAuthorizedClient copiedEntry = new SessionAuthorizedClient();
            copiedEntry.setSession(savedCopy);
            copiedEntry.setClient(original_ac.getClient());
            copiedEntry.setHost(original_ac.isHost());
            copiedEntry.setAddedAt(OffsetDateTime.now());
            authorizedClientRepository.save(copiedEntry);
        }

        // SessionNotifiedClient ("Clients to be Notified") also carries
        // forward per DIBs' own "Is copied on session-copy" label --
        // same reasoning as SessionAuthorizedClient above.
        for (SessionNotifiedClient original_nc : notifiedClientRepository.findBySessionId(sessionId)) {
            SessionNotifiedClient copiedEntry = new SessionNotifiedClient();
            copiedEntry.setSession(savedCopy);
            copiedEntry.setClient(original_nc.getClient());
            copiedEntry.setCreatedAt(OffsetDateTime.now());
            notifiedClientRepository.save(copiedEntry);
        }

        initializeSessionLazyRelations(savedCopy);
        return ResponseEntity.status(HttpStatus.CREATED).body(savedCopy);
    }

    // -----------------------------------------------------------------
    // Roster -- Section 4f
    // -----------------------------------------------------------------

    /**
     * @Transactional keeps one Hibernate session open across BOTH the
     * enrollment fetch and RosterService's use of their lazy fields
     * (student/client/certifyingRun/practiceRun). Without this, the fetch
     * and the lazy access happen in two different (auto-)transactions --
     * opening a new transaction inside RosterService itself would NOT
     * fix it, since Hibernate proxies are permanently bound to the
     * session that created them, not re-attachable to a later one.
     */
    @GetMapping("/{sessionId}/roster")
    @Transactional(readOnly = true)
    public ResponseEntity<List<RosterService.RosterEntry>> roster(@PathVariable Long sessionId) {
        Session session = sessionRepository.findById(sessionId)
                .orElseThrow(() -> new IllegalArgumentException("Session not found: " + sessionId));
        List<Enrollment> enrollments = enrollmentRepository.findBySessionId(sessionId);
        return ResponseEntity.ok(rosterService.buildRoster(session, enrollments));
    }
}
