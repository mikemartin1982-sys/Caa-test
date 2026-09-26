@extends('layouts.admin')

@section('title', 'Session Roster - Admin')

@section('content')
    <h1>Roster &mdash; Session #{{ $session['id'] ?? '' }}</h1>
    <p><a href="{{ route('admin.sessions.show', ['session' => $session['id']]) }}">&laquo; Back to Session Details</a></p>

    {{--
        Michael, 2026-09-01 -- found live: this page never actually
        displayed either flash key at all -- a redirect here (e.g.
        after generating a QBO invoice) set the message, but nothing
        on arrival ever showed it. success/status are deliberately
        separate keys, not one -- a real error (e.g. "could not
        generate invoice") uses status and should never render in the
        same green styling as a genuine success.
    --}}
    @if (session('success'))
        <p style="color:#16803c; font-weight:600;">{{ session('success') }}</p>
    @endif
    @if (session('status'))
        <p style="color:#b82027; font-weight:600;">{{ session('status') }}</p>
    @endif
    {{--
        Michael, 2026-08-31/09-01 -- QBO Per-Student Invoicing, Stage
        3A. This link now points at the new bulk-enroll flow --
        confirmed with Michael as replacing this link specifically,
        while the Dashboard's own "Manual Enroll" link keeps pointing
        at the original, single-student flow, unchanged.
    --}}
    <p><a href="{{ route('admin.enroll.bulk', ['session' => $session['id']]) }}" class="btn-primary" style="display:inline-block; padding:0.5rem 1rem; text-decoration:none;">+ Enroll Student</a></p>

    {{--
        Michael, 2026-08-29 -- staff calendar Phase 5. Confirmed with
        Michael: Office-Use and Field-Use are NOT two separate pages --
        matching DIBs' own real, verified behavior (identical underlying
        row markup in both, confirmed byte-for-byte on a real student
        row) -- just this one roster with certain columns and actions
        shown or hidden. Field-Use is for the Operator/Field Manager
        on-site (name, contact, status, lecture -- the essentials, no
        billing or account-management actions); Office-Use adds
        Company/Enrolled/Payment and the Unenroll action. A plain query
        param (?office=1), not a database preference or session state --
        matching DIBs' own approach, and simple enough that a link is
        all that's needed to switch.
    --}}
    @php $officeUse = request()->boolean('office', true); @endphp
    <p>
        @if ($officeUse)
            <a href="{{ route('admin.sessions.roster', ['session' => $session['id'], 'office' => 0]) }}">Switch to Field-Use</a>
        @else
            <a href="{{ route('admin.sessions.roster', ['session' => $session['id'], 'office' => 1]) }}">Switch to Office-Use</a>
        @endif
    </p>

    {{-- Section 4g: only active once every roster entry has a terminal status. --}}
    <form method="POST" action="{{ route('admin.sessions.send-summary-email', ['session' => $session['id']]) }}">
        @csrf
        <button type="submit" class="btn-primary" @disabled(!$summaryEmailReady)>Send Summary Email</button>
        @unless ($summaryEmailReady)
            <span class="text-note"> &mdash; not every student has a final status yet</span>
        @endunless
    </form>

    {{--
        Michael, 2026-08-31 -- appearance cleanup: this table was built
        entirely with scattered inline style="padding:0.5rem;" on every
        single cell, plus its own hardcoded border colors -- the only
        admin page still doing this, rather than getting the real,
        established brand table style (caa-brand.css) that every plain
        <table> already receives automatically. Stripped entirely so
        this page finally looks consistent with the rest of admin.
    --}}
    <table>
        <thead>
            <tr>
                {{-- Michael, 2026-08-23, found live while adding the Enroll
                     link above: 'VR' was checked here as a schoolType value,
                     but VR was deliberately removed as its own schoolType
                     earlier tonight -- it's the separate vrSession boolean
                     modifier instead (can apply to PUBLIC or PRIVATE). This
                     check could never match 'VR', so these columns were
                     silently never shown for VR sessions, even though
                     RosterService already correctly computes this data for
                     them (its own showBillingColumns check already uses
                     schoolType == PUBLIC || isVrSession() correctly). --}}
                @if ($officeUse && (($session['schoolType'] ?? null) === 'PUBLIC' || ($session['vrSession'] ?? false)))
                    <th>Company</th>
                    <th>Enrolled</th>
                    <th>Payment</th>
                @endif
                <th>Student</th>
                <th>Email</th>
                <th>Phone</th>
                {{-- Michael, 2026-08-25 -- found live: a "Both"
                     enrollment is genuinely two separate records (one
                     Lecture, one Field), so without this column the
                     same student's name shows twice with nothing
                     telling staff which row is which. --}}
                <th>Enrolled For</th>
                <th>Status</th>
                <th>Lecture</th>
                <th>Lecture Complete</th>
                <th>Cert. Run</th>
                <th>Practice Run</th>
                @if ($officeUse)
                    <th>Actions</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach ($roster as $entry)
                <tr>
                    @if ($officeUse && (($session['schoolType'] ?? null) === 'PUBLIC' || ($session['vrSession'] ?? false)))
                        <td>
                            @if (!empty($entry['companyName'] ?? null))
                                <a href="{{ route('admin.clients.edit', ['client' => $entry['clientId']]) }}">{{ $entry['companyName'] }}</a>
                            @endif
                        </td>
                        <td>{{ $entry['enrollmentDate'] ?? '' }}</td>
                        <td>{{ $entry['paymentStatus'] ?? '' }}</td>
                    @endif
                    <td>{{ $entry['studentName'] ?? '' }}</td>
                    <td>{{ $entry['studentEmail'] ?? '' }}</td>
                    <td>{{ $entry['studentPhone'] ?? '' }}</td>
                    <td>
                        @if (($entry['enrollmentComponents'] ?? null) === 'LECTURE_ONLY')
                            Lecture
                        @elseif (($entry['enrollmentComponents'] ?? null) === 'FIELD_ONLY')
                            Field
                        @endif
                    </td>
                    <td>
                        <form method="POST" action="{{ route('admin.roster.update-status', ['enrollment' => $entry['enrollmentId']]) }}">
                            @csrf
                            <input type="hidden" name="session_id" value="{{ $session['id'] }}">
                            <select name="roster_status" onchange="this.form.submit()">
                                @foreach (['ARR', 'CERTIFIED', 'DNC', 'DNA'] as $status)
                                    <option value="{{ $status }}" @selected(($entry['rosterStatus'] ?? null) === $status)>{{ $status }}</option>
                                @endforeach
                            </select>
                        </form>
                    </td>
                    <td>{{ ($entry['lectureComplete'] ?? false) ? 'Yes' : 'No' }}</td>
                    <td>{{ $entry['lectureComplete'] ?? false ? 'Complete' : '' }}</td>
                    <td>{{ $entry['certificationRunNumber'] ?? '' }}</td>
                    <td>{{ $entry['practiceRunNumber'] ?? '' }}</td>
                    @if ($officeUse)
                        <td>
                            {{-- Michael, 2026-08-23 -- "unenroll cleanly." Hidden
                                 entirely once CERTIFIED -- that has to remain a
                                 permanent record, matching the same block already
                                 enforced server-side (EnrollmentController), not
                                 just a UI-level suggestion. Also confirmed
                                 Office-Use-only (2026-08-29) -- Field-Use never
                                 shows account-management actions at all. --}}
                            @if (($entry['rosterStatus'] ?? null) !== 'CERTIFIED')
                                <form method="POST" action="{{ route('admin.roster.unenroll', ['enrollment' => $entry['enrollmentId']]) }}"
                                      onsubmit="return confirm('Unenroll {{ $entry['studentName'] ?? 'this student' }} from this session? This cannot be undone.');">
                                    @csrf @method('DELETE')
                                    <input type="hidden" name="session_id" value="{{ $session['id'] }}">
                                    <button type="submit" class="btn-secondary" style="padding:0.3rem 0.75rem; font-size:0.8rem;">Unenroll</button>
                                </form>
                            @endif
                        </td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
