@extends('layouts.admin')
@section('title', 'Staff Dashboard')
@section('content')
@if(auth('staff')->user()->isComplianceAdministrator())
<p><a href="{{ route('admin.mailbox.connection') }}">Microsoft mailbox connection</a></p>
@endif
<h1>Staff Dashboard</h1>
<div class="admin-card">
    <h2>Client Mailbox</h2>
    @if(config('mailbox.enabled'))
        <p><a href="{{ route('admin.mailbox.index') }}">Open Client Mailbox</a> &middot; {{ $newMailboxCount }} new messages</p>
    @else
        <p>Client Mailbox is awaiting Microsoft mailbox configuration.</p>
    @endif
    <p><a href="{{ config('mailbox.template_library_url') }}" target="_blank" rel="noopener">Open Cloudflare template library</a></p>
</div>
@foreach(['Today' => $todaySessions, 'Tomorrow' => $tomorrowSessions] as $label => $sessions)
<div class="admin-card">
    <h2>{{ $label }}’s sessions</h2>
    <ul>
    @forelse($sessions as $session)
        <li><a href="{{ route('admin.sessions.show', ['session' => $session['id']]) }}">Session #{{ $session['id'] }}</a> {{ $session['date'] ?? '' }}</li>
    @empty
        <li>No sessions scheduled.</li>
    @endforelse
    </ul>
</div>
@endforeach
@endsection
