@extends('layouts.app')

@section('title', 'Enrollment Results - Compliance Assurance Associates, Inc.')

@section('content')
{{--
    Michael, 2026-08-31/09-01 -- QBO Per-Student Invoicing, Stage 3B.
    Real, computed pricing (EnrollmentPricingService, via a new,
    read-only preview endpoint) and the three real invoice actions --
    confirmed with Michael as only 3 of the original 4 DIBs buttons
    apply here: "Just Summary-Email, No QBO Invoice" is Private-session
    only, and this whole flow is already hard-scoped to Public sessions
    (EnrollmentPricingService itself rejects anything else).
--}}
<div class="admin-content">
    <h1>Enrollment Results</h1>

    @php
        $succeeded = collect($results)->where('success', true);
        $failed = collect($results)->where('success', false);
        $pricingByEnrollmentId = collect($pricing['lines'] ?? [])->keyBy('enrollmentId');
        $total = $pricingByEnrollmentId->sum('price');
        $anyLate = $pricing['anyLate'] ?? false;
        $lateFeeAmount = $pricing['lateFeeAmount'] ?? null;
        if ($anyLate && $lateFeeAmount !== null) {
            $total += $lateFeeAmount;
        }
    @endphp

    <p style="color:#16803c; font-weight:600;">
        {{ $succeeded->count() }} student(s) newly enrolled.
        @if ($failed->count())
            {{ $failed->count() }} could not be enrolled -- see below.
        @endif
    </p>

    @if (session('status'))
        <p class="hint" style="color:#b82027; font-weight:600;">{{ session('status') }}</p>
    @endif

    @if ($schoolType === 'PUBLIC' && $succeeded->count())
        <div class="admin-card">
            <h2>Charges</h2>
            <table>
                <thead>
                    <tr><th>Student</th><th>Components</th><th style="text-align:right;">Cost</th></tr>
                </thead>
                <tbody>
                    @foreach ($succeeded as $r)
                        @php $line = $pricingByEnrollmentId->get($r['enrollment']['id'] ?? null); @endphp
                        <tr>
                            <td>Student #{{ $r['studentId'] }}</td>
                            <td>{{ $r['components'] }}</td>
                            <td style="text-align:right;">${{ $line ? number_format($line['price'], 2) : '—' }}</td>
                        </tr>
                    @endforeach
                    @if ($anyLate && $lateFeeAmount !== null)
                        <tr>
                            <td colspan="2">Late Enrollment Fee</td>
                            <td style="text-align:right;">${{ number_format($lateFeeAmount, 2) }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
            <p style="margin-top:0.75rem; font-weight:600;">Total Registration Costs: ${{ number_format($total, 2) }}</p>
        </div>

        <div class="admin-card">
            <h2>Invoice</h2>
            <p class="hint">Nothing is charged in QuickBooks until one of these is chosen.</p>

            <form method="POST" action="{{ route('admin.enroll.bulk.invoice-just-now', ['session' => $sessionId]) }}" style="display:inline-block; margin-right:0.75rem;">
                @csrf
                <button type="submit" class="btn-primary">Generate QBO Invoice for Just-Now Registered (total = ${{ number_format($total, 2) }})</button>
            </form>

            <form method="POST" action="{{ route('admin.enroll.bulk.invoice-all-un-invoiced', ['session' => $sessionId]) }}" style="display:inline-block; margin-right:0.75rem;">
                @csrf
                <input type="hidden" name="client_id" value="{{ $clientId }}">
                <button type="submit" class="btn-secondary">Generate QBO Invoice for ALL Un-Invoiced Registrations</button>
            </form>

            <a href="{{ route('admin.enroll.bulk', ['session' => $sessionId]) }}" class="btn-secondary" style="display:inline-block; text-decoration:none; padding:0.6rem 1.2rem;">Don't invoice -- register more students</a>
        </div>
    @endif

    {{--
        Michael, 2026-09-03 -- Bulk Enroll, Private/Semi-Private
        support. Confirmed with Michael directly: no per-student price
        breakdown here at all -- Private billing is flat, session-
        level (EnrollmentPricingService itself explicitly rejects
        anything that isn't PUBLIC). Instead: real current headcount,
        this batch's own field count, the resulting total, and a
        clear, visible warning if that total would exceed the
        session's own negotiated included headcount -- giving staff a
        real chance to flag the overage to the client before
        proceeding, not after the fact.

        Michael, 2026-09-04 -- billing moved entirely to session close-
        out (confirmed with Michael: no invoice exists at all until
        then -- overage is simply counted as enrollments happen, and
        one, final invoice at close-out reflects the true, final
        total). The "Generate QBO Invoice" button that used to sit here
        is removed entirely, not just hidden -- there's no real,
        legitimate reason to invoice from this screen anymore. This
        headcount/overage readout stays exactly as it was, still
        genuinely useful for flagging a real overage to the client
        before it happens, well ahead of the eventual invoice
        reflecting it.

        Michael, 2026-09-04, later same day -- correction: billing is
        NOT automatic at close-out at all -- confirmed with Michael: a
        real, separate, two-step "Generate Invoice" / "Send Invoice"
        action on the Session Details page itself (Bid & Invoice
        section), with closedOut staying a plain, manual toggle Chasity
        sets herself once payment is actually confirmed. Fixed the
        hint text below to match -- it previously, incorrectly said
        this happens automatically.
    --}}
    @if (in_array($schoolType, ['PRIVATE', 'SEMI_PRIVATE'], true) && $succeeded->count())
        <div class="admin-card">
            <h2>Field Headcount</h2>
            <table>
                <tbody>
                    <tr><td>Already enrolled (field)</td><td style="text-align:right;">{{ $headcount['currentFieldCount'] ?? 0 }}</td></tr>
                    <tr><td>Just enrolled, this batch (field)</td><td style="text-align:right;">{{ $headcount['newFieldCount'] ?? 0 }}</td></tr>
                    <tr style="font-weight:600;"><td>Resulting total</td><td style="text-align:right;">{{ $headcount['resultingTotal'] ?? 0 }}</td></tr>
                    <tr><td>Included in this session's own quoted price</td>
                        <td style="text-align:right;">{{ $headcount['includedFieldHeadcount'] ?? 'Not set' }}</td></tr>
                </tbody>
            </table>

            @if ($headcount['overCapacity'] ?? false)
                @php
                    $overCount = ($headcount['resultingTotal'] ?? 0) - ($headcount['includedFieldHeadcount'] ?? 0);
                    $overageRate = $headcount['overageRate'] ?? null;
                @endphp
                <p style="color:#b82027; font-weight:700; margin-top:0.75rem;">
                    This puts the session {{ $overCount }} student(s) over its included headcount
                    @if ($overageRate !== null)
                        -- a real overage charge of ${{ number_format($overCount * $overageRate, 2) }} (${{ number_format($overageRate, 2) }}/student) will apply, reflected in the invoice generated at session close-out.
                    @else
                        -- but this session has no overage rate set, so that charge can't be calculated yet.
                    @endif
                    Consider letting the client know now, ahead of the eventual invoice.
                </p>
            @endif

            <p class="hint" style="margin-top:0.75rem;">No invoice is generated from here -- once this session is closed out, generate and send the invoice from the Session Details page (Bid & Invoice section).</p>
            <a href="{{ route('admin.enroll.bulk', ['session' => $sessionId]) }}" class="btn-secondary" style="display:inline-block; text-decoration:none; padding:0.6rem 1.2rem;">Register more students</a>
        </div>
    @endif

    @if ($failed->count())
        <div class="admin-card">
            <h2>Not Enrolled</h2>
            @foreach ($failed as $r)
                <div class="admin-form-errors" style="margin-bottom:0.75rem;">
                    <p>Student #{{ $r['studentId'] }} ({{ $r['components'] }}): {{ $r['error'] }}</p>
                </div>
            @endforeach
        </div>
    @endif

    <p style="margin-top:1.5rem;">
        <a href="{{ route('admin.sessions.roster', ['session' => $sessionId]) }}">Back to Roster</a>
    </p>
</div>
@endsection
