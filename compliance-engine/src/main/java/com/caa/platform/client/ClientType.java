package com.caa.platform.client;

/**
 * Michael, 2026-08-19 -- distinguishes Individual (a single person is
 * both the account holder and the test-taker) from Organization (the
 * account holder manages employees separately, added later via the
 * org portal). Deliberately an explicit column, not inferred from
 * whether Client.company is blank -- much harder to get wrong later.
 */
public enum ClientType {
    INDIVIDUAL,
    ORGANIZATION
}
