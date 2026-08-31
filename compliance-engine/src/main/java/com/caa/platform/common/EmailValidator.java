package com.caa.platform.common;

import java.util.regex.Pattern;

/**
 * Michael, 2026-08-23 -- certificates are emailed to the student on
 * successful certification, so a valid email is required. Enforced in
 * two places, both using this same check: at Student creation going
 * forward, and again at Enrollment creation as the real backstop --
 * closes the gap for any student record that predates this
 * requirement, or that otherwise ended up without one.
 *
 * Deliberately a simple presence + shape check (something@something.tld),
 * not full RFC 5322 validation -- that's notoriously complex and mostly
 * overkill; this catches the realistic mistakes (blank, missing @,
 * no domain) without being needlessly strict.
 */
public final class EmailValidator {

    private static final Pattern PATTERN = Pattern.compile("^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$");

    private EmailValidator() {
    }

    public static boolean isValid(String email) {
        return email != null && !email.isBlank() && PATTERN.matcher(email.trim()).matches();
    }
}
