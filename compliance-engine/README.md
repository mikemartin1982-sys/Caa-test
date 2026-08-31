# CAA Compliance Engine (Java / Spring Boot)

Owns session/scheduling logic, certification records, Method 9 scoring,
QuickBooks invoice creation and status sync, and Brevo triggers (Section 2
of the architecture doc).

## Maven Wrapper (recommended for Windows/PowerShell)

The project now includes a Maven Wrapper (`mvnw.cmd` / `mvnw`) so you don't
need Maven installed globally — it downloads Maven 3.9.9 automatically on
first run, using the version pinned in `.mvn/wrapper/maven-wrapper.properties`.

**PowerShell:**
```powershell
cd path\to\caa-platform\compliance-engine
$env:JAVA_HOME = "C:\Program Files\Java\jdk-21"   # if not already set
.\mvnw.cmd clean compile
```

**Honesty note:** `mvnw` (the Unix version) passed a real `sh -n` syntax
check in this sandbox. `mvnw.cmd` could not be executed or tested at all
here — there's no Windows environment available, and the sandbox's network
restrictions would block the wrapper's own jar download even if there
were. I wrote it carefully against the standard Apache Maven Wrapper 3.3.2
format, but this is the one piece in the whole project I have zero
execution evidence for, unlike everything else in this repo.

**If `mvnw.cmd` doesn't work:** the safest fallback is to generate a
guaranteed-correct wrapper yourself, once you have Maven installed
locally (`winget install Apache.Maven`):
```powershell
mvn -N wrapper:wrapper -Dmaven=3.9.9
```
That regenerates `mvnw.cmd`, `mvnw`, and the `.mvn/wrapper/` files directly
from the official Maven Wrapper plugin — it will overwrite what's here
with something Apache's own tooling generated and verified, rather than
something I typed from memory.

## Honesty note on verification

Maven Central is not reachable from this build environment (network egress
returns `host_not_allowed`), so **this could not be compiled with `mvn
compile` and real dependencies**, unlike the database migrations, which
were fully run and tested against a live Postgres instance.

What *was* verified instead, across all 67 Java files:
- Every internal `com.caa.platform.*` import resolves to a file that
  actually exists
- Every file has balanced braces
- Every file's public type name matches its filename (a real javac
  requirement)

Run `mvn compile` yourself once you have normal internet access to get a
real compiler check — I'd expect it to build clean based on the manual
review, but that's not the same guarantee as an actual compile, so please
verify before relying on it.

## Package structure

| Package | Covers | Doc section |
|---|---|---|
| `staff` | StaffUser, roles (incl. Compliance Administrator) | 3, 3b |
| `client` | Client, name derivation, Inquiry + conversion | 3, 3c |
| `student` | Student | 3 |
| `session` | Session, SessionDay, authorized clients, staggered blocks, comments, publish gate | 3, 3a, 4c, 4d |
| `enrollment` | Enrollment, Payment, close-out logic | 3, 4f, 4g, 7 |
| `certification` | CertificationRun, Observation, Certification, Method 9 scoring, split-run eligibility | 3, 3b |
| `equipment` | Trailers, testing systems, 5-Filter, Chart Recorder | 4h |
| `vr` | VR token blocks, pricing, outreach log | 3a, 4b |

## Business rules implemented as actual code, not just entities

- **`Method9ScoringService`** — the two-condition pass/fail rule (37
  cumulative cap per color, independently; 20% failed-reading threshold;
  no 15% scoring rule)
- **`SplitRunEligibilityService`** — White-only split-run logic (Black
  failing always requires a full new 50-point run), plus the CA/TX/VR
  hard blocks
- **`SessionAuthorizationService`** — enforces Private-locked-to-host vs.
  Semi-Private-unlocks-self-service (a business rule, deliberately not a
  DB constraint — see `db/README.md`)
- **`SessionPublishGateService`** — the Publish toggle's completeness gate
- **`SessionCloseOutService`** — the two independent close-out triggers
  (automatic per-student cert email vs. manual gated summary email)
- **`ClientNameDerivationService`** — the suffix-stripping Record
  Name/Portal Display Name logic
- **`VRPricingService`** — per-transaction tiered pricing with client
  override precedence

## What's still a placeholder

- `SplitRunEligibilityService`'s Alt-152-a VR restriction is a simple
  boolean flag here — wire it to the state compliance rules engine
  (Section 4's design note) and the Compliance Administrator role once
  that's built out.
- QuickBooks/Brevo/Stacktest.net API clients aren't built yet — this
  scaffold covers the domain model and business logic that those
  integrations will call into.
- No controllers/REST endpoints yet — this is the domain + persistence
  layer. The internal API contract (PHP <-> Java) is the next scaffolding
  step per the build order agreed earlier.

## Running against the database

Point `application.yml`'s datasource at a Postgres instance that has
already had `db/migrations/*.sql` applied, in order. `ddl-auto: validate`
means Hibernate will fail fast on startup if the entities and the actual
schema disagree — which is deliberate, since the SQL migrations are the
source of truth, not Hibernate auto-generation.
