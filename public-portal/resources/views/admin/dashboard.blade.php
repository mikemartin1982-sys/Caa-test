@extends('layouts.admin')

@section('title', 'Staff Dashboard - Compliance Assurance Associates, Inc.')

@section('content')
    <h1 style="margin-bottom: 1.5rem;">Welcome, {{ auth('staff')->user()->name }}</h1>

    <div class="admin-card">
        <h2>Client Mailbox</h2>
        @if(config('mailbox.enabled'))
            <p><a href="{{ route('admin.mailbox.index') }}">Open Client Mailbox</a> &middot; {{ $newMailboxCount ?? 0 }} new messages</p>
        @else
            <p>Client Mailbox is awaiting Microsoft mailbox configuration.</p>
        @endif
        <p><a href="{{ route('admin.mailbox.connection') }}">Microsoft mailbox connection</a></p>
        <p><a href="{{ config('mailbox.template_library_url') }}" target="_blank" rel="noopener">Open Cloudflare template library</a></p>
    </div>

    {{--
        Full icon menu, matching DIBs' real dashboard layout. Same
        "honest links only" convention as the sidebar: real destinations
        are live blue tiles, everything not built yet is a greyed,
        unclickable tile with the same "Not built yet" tooltip -- not
        silently omitted, so the eventual full menu is already laid out.
    --}}
    @php
        $dashboardIcons = [
            ['label' => 'Calendar', 'icon' => 'fa-calendar', 'route' => 'admin.calendar'],
            ['label' => 'Schools', 'icon' => 'fa-chart-simple', 'route' => 'admin.calendar'],
            ['label' => 'ManualEnroll', 'icon' => 'fa-pen-to-square', 'route' => 'admin.enroll.create'],
            ['label' => 'Stacktest', 'icon' => 'fa-right-to-bracket', 'external' => 'http://stacktest.net:5782/user/sign_in'],
            ['label' => 'Clients', 'icon' => 'fa-user', 'route' => 'admin.clients.index'],
            ['label' => 'Employees', 'icon' => 'fa-users', 'route' => 'admin.employees.index'],
            // Michael, 2026-08-31 -- "Instructors" tile removed: same
            // reasoning as the sidebar's own "Manage Instructors" --
            // confirmed as an old DIBs term for the same concept as
            // Staff Accounts, not a separate thing.
            // Michael, 2026-08-31 -- accuracy fix: this still pointed
            // at the old Stacktest external link, even though the
            // sidebar's own equivalent link was already updated last
            // night to the real, built Trucks & Trailers page
            // (admin.equipment.index) once that existed. This tile
            // just never got the same update.
            ['label' => 'Trailers', 'icon' => 'fa-truck', 'route' => 'admin.equipment.index'],
            // Michael, 2026-08-31 -- same real feature as the sidebar's
            // own "Certify Employee"/"Upload Certifications" (Lecture
            // Certificate Upload) -- search for the employee, then
            // upload from their profile.
            ['label' => 'Certs', 'icon' => 'fa-certificate', 'route' => 'admin.employees.index'],
            ['label' => 'Payments', 'icon' => 'fa-credit-card'],
            ['label' => 'Dig Test Admin', 'icon' => 'fa-mobile-screen', 'route' => 'admin.digital-testing.index'],
            ['label' => 'Staff Accounts', 'icon' => 'fa-id-badge', 'route' => 'admin.staff.index', 'adminOnly' => true],
        ];
    @endphp
    <div class="admin-icon-grid">
        @foreach ($dashboardIcons as $item)
            {{-- Staff Accounts specifically hidden from non-admins entirely
                 (Michael, 2026-08-22) -- not just gated on click, since a
                 tile that only redirects you away with an error isn't a
                 good experience when we can just not show it in the first
                 place. --}}
            @continue(($item['adminOnly'] ?? false) && !auth('staff')->user()->isComplianceAdministrator())
            @if (isset($item['route']) || isset($item['anchor']) || isset($item['external']))
                <a href="{{ isset($item['route']) ? route($item['route']) : ($item['anchor'] ?? $item['external']) }}"
                   class="admin-icon-tile" @if (isset($item['external'])) target="_blank" rel="noopener" @endif>
                    <div class="admin-icon-box"><i class="fa-solid {{ $item['icon'] }}"></i></div>
                    <div class="admin-icon-label">{{ $item['label'] }}</div>
                </a>
            @else
                <span class="admin-icon-tile admin-icon-disabled" title="Not built yet">
                    <div class="admin-icon-box"><i class="fa-solid {{ $item['icon'] }}"></i></div>
                    <div class="admin-icon-label">{{ $item['label'] }}</div>
                </span>
            @endif
        @endforeach
    </div>

    {{-- ===================== Today / Tomorrow ===================== --}}
    @foreach ([['label' => 'Today', 'items' => $todaySessions], ['label' => 'Tomorrow', 'items' => $tomorrowSessions]] as $group)
        <div class="sd-section" style="margin-bottom:1.5rem;">
            <div class="caa-box-header-blue">{{ $group['label'] }} ({{ count($group['items']) }})</div>
            <div style="padding: 0.75rem 1.25rem;">
                @if (empty($group['items']))
                    <p style="color:#888; margin:0.25rem 0;">No sessions.</p>
                @else
                    @foreach ($group['items'] as $s)
                        <div style="padding:0.5rem 0; border-bottom:1px solid #f3f4f6;">
                            <strong>{{ $s['id'] ?? '' }}: {{ $s['locationName'] ?? '' }}</strong>
                            <span style="color:#888;">({{ $s['schoolType'] ?? '' }})</span>
                            &mdash;
                            <a href="{{ route('admin.sessions.show', ['session' => $s['id']]) }}">Details</a>
                            &middot;
                            <a href="{{ route('admin.sessions.roster', ['session' => $s['id']]) }}">Roster</a>
                            @if (($s['mapLat'] ?? null) !== null && ($s['mapLng'] ?? null) !== null)
                                &middot;
                                <a href="https://www.google.com/maps/search/?api=1&query={{ $s['mapLat'] }},{{ $s['mapLng'] }}" target="_blank" rel="noopener">Map It</a>
                            @elseif (!empty($s['mapAddress']))
                                &middot;
                                <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($s['mapAddress']) }}" target="_blank" rel="noopener">Map It</a>
                            @endif
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    @endforeach
@endsection
