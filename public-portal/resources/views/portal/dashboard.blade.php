@extends('layouts.app')

@section('title', 'Client Portal - Compliance Assurance Associates, Inc.')

@section('content')
    <h1>Welcome{{ isset($client['portalDisplayName']) ? ', ' . $client['portalDisplayName'] : '' }}</h1>

    {{-- Section 4e: Client Page fields match what staff see on the Roster
         (Section 4f) -- Student #, Name, Phone, Email, Lecture Completion,
         Field Certification -- plus audit-ready certificate downloads. --}}
    <section>
        <h2>Your Employees</h2>
        <table style="width:100%; border-collapse: collapse;">
            <thead>
                <tr style="text-align:left; border-bottom: 2px solid #e5e5e5;">
                    <th style="padding:0.5rem;">Student #</th>
                    <th style="padding:0.5rem;">Name</th>
                    <th style="padding:0.5rem;">Phone</th>
                    <th style="padding:0.5rem;">Email</th>
                    <th style="padding:0.5rem;">Lecture Completion</th>
                    <th style="padding:0.5rem;">Field Certification</th>
                    <th style="padding:0.5rem;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($employees as $employee)
                    <tr style="border-bottom: 1px solid #f0f0f0;">
                        <td style="padding:0.5rem;">{{ $employee['studentNumber'] ?? '' }}</td>
                        <td style="padding:0.5rem;">{{ $employee['name'] ?? '' }}</td>
                        <td style="padding:0.5rem;">{{ $employee['phone'] ?? '' }}</td>
                        <td style="padding:0.5rem;">{{ $employee['email'] ?? '' }}</td>
                        <td style="padding:0.5rem;">
                            {{ ($employee['lectureComplete'] ?? false) ? ($employee['lectureCompletionDate'] ?? 'Complete') : 'Not complete' }}
                        </td>
                        <td style="padding:0.5rem;">&mdash;</td>
                        <td style="padding:0.5rem;"><a href="#">Download</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="padding:0.5rem; color:#999;">No employees on file yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    {{-- Section 4: host clients can self-service authorize outside
         organizations on a Semi-Private session -- Private sessions reject
         this with a 409, surfaced as a friendly validation error. --}}
    <section>
        <h2>Authorize an Organization for a Semi-Private Session</h2>
        <form method="POST" action="{{ route('portal.authorized-clients.store') }}">
            @csrf
            <label for="session_id_for_auth">Session ID</label>
            <input type="text" id="session_id_for_auth" name="session_id" required>
            <label for="client_id">Client # to authorize</label>
            <input type="text" id="client_id" name="client_id" required>
            <button type="submit">Authorize</button>
        </form>
        @error('client_id')
            <p style="color:#c62828;">{{ $message }}</p>
        @enderror
    </section>
@endsection
