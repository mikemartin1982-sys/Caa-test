{{-- Mailbox inbox: status tabs + list. Built on the real CAA brand
     stylesheet (caa-brand.css) -- .admin-card, .status-badge, table
     styles -- not Tailwind. Adjust @extends to your admin layout name. --}}
@extends('layouts.admin')

@section('content')
@if(auth('staff')->user()->isComplianceAdministrator())
<p><a href="{{ route('admin.mailbox.connection') }}">Microsoft mailbox connection</a></p>
@endif
@if ($errors->any())
<div role="alert">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
@endif
<div class="admin-content">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.25rem;">
        <h1 style="font-size:1.5rem; color:#1a1a1a; margin:0;">Client Mailbox</h1>
        <div><a href="{{ config('mailbox.template_library_url') }}" target="_blank" rel="noopener">Cloudflare template library</a>
        @if(auth('staff')->user()->isComplianceAdministrator())
        &middot; <a href="{{ route('admin.mailbox.templates.index') }}">Manage saved replies</a>
        @endif</div>
    </div>

    <div class="mailbox-tabs">
        @foreach (['new' => 'New', 'replied' => 'Replied', 'closed' => 'Closed'] as $key => $label)
            <a href="{{ route('admin.mailbox.index', ['status' => $key]) }}"
               class="mailbox-tab @if ($status === $key) is-current @endif">
                {{ $label }}
                <span class="mailbox-tab-count">{{ $counts[$key] }}</span>
            </a>
        @endforeach
    </div>

    <div class="admin-card" style="padding:0;">
        <table>
            <thead>
                <tr>
                    <th style="padding-left:1.25rem;">From</th>
                    <th>Subject</th>
                    <th>Received</th>
                    <th style="padding-right:1.25rem;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($messages as $message)
                    <tr>
                        <td style="padding-left:1.25rem; font-weight:600; color:#1a1a1a;">
                            {{ $message->from_name ?: $message->from_email }}
                        </td>
                        <td><a href="{{ route('admin.mailbox.show', $message) }}">{{ $message->subject ?: '(no subject)' }}</a></td>
                        <td style="white-space:nowrap; color:#888888; font-size:0.85rem;">
                            {{ $message->received_at->diffForHumans() }}
                        </td>
                        <td style="padding-right:1.25rem;">
                            @php
                                $badgeClass = match ($message->status) {
                                    'new' => 'status-role-staff',
                                    'replied' => 'status-active',
                                    'closed' => 'status-inactive',
                                };
                            @endphp
                            <span class="status-badge {{ $badgeClass }}">{{ ucfirst($message->status) }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align:center; color:#888888; padding:2.5rem 1rem;">
                            No messages here.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:1rem;">{{ $messages->links() }}</div>
</div>

<style>
    /* Page-specific, built on the shared brand tokens (#005da0 / #444444 /
       #e5e7eb) -- same convention the rest of the admin UI already uses for
       anything not covered by a shared component class. */
    .mailbox-tabs {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 1rem;
        border-bottom: 1px solid #e5e7eb;
    }
    .mailbox-tab {
        padding: 0.6rem 0.25rem;
        margin-right: 1rem;
        margin-bottom: -1px;
        font-size: 0.9rem;
        font-weight: 600;
        color: #444444;
        text-decoration: none;
        border-bottom: 2px solid transparent;
    }
    .mailbox-tab:hover {
        color: #005da0;
    }
    .mailbox-tab.is-current {
        color: #005da0;
        border-bottom-color: #005da0;
    }
    .mailbox-tab-count {
        display: inline-block;
        margin-left: 0.35rem;
        padding: 0.05rem 0.55rem;
        font-size: 0.75rem;
        font-weight: 600;
        border-radius: 1rem;
        background-color: #f3f4f6;
        color: #666666;
    }
    .mailbox-tab.is-current .mailbox-tab-count {
        background-color: #eef4fa;
        color: #005da0;
    }
    .mailbox-row {
        cursor: pointer;
    }
</style>
@endsection
