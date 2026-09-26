@extends('layouts.admin')

@section('title', 'Trucks & Trailers - Admin')

@push('styles')
<style>
    .equip-form { max-width: 400px; margin-top: 0.75rem; }
    .equip-form label { display: block; margin-top: 0.5rem; font-size: 0.85rem; }
    .equip-form input, .equip-form select { width: 100%; padding: 0.4rem; margin-top: 0.15rem; }
    .equip-section { margin-top: 2rem; }
</style>
@endpush

@section('content')
    <h1>Trucks &amp; Trailers</h1>

    <div class="equip-section">
        <h2>Trailers</h2>
        {{--
            Michael, 2026-08-31 -- shortCode isn't displayed or
            settable here -- the real, existing backend has no
            create/update endpoint that sets it at all (confirmed
            against TrailerController.CreateTrailerRequest directly),
            so showing an always-empty column would be misleading.
        --}}
        <table>
            <thead>
                <tr><th>Identifier</th><th>Equipment Set</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($trailers as $trailer)
                    <tr>
                        <td>{{ $trailer['identifier'] ?? '' }}</td>
                        <td>{{ $trailer['equipmentSetDescription'] ?? '' }}</td>
                        <td><a href="{{ route('admin.equipment.trailers.show', ['trailer' => $trailer['id']]) }}">View / Manage &raquo;</a></td>
                    </tr>
                @empty
                    <tr><td colspan="3">No trailers yet.</td></tr>
                @endforelse
            </tbody>
        </table>

        <details style="margin-top:1rem;">
            <summary>+ Add Trailer</summary>
            <form method="POST" action="{{ route('admin.equipment.trailers.store') }}" class="equip-form">
                @csrf
                <label for="trailer-identifier">Identifier</label>
                <input type="text" name="identifier" id="trailer-identifier" required>
                <label for="trailer-esd">Equipment Set Description</label>
                <input type="text" name="equipment_set_description" id="trailer-esd">
                {{--
                    Michael, 2026-08-31 -- confirmed with Michael: this
                    is a light, human-readable label for the set as a
                    whole -- the actual audit-grade uniqueness already
                    lives on each individual component's own identifier
                    (Light Source, Photo Cell, Op-Amp Card, Data Source,
                    Monitor), tracked separately on the Testing System
                    itself, not here.
                --}}
                <p class="hint" style="margin-top:0.25rem;">A plain-language label for this trailer's equipment set (e.g. "Standard EPA Method 9 rig") -- not where component-level identification lives. Each individual component (Light Source, Photo Cell, Op-Amp Card, Data Source, Monitor) gets its own unique identifier separately, on the Testing System.</p>
                <button type="submit" class="btn-primary" style="margin-top:0.75rem;">Add Trailer</button>
            </form>
        </details>
    </div>

    <div class="equip-section">
        <h2>Trucks</h2>
        <table>
            <thead>
                <tr><th>Identifier</th><th>Paired Trailer</th></tr>
            </thead>
            <tbody>
                @forelse ($trucks as $truck)
                    <tr>
                        <td>{{ $truck['identifier'] ?? '' }}</td>
                        <td>{{ $truck['pairedTrailer']['identifier'] ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2">No trucks yet.</td></tr>
                @endforelse
            </tbody>
        </table>

        <details style="margin-top:1rem;">
            <summary>+ Add Truck</summary>
            <form method="POST" action="{{ route('admin.equipment.trucks.store') }}" class="equip-form">
                @csrf
                <label for="truck-identifier">Identifier</label>
                <input type="text" name="identifier" id="truck-identifier" required>
                <label for="truck-paired-trailer">Paired Trailer</label>
                <select name="paired_trailer_id" id="truck-paired-trailer">
                    <option value="">-- None --</option>
                    @foreach ($trailers as $trailer)
                        <option value="{{ $trailer['id'] }}">{{ $trailer['identifier'] ?? '' }}</option>
                    @endforeach
                </select>
                {{--
                    Michael, 2026-08-31 -- confirmed with Michael: all
                    Truck/Trailer combinations are fluid and assigned
                    per-session, no fixed exceptions -- matches Truck's
                    own existing Javadoc ("a DEFAULT/typical pairing,
                    not a hard constraint") uniformly, across every
                    truck and trailer.
                --}}
                <p class="hint" style="margin-top:0.25rem;">Typical pairing, not a hard rule -- combinations are fluid and assigned per-session.</p>
                <button type="submit" class="btn-primary" style="margin-top:0.75rem;">Add Truck</button>
            </form>
        </details>
    </div>
@endsection
