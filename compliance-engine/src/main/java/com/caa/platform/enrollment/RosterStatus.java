package com.caa.platform.enrollment;

/**
 * Section 4f/4g: ARR and CERTIFIED auto-populate (Digital Testing sign-in,
 * passing certification). DNC/DNA are staff-set. Drives Section 4g's
 * close-out logic -- only a terminal status (CERTIFIED/DNC/DNA) counts
 * toward the "Send Summary Email" button becoming active.
 */
public enum RosterStatus {
    ARR,
    CERTIFIED,
    DNC,
    DNA;

    public boolean isTerminal() {
        return this != ARR;
    }
}
