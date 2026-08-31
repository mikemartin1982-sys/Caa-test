package com.caa.platform.session;

import com.caa.platform.equipment.Trailer;
import com.caa.platform.equipment.TrailerRepository;
import com.caa.platform.equipment.Truck;
import com.caa.platform.equipment.TruckRepository;
import com.caa.platform.staff.StaffUser;
import com.caa.platform.staff.StaffUserRepository;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Propagation;
import org.springframework.transaction.annotation.Transactional;

/**
 * Michael, 2026-08-29 -- staff calendar, Phase 4 (drag-and-drop
 * staging + bulk save). Confirmed with Michael:
 *  - Delta-based, not DIBs' own full-state resubmit -- rare for two
 *    staff to drag-and-drop at the same moment, but not unusual for
 *    several (the logistics team plus Joe and Derek) to be editing
 *    different sessions concurrently. A full-snapshot resubmit could
 *    silently overwrite someone else's unrelated, concurrent edit;
 *    a change list of exactly what moved cannot.
 *  - Best-effort, not atomic -- valid changes save, the one bad one
 *    is reported back separately so staff can resolve just that one.
 *  - No double-booking enforcement at all -- purely a visual prompt
 *    (buildDailyAvailability()'s new doubleBookedStaffInitials),
 *    never a block. Some sessions can legitimately double-book (a
 *    morning session at one location, an afternoon session at
 *    another) -- staff judgment decides, not this service.
 *
 * REQUIRES_NEW on applyChange(), not the default propagation: this
 * gets called in a loop from the controller, one call per staged
 * change. Same class of bug already found and fixed during the QBO
 * integration work -- if these all shared one transaction, a single
 * invalid change would mark that WHOLE transaction rollback-only the
 * instant it threw, silently defeating "best-effort" even with a
 * per-item try/catch in the caller: catching the exception doesn't
 * un-poison a transaction once Spring has already flagged it.
 * REQUIRES_NEW gives each change its own, fully independent
 * transaction that can fail and roll back entirely on its own.
 */
@Service
public class CalendarAssignmentService {

    public enum Slot { FIELD_MANAGER, OPERATOR, PROCTOR1, PROCTOR2, PROCTOR3, TRUCK, TRAILER }

    /** newValueId is nullable -- null means "unassign this slot," not "leave unchanged." */
    public record AssignmentChange(Long sessionId, Slot slot, Long newValueId) {}
    public record AssignmentChangeResult(Long sessionId, Slot slot, boolean success, String errorMessage) {}

    private final SessionRepository sessionRepository;
    private final StaffUserRepository staffUserRepository;
    private final TruckRepository truckRepository;
    private final TrailerRepository trailerRepository;

    public CalendarAssignmentService(SessionRepository sessionRepository, StaffUserRepository staffUserRepository,
                                      TruckRepository truckRepository, TrailerRepository trailerRepository) {
        this.sessionRepository = sessionRepository;
        this.staffUserRepository = staffUserRepository;
        this.truckRepository = truckRepository;
        this.trailerRepository = trailerRepository;
    }

    @Transactional(propagation = Propagation.REQUIRES_NEW)
    public AssignmentChangeResult applyChange(AssignmentChange change) {
        try {
            Session session = sessionRepository.findById(change.sessionId())
                    .orElseThrow(() -> new IllegalArgumentException("Session not found: " + change.sessionId()));

            switch (change.slot()) {
                case FIELD_MANAGER -> session.setFieldManager(resolveStaff(change.newValueId()));
                case OPERATOR -> session.setOperator(resolveStaff(change.newValueId()));
                case PROCTOR1 -> session.setProctor1(resolveStaff(change.newValueId()));
                case PROCTOR2 -> session.setProctor2(resolveStaff(change.newValueId()));
                case PROCTOR3 -> session.setProctor3(resolveStaff(change.newValueId()));
                case TRUCK -> session.setTruck(resolveTruck(change.newValueId()));
                case TRAILER -> session.setTrailer(resolveTrailer(change.newValueId()));
            }

            sessionRepository.save(session);
            return new AssignmentChangeResult(change.sessionId(), change.slot(), true, null);
        } catch (Exception e) {
            // Michael, 2026-08-29 -- caught here, not left to propagate:
            // this is the actual mechanism that makes "best-effort"
            // real. Each change already has its own REQUIRES_NEW
            // transaction, so this failure rolling back only affects
            // THIS change -- but the exception still has to be caught
            // here (not just given its own transaction) so the
            // caller's loop can continue to the next change at all,
            // rather than the exception propagating up and aborting
            // the whole batch.
            return new AssignmentChangeResult(change.sessionId(), change.slot(), false, e.getMessage());
        }
    }

    private StaffUser resolveStaff(Long id) {
        if (id == null) return null;
        return staffUserRepository.findById(id)
                .orElseThrow(() -> new IllegalArgumentException("Staff user not found: " + id));
    }

    private Truck resolveTruck(Long id) {
        if (id == null) return null;
        return truckRepository.findById(id)
                .orElseThrow(() -> new IllegalArgumentException("Truck not found: " + id));
    }

    private Trailer resolveTrailer(Long id) {
        if (id == null) return null;
        return trailerRepository.findById(id)
                .orElseThrow(() -> new IllegalArgumentException("Trailer not found: " + id));
    }
}
