@extends('layouts.admin')

@section('title', 'Invoice Checker - Admin')

@section('content')
{{--
    Michael, 2026-09-01 -- Client Auto-Notify feature. Confirmed with
    Michael as scoped to only our own known invoices/enrollments,
    nothing broader.

    Michael, 2026-09-03 -- two genuinely separate sections now, not one
    merged table -- confirmed with Michael: "QBO Invoice" as a concept
    doesn't apply to Private/Semi-Private rows at all (not billed at
    enrollment time), so mixing them into the Public table would just
    show a confusing, blank invoice column for half the rows. Also,
    both lists are filtered to the last 7 days once a notification
    genuinely succeeds -- confirmed with Michael as filtering the view
    only, never deleting real, historical data; a row still genuinely
    stuck (never successfully sent) stays visible indefinitely, no
    matter how old, until it's actually resolved.
--}}
<h1>Invoice Checker</h1>
<p class="hint">Every recent enrollment we've billed or enrolled, and whether the student's Brevo notification actually went out. Rows more than 7 days past a successful send drop off this list -- the underlying record itself is never deleted.</p>

@if ($justRefreshed ?? false)
    <p style="color:#16803c; font-weight:600;">Refreshed just now.</p>
@endif

<h2>Public (billed via QBO)</h2>
<form method="POST" action="{{ route('admin.invoice-checker.refresh') }}" style="margin-bottom:1.25rem;">
    @csrf
    <button type="submit" class="btn-primary">Refresh</button>
</form>

<div class="admin-card" style="margin-bottom:2rem;">
    <table>
        <thead>
            <tr>
                <th>QBO Invoice</th>
                <th>Student</th>
                <th>Client</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Brevo Notification</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($invoices as $row)
                <tr>
                    <td>{{ $row['qbInvoiceId'] ?? '' }}</td>
                    <td>{{ $row['studentName'] ?? '' }}</td>
                    <td>{{ $row['clientName'] ?? '' }}</td>
                    <td>${{ number_format($row['amount'] ?? 0, 2) }}</td>
                    <td>
                        @if (($row['status'] ?? null) === 'PAID')
                            <span class="status-badge status-active">Paid</span>
                        @else
                            <span class="status-badge status-inactive">{{ $row['status'] ?? '' }}</span>
                        @endif
                    </td>
                    <td>
                        @if (!empty($row['brevoNotifiedAt']))
                            <span class="status-badge status-active">Sent {{ \Illuminate\Support\Carbon::parse($row['brevoNotifiedAt'])->format('m/d/Y g:i A') }}</span>
                        @else
                            @if (!empty($row['brevoNotificationError']))
                                <span class="status-badge status-alert" title="{{ $row['brevoNotificationError'] }}">Failed</span>
                            @else
                                <span class="hint">Not yet</span>
                            @endif
                            @if (($row['status'] ?? null) === 'PAID')
                                {{--
                                    Michael, 2026-09-02 -- real, manual
                                    retry for a Payment stuck here --
                                    e.g. Invoice 146's own test rows,
                                    marked PAID before a real Brevo API
                                    key existed. Safe to click more than
                                    once, and from any row on the same
                                    invoice -- notifyStudentsForInvoice()
                                    is per-invoice, not per-row, and its
                                    own isNotifiable() check skips
                                    anything already genuinely notified.
                                --}}
                                <form method="POST" action="{{ route('admin.invoice-checker.retry-notification') }}" style="display:inline-block; margin-left:0.5rem;">
                                    @csrf
                                    <input type="hidden" name="qb_invoice_id" value="{{ $row['qbInvoiceId'] ?? '' }}">
                                    <button type="submit" class="btn-secondary" style="font-size:0.8rem; padding:0.25rem 0.6rem;">Retry</button>
                                </form>
                            @endif
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6">No recent invoiced enrollments.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<h2>Private / Semi-Private (notified at enrollment)</h2>
<p class="hint">Not billed at enrollment time -- these fire immediately when the student is enrolled, not on payment.</p>

<div class="admin-card">
    <table>
        <thead>
            <tr>
                <th>Student</th>
                <th>Client</th>
                <th>School Type</th>
                <th>Component</th>
                <th>Brevo Notification</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($privateNotifications as $row)
                <tr>
                    <td>{{ $row['studentName'] ?? '' }}</td>
                    <td>{{ $row['clientName'] ?? '' }}</td>
                    <td>{{ $row['schoolType'] ?? '' }}</td>
                    <td>{{ $row['component'] ?? '' }}</td>
                    <td>
                        @if (!empty($row['brevoNotifiedAt']))
                            <span class="status-badge status-active">Sent {{ \Illuminate\Support\Carbon::parse($row['brevoNotifiedAt'])->format('m/d/Y g:i A') }}</span>
                        @else
                            @if (!empty($row['brevoNotificationError']))
                                <span class="status-badge status-alert" title="{{ $row['brevoNotificationError'] }}">Failed</span>
                            @else
                                <span class="hint">Not yet</span>
                            @endif
                            {{--
                                Michael, 2026-09-03 -- real, manual retry
                                mirroring the Public table's own --
                                notifyStudentForEnrollment()'s own guard
                                (added alongside this) skips anything
                                already genuinely notified, so this is
                                safe to click more than once too.
                            --}}
                            <form method="POST" action="{{ route('admin.invoice-checker.retry-private-notification') }}" style="display:inline-block; margin-left:0.5rem;">
                                @csrf
                                <input type="hidden" name="enrollment_id" value="{{ $row['enrollmentId'] ?? '' }}">
                                <button type="submit" class="btn-secondary" style="font-size:0.8rem; padding:0.25rem 0.6rem;">Retry</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">No recent Private/Semi-Private enrollments.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
