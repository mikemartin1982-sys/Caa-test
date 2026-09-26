@extends('layouts.admin')
 
@section('title', ($student['name'] ?? 'Student') . ' - ' . ($clientRecord['recordName'] ?? $clientRecord['company'] ?? 'Client') . ' - Admin')
 
@section('content')
    <h1>{{ $student['name'] ?? '' }}</h1>
    <p>
        <a href="{{ route('admin.clients.employees', ['client' => $clientRecord['id']]) }}">&laquo; Back to Employees</a>
    </p>
 
    <div class="admin-card">
        <div class="info-row"><strong>Student #</strong> {{ $student['studentNumber'] ?? '' }}</div>
        <div class="info-row"><strong>Email</strong> {{ $student['email'] ?? '' }}</div>
        <div class="info-row"><strong>Phone</strong> {{ $student['phone'] ?? '—' }}</div>
        <div class="info-row"><strong>Status</strong> {{ ($student['active'] ?? false) ? 'Active' : 'Inactive' }}</div>
    </div>

    {{--
        Michael, 2026-09-03 -- lecture billing exemption, student-level
        scope. A real, one-off exemption for this specific person (e.g.
        logistics resolving a technical issue) -- separate from the
        client-wide exemption on the Edit Client page, which applies to
        every one of this client's employees instead.
    --}}
    <div class="admin-card">
        <h2>Lecture Fee</h2>
        <form method="POST" action="{{ route('admin.clients.students.update', ['client' => $clientRecord['id'], 'student' => $student['id']]) }}">
            @csrf
            @method('PATCH')
            <label><input type="checkbox" name="lecture_fee_exempt" value="1" @checked($student['lectureFeeExempt'] ?? false)> Exempt this student from the lecture fee</label>
            <div class="hint">A one-off exemption for this specific person -- for a client-wide exemption instead (e.g. a government agency management has decided to offer the lecture to at no charge), use the exemption on the client's own Edit page.</div>
            <div style="margin-top:0.75rem;"><button type="submit" class="btn-primary" style="padding:0.4rem 0.9rem; font-size:0.85rem;">Save</button></div>
        </form>
    </div>

    @include('admin.students.partials.lecture-certificate')
 
    <div class="admin-card">
        <h2>Certification History</h2>
        {{--
            Michael, 2026-08-25 -- Section 4a extension, piece 3A. Only
            actual CERTIFIED outcomes appear here -- an enrollment that
            never certified isn't part of this history, confirmed with
            Michael, even though it's still a real, separate enrollment
            record elsewhere (Current Enrollments, the roster, etc.).
        --}}
        @if (empty($certificationHistory))
            <p class="hint">No certifications on file yet.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Location</th>
                        <th>Type</th>
                        <th>Component</th>
                        <th>Run #</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($certificationHistory as $cert)
                        <tr>
                            <td>
                                <a href="{{ route('admin.sessions.roster', ['session' => $cert['sessionId']]) }}">
                                    {{ $cert['sessionLocationName'] ?? ('Session #' . $cert['sessionId']) }}
                                </a>
                            </td>
                            <td>{{ $cert['schoolType'] ?? '' }}</td>
                            <td>
                                @if (($cert['enrollmentComponents'] ?? null) === 'LECTURE_ONLY')
                                    Lecture
                                @elseif (($cert['enrollmentComponents'] ?? null) === 'FIELD_ONLY')
                                    Field
                                @endif
                            </td>
                            <td>{{ $cert['runNumber'] ?? '—' }}</td>
                            <td>
                                @if (!empty($cert['performedAt']))
                                    {{ \Illuminate\Support\Carbon::parse($cert['performedAt'])->format('m/d/Y') }}
                                @else
                                    <span class="hint">&mdash;</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection