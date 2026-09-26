@extends('layouts.admin')

@section('title', 'QuickBooks Connection - Admin')

@push('styles')
<style>
    .qbo-card { max-width: 560px; border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 1.5rem; margin-top: 1rem; }
    .qbo-row { margin-bottom: 0.75rem; font-size: 0.9rem; }
    .qbo-row strong { display: inline-block; width: 160px; }
    .qbo-badge { display: inline-block; padding: 0.2rem 0.7rem; border-radius: 1rem; font-size: 0.8rem; font-weight: 600; }
    .qbo-connected { background: #e6f4ea; color: #16803C; }
    .qbo-disconnected { background: #f3f4f6; color: #666666; }
    .qbo-expired { background: #fdecea; color: #b82027; }
</style>
@endpush

@section('content')
    <h1>QuickBooks Connection</h1>

    @if (session('status'))
        <p style="color:#005da0; font-weight:600;">{{ session('status') }}</p>
    @endif

    {{--
        Michael, 2026-08-25 -- one company-wide connection, not per
        client or per staff member -- confirmed via QboConnection's
        own real, DB-level partial unique index (migration 032), not
        just an assumption made here. Compliance-Administrator-only,
        same gate Staff Management already uses.
    --}}
    <div class="qbo-card">
        @if ($connection['connected'] ?? false)
            <div class="qbo-row">
                <strong>Status</strong>
                @if ($connection['accessTokenExpired'] ?? false)
                    <span class="qbo-badge qbo-expired">Access token expired</span>
                @else
                    <span class="qbo-badge qbo-connected">Connected</span>
                @endif
            </div>
            <div class="qbo-row"><strong>Environment</strong> {{ $connection['environment'] ?? '' }}</div>
            <div class="qbo-row"><strong>Company (Realm ID)</strong> {{ $connection['realmId'] ?? '' }}</div>
            <div class="qbo-row">
                <strong>Connected by</strong>
                {{ $connection['connectedByStaffName'] ?? 'Unknown' }}
                @if (!empty($connection['connectedAt']))
                    on {{ \Illuminate\Support\Carbon::parse($connection['connectedAt'])->format('m/d/Y g:i A') }}
                @endif
            </div>

            <form method="POST" action="{{ route('admin.qbo.disconnect') }}" style="margin-top:1.25rem;"
                  onsubmit="return confirm('Disconnect from QuickBooks? Any features relying on this connection will stop working until reconnected.');">
                @csrf
                <button type="submit" class="btn-secondary">Disconnect</button>
            </form>
        @else
            <p class="hint" style="margin-top:0;">Not currently connected to QuickBooks.</p>
            <a href="{{ route('admin.qbo.connect') }}" class="btn-primary" style="display:inline-block; padding:0.6rem 1.2rem; text-decoration:none;">Connect to QuickBooks</a>
        @endif
    </div>
@endsection
