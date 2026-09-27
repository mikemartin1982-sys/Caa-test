-- Scrambles client/student contact emails in a SANDBOX copy of caa_platform
-- so real outbound email (Brevo SMTP / Brevo API) can never reach real
-- clients or students during development. Never run this against the live
-- production database.
--
-- Addresses become <table>-<id>@caatest.invalid (".invalid" is reserved and
-- can never be delivered). Staff emails are left alone so password resets
-- keep working. Addresses matching the keep list below are left alone too.
--
-- Safe to re-run (already-scrambled addresses are skipped), e.g. after
-- re-copying the database from the PC.
--
-- Preview (changes nothing, rolls back):
--   psql -h 127.0.0.1 -U caa_engine_user -d caa_platform -v apply=0 -f scramble-emails.sql
-- Apply:
--   psql -h 127.0.0.1 -U caa_engine_user -d caa_platform -v apply=1 -f scramble-emails.sql

\set ON_ERROR_STOP on
BEGIN;

-- Addresses to KEEP real (SQL LIKE patterns, case-insensitive). Add your own
-- test client/student addresses here so you can still log in and receive
-- mail as them.
CREATE TEMP TABLE keep_email (pattern text) ON COMMIT DROP;
INSERT INTO keep_email VALUES
    ('%@compliance-assurance.com'),
    ('mike.martin1982@gmail.com');

CREATE TEMP TABLE scramble_report (source text, before_real int, scrambled int) ON COMMIT DROP;

-- true when the address should be replaced
CREATE FUNCTION pg_temp.should_scramble(addr text) RETURNS boolean
LANGUAGE sql STABLE AS $$
    SELECT addr IS NOT NULL
       AND btrim(addr) <> ''
       AND lower(addr) NOT LIKE '%@caatest.invalid'
       AND NOT EXISTS (SELECT 1 FROM keep_email k WHERE lower(btrim(addr)) LIKE lower(k.pattern))
$$;

-- clients.email (also the client portal login)
INSERT INTO scramble_report SELECT 'clients.email', count(*) FILTER (WHERE email LIKE '%@%'), count(*) FILTER (WHERE pg_temp.should_scramble(email)) FROM clients;
UPDATE clients SET email = 'client-' || id || '@caatest.invalid' WHERE pg_temp.should_scramble(email);

-- clients.billing_email
INSERT INTO scramble_report SELECT 'clients.billing_email', count(*) FILTER (WHERE billing_email LIKE '%@%'), count(*) FILTER (WHERE pg_temp.should_scramble(billing_email)) FROM clients;
UPDATE clients SET billing_email = 'client-billing-' || id || '@caatest.invalid' WHERE pg_temp.should_scramble(billing_email);

-- students.email (NOT NULL)
INSERT INTO scramble_report SELECT 'students.email', count(*) FILTER (WHERE email LIKE '%@%'), count(*) FILTER (WHERE pg_temp.should_scramble(email)) FROM students;
UPDATE students SET email = 'student-' || id || '@caatest.invalid' WHERE pg_temp.should_scramble(email);

-- inquiries.email
INSERT INTO scramble_report SELECT 'inquiries.email', count(*) FILTER (WHERE email LIKE '%@%'), count(*) FILTER (WHERE pg_temp.should_scramble(email)) FROM inquiries;
UPDATE inquiries SET email = 'inquiry-' || id || '@caatest.invalid' WHERE pg_temp.should_scramble(email);

-- client_purchase_orders.contact_email (VARCHAR(50))
INSERT INTO scramble_report SELECT 'client_purchase_orders.contact_email', count(*) FILTER (WHERE contact_email LIKE '%@%'), count(*) FILTER (WHERE pg_temp.should_scramble(contact_email)) FROM client_purchase_orders;
UPDATE client_purchase_orders SET contact_email = 'po-' || id || '@caatest.invalid' WHERE pg_temp.should_scramble(contact_email);

-- chart_recorder_exports.recipients (comma-separated list; each address
-- handled on its own so kept staff addresses survive)
INSERT INTO scramble_report
SELECT 'chart_recorder_exports.recipients',
       count(*) FILTER (WHERE r.addr LIKE '%@%'),
       count(*) FILTER (WHERE pg_temp.should_scramble(r.addr))
FROM chart_recorder_exports e
CROSS JOIN LATERAL (SELECT btrim(x) AS addr FROM unnest(string_to_array(e.recipients, ',')) AS x) r;

UPDATE chart_recorder_exports e
SET recipients = s.new_list
FROM (
    SELECT e2.id,
           string_agg(CASE WHEN pg_temp.should_scramble(btrim(a.raw))
                           THEN 'export-' || e2.id || '-' || a.ord || '@caatest.invalid'
                           ELSE btrim(a.raw) END,
                      ', ' ORDER BY a.ord) AS new_list
    FROM chart_recorder_exports e2
    CROSS JOIN LATERAL unnest(string_to_array(e2.recipients, ',')) WITH ORDINALITY AS a(raw, ord)
    GROUP BY e2.id
) s
WHERE e.id = s.id
  AND EXISTS (SELECT 1 FROM unnest(string_to_array(e.recipients, ',')) AS x WHERE pg_temp.should_scramble(btrim(x)));

\echo
\echo 'Email addresses found / to be scrambled (kept = found - scrambled):'
SELECT source, before_real AS found, scrambled FROM scramble_report ORDER BY source;

\if :apply
    COMMIT;
    \echo 'APPLIED: changes committed.'
\else
    ROLLBACK;
    \echo 'PREVIEW ONLY: nothing was changed. Rerun with -v apply=1 to apply.'
\endif
