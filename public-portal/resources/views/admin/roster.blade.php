@extends('layouts.app')

@section('title', 'Session Roster - Admin')

@section('content')
    <h1>Roster &mdash; Session #{{ $session['id'] ?? '' }}</h1>
    <p><a href="{{ route('admin.sessions.show', ['session' => $session['id']]) }}">&laquo; Back to Session Details</a></p>

    {{-- Section 4g: only active once every roster entry has a terminal status. --}}
    <form method="POST" action="{{ route('admin.sessions.send-summary-email', ['session' => $session['id']]) }}">
        @csrf
        <button type="submit" @disabled(!$summaryEmailReady)>Send Summary Email</button>
        @unless ($summaryEmailReady)
            <span style="color:#999;"> &mdash; not every student has a final status yet</span>
        @endunless
    </form>

    <table style="width:100%; border-collapse: collapse; margin-top:1rem;">
        <thead>
            <tr style="text-align:left; border-bottom: 2px solid #e5e5e5;">
                @if (in_array($session['schoolType'] ?? null, ['PUBLIC', 'VR']))
                    <th style="padding:0.5rem;">Company</th>
                    <th style="padding:0.5rem;">Enrolled</th>
                    <th style="padding:0.5rem;">Payment</th>
                @endif
                <th style="padding:0.5rem;">Student</th>
                <th style="padding:0.5rem;">Status</th>
                <th style="padding:0.5rem;">Lecture</th>
                <th style="padding:0.5rem;">Lecture Complete</th>
                <th style="padding:0.5rem;">Cert. Run</th>
                <th style="padding:0.5rem;">Practice Run</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($roster as $entry)
                <tr style="border-bottom: 1px solid #f0f0f0;">
                    @if (in_array($session['schoolType'] ?? null, ['PUBLIC', 'VR']))
                        <td style="padding:0.5rem;">
                            @if (!empty($entry['companyName'] ?? null))
                                <a href="#">{{ $entry['companyName'] }}</a>
                            @endif
                        </td>
                        <td style="padding:0.5rem;">{{ $entry['enrollmentDate'] ?? '' }}</td>
                        <td style="padding:0.5rem;">{{ $entry['paymentStatus'] ?? '' }}</td>
                    @endif
                    <td style="padding:0.5rem;">{{ $entry['studentName'] ?? '' }}</td>
                    <td style="padding:0.5rem;">
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
                    <td style="padding:0.5rem;">{{ ($entry['lectureComplete'] ?? false) ? 'Yes' : 'No' }}</td>
                    <td style="padding:0.5rem;">{{ $entry['lectureComplete'] ?? false ? 'Complete' : '' }}</td>
                    <td style="padding:0.5rem;">{{ $entry['certificationRunNumber'] ?? '' }}</td>
                    <td style="padding:0.5rem;">{{ $entry['practiceRunNumber'] ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
