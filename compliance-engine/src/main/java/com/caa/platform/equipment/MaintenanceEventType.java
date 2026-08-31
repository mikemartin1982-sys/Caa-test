package com.caa.platform.equipment;

/** Section 4h: either triggers a new 5-Filter requirement, independent of the 6-month schedule. */
public enum MaintenanceEventType {
    SIGNIFICANT_REPAIR,  // a component within a piece is swapped out
    REPLACE              // an entire piece is replaced outright
}
