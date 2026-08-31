package com.caa.platform.common;

/**
 * Section 3b: region is auto-derived from a Session's physical address,
 * not manually selected. Staff can override if the derived region is
 * inaccurate (override is logged). Western and Texas are the two regions
 * where split-run certification is never permitted (Section 3b).
 */
public enum RegionType {
    CENTRAL,
    EASTERN,
    WESTERN,
    TEXAS
}
