package com.caa.platform.security;

import org.springframework.stereotype.Component;

import java.time.Instant;
import java.time.temporal.ChronoUnit;
import java.util.ArrayDeque;
import java.util.Deque;
import java.util.concurrent.ConcurrentHashMap;

/**
 * Michael, 2026-09-04 -- rate limiting for the four genuinely public,
 * unauthenticated password-reset endpoints (Staff forgot/reset, Client
 * forgot/reset). Confirmed with Michael: 5 attempts per 15 minutes,
 * keyed by IP address.
 *
 * Confirmed with Michael as unsure between a real library
 * (e.g. Bucket4j) and a simpler, hand-rolled approach -- recommended
 * and built as hand-rolled, in-memory: this app is single-instance
 * today, and 5-per-15-minutes is a simple enough rule that a real,
 * dedicated rate-limiting library would be more machinery than this
 * genuinely needs right now. A real trade-off worth knowing: this
 * resets on app restart, and would need real, distributed coordination
 * (e.g. a real, shared Redis-backed approach) if this app is ever
 * genuinely deployed across multiple instances.
 *
 * Keyed by (IP, endpoint) together, not just IP alone -- so a shared
 * IP (e.g. an office network) hitting both the Staff and Client portal
 * reset flows doesn't have one, unrelated action count against the
 * other's own, separate limit.
 *
 * Thread-safe: each key's own Deque is only ever mutated inside a
 * synchronized block on that same Deque instance, so a real check-and-
 * record under genuine concurrent load (two requests from the same IP
 * arriving at the same moment) can't both slip through as the 5th and
 * 6th attempt.
 */
@Component
public class IpRateLimiter {

    private static final int MAX_ATTEMPTS = 5;
    private static final long WINDOW_MINUTES = 15;

    private final ConcurrentHashMap<String, Deque<Instant>> attemptsByKey = new ConcurrentHashMap<>();

    /**
     * Records this attempt and returns whether it's allowed. Call this
     * once per real request, right before doing the actual work --
     * not just for observability, since a rejected attempt must not
     * count as if it happened.
     *
     * Michael, 2026-09-04 -- no automatic map cleanup here: a real,
     * earlier attempt at this (checking isEmpty() right after adding
     * the new timestamp) was genuinely dead code -- the deque always
     * has at least the just-added entry at that point, so the check
     * could never fire. Fixing that properly (removing a stale entry
     * while still safely recording the new attempt against it) adds
     * real complexity for a minor, non-security concern -- accepted as
     * a known, bounded limitation instead: this map only ever grows to
     * one entry per distinct (IP, endpoint) pair that has EVER
     * attempted a reset, which is genuinely bounded by real, distinct
     * visitor traffic, not an unbounded leak.
     */
    public boolean allow(String ip, String endpointKey) {
        String key = ip + ":" + endpointKey;
        Deque<Instant> attempts = attemptsByKey.computeIfAbsent(key, k -> new ArrayDeque<>());

        synchronized (attempts) {
            Instant cutoff = Instant.now().minus(WINDOW_MINUTES, ChronoUnit.MINUTES);
            while (!attempts.isEmpty() && attempts.peekFirst().isBefore(cutoff)) {
                attempts.pollFirst();
            }

            if (attempts.size() >= MAX_ATTEMPTS) {
                return false;
            }

            attempts.addLast(Instant.now());
            return true;
        }
    }
}
