@extends('layouts.app')

@section('title', 'Session Details - Admin')

@section('content')
    <h1>Session Details &mdash; #{{ $session['id'] ?? '' }}</h1>
    <p><a href="{{ route('admin.sessions.roster', ['session' => $session['id']]) }}">View Roster &raquo;</a></p>

    {{-- Section 4c: School Type selector. Private/Semi-Private are a
         deliberate staff choice, not a derived state -- Private is
         structurally locked to the host, Semi-Private unlocks self-service. --}}
    <section>
        <h2>School Type</h2>
        <p><strong>{{ $session['schoolType'] ?? '' }}</strong></p>

        @if (in_array($session['schoolType'] ?? null, ['PRIVATE', 'SEMI_PRIVATE']))
            <h3>Authorized Clients</h3>
            <ul>
                @foreach ($authorizedClients as $ac)
                    <li>Client #{{ $ac['clientId'] ?? '' }} {{ ($ac['isHost'] ?? false) ? '(Host)' : '' }}</li>
                @endforeach
            </ul>
        @endif
    </section>

    {{-- Section 4c: Publish toggle -- only available once Location Name,
         Address, Pricing, and GPS Coordinates are complete. Sticky once on. --}}
    <section>
        <h2>Publish</h2>
        @if ($session['published'] ?? false)
            <p>&#9989; Published</p>
        @elseif ($readyToPublish)
            <form method="POST" action="{{ route('admin.sessions.publish', ['session' => $session['id']]) }}">
                @csrf
                <button type="submit">Publish Session</button>
            </form>
        @else
            <p style="color:#999;">Not ready to publish yet &mdash; complete Location Name, Address, Pricing, and GPS Coordinates.</p>
        @endif
    </section>

    {{-- Section 4c: Confirmed + Team Comments, write-once, no edit/delete anywhere. --}}
    <section>
        <h2>Confirmation &amp; Team Comments</h2>
        <ul style="list-style:none; padding:0;">
            @foreach ($comments as $comment)
                <li style="padding:0.5rem 0; border-bottom:1px solid #f0f0f0;">
                    <strong>{{ $comment['commentType'] ?? '' }}</strong>
                    &mdash; {{ $comment['authorUsername'] ?? '' }}, {{ $comment['createdAtCentral'] ?? '' }}
                    <br>{{ $comment['text'] ?? '' }}
                </li>
            @endforeach
        </ul>

        <form method="POST" action="{{ route('admin.sessions.comments.store', ['session' => $session['id']]) }}">
            @csrf
            <label for="comment_type">Type</label>
            <select id="comment_type" name="comment_type" required>
                <option value="TEAM_COMMENT">Team Comment</option>
                <option value="CONFIRMATION">Confirmation</option>
            </select><br>
            <label for="text">Comment</label>
            <textarea id="text" name="text" required></textarea><br>
            <button type="submit">Add Comment</button>
        </form>
    </section>

    {{-- Section 4c: "Copy Forward 6 Months," available on all session types. --}}
    <section>
        <h2>Recurring Session</h2>
        <form method="POST" action="{{ route('admin.sessions.copy-forward', ['session' => $session['id']]) }}">
            @csrf
            <button type="submit">Copy Forward 6 Months</button>
        </form>
    </section>
@endsection
