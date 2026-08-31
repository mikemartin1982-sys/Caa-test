# CAA Platform — Database Schema

PostgreSQL schema derived from `CAA-Website-Rebuild-Architecture.md`. This is the
first scaffolding layer — the Java/Spring Boot entities and the internal API
contract should be built against this schema next.

## Migration order (must run in this sequence — later files depend on earlier ones)

| # | File | Covers | Doc section |
|---|---|---|---|
| 001 | `core_client_student.sql` | Staff, Clients, Inquiries, Students | 3, 3c |
| 002 | `session_scheduling.sql` | Sessions, SessionDays, authorized clients, staggered blocks, comments | 3, 3a, 4c, 4d |
| 003 | `enrollment_payment.sql` | Enrollments, Payments, VR token blocks | 3, 3a, 4f, 4g, 7 |
| 004 | `equipment_calibration.sql` | Trailers, testing systems, 5-Filter, Chart Recorder | 4h |
| 005 | `certification_scoring.sql` | Certifications, Runs, Observations, split-run | 3, 3b |
| 006 | `vr_coaching.sql` | VR outreach log | 4b |

Migrations 004 and 005 have circular-ish dependencies (a `CertificationRun`
references a `TestingSystem`, and a `ComplianceFlag` references a
`CertificationRun`) — resolved with deferred `ALTER TABLE ... ADD CONSTRAINT`
statements at the end of 004/005 rather than reordering everything into one
giant file. `students.texas_practice_run_id` and both
`enrollments.certifying_run_id` / `practice_run_id` are wired up the same way
at the end of 005.

## Running the migrations

```bash
createdb caa_platform
for f in migrations/*.sql; do
  psql -d caa_platform -v ON_ERROR_STOP=1 -f "$f"
done
```

## What's deliberately enforced at the DB layer vs. the app layer

**DB-enforced (CHECK constraints / unique indexes):**
- A `CertificationRun` is exactly 50 or 25 points, never anything else
- A 25-point run can never carry Black data (Black is never split — Section 3b)
- Every `Observation` opacity value is a multiple of 5 (Section 4g)
- Exactly one `is_host = true` row per session in `session_authorized_clients`
- `quoted_headcount` only applies to Private/Semi-Private sessions

**App-layer only (not DB constraints), because the rule is a business/UX
decision rather than a structural one:**
- Private is locked to host-only (no additions) — Semi-Private is what
  unlocks the self-service mechanism (Section 4). The DB allows either;
  the application must block adding non-host rows when `school_type = 'private'`.
- `SessionComment` and `SplitRunAuthorization` immutability (write-once) —
  enforce via application logic and/or `REVOKE UPDATE, DELETE` on the
  app's DB role once a real deployment role exists.
- The Texas proctor rule's hard-block-vs-warn behavior (still pending
  Derek/Joe's decision, Section 9) — build behind a swappable rule, not a
  hardcoded constraint, since the answer isn't settled yet.
- Region auto-derivation from address, and the half-mile GPS-pin warning
  (Section 4d) — geocoding is an external API call, not something a DB
  constraint can do.

## Verified

All 6 migrations run cleanly against Postgres 16 with no errors. Spot-tested
the following constraints directly against the schema:
- Two different clients cannot both be marked host on the same session
- A Semi-Private session correctly allows a non-host outside attendee
- A 25-point run rejects any Black data
- An observation with a non-multiple-of-5 opacity value is rejected
