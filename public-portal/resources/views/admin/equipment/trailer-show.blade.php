@extends('layouts.admin')

@section('title', ($trailerRecord['identifier'] ?? 'Trailer') . ' - Admin')

@push('styles')
<style>
    .equip-form { max-width: 400px; margin-top: 0.75rem; }
    .equip-form label { display: block; margin-top: 0.5rem; font-size: 0.85rem; }
    .equip-form input, .equip-form select { width: 100%; padding: 0.4rem; margin-top: 0.15rem; }
    .equip-section { margin-top: 2rem; }
</style>
@endpush

@section('content')
    <h1>{{ $trailerRecord['identifier'] ?? '' }}</h1>
    <p><a href="{{ route('admin.equipment.index') }}">&laquo; Back to Trucks &amp; Trailers</a></p>
    <p>{{ $trailerRecord['equipmentSetDescription'] ?? '' }}</p>

    <div class="equip-section">
        <h2>Testing Systems</h2>
        @forelse ($testingSystems as $system)
            <div class="admin-card">
                <strong>{{ $system['designation'] ?? '' }}</strong>
                @if ($system['currentlyValid'] ?? false)
                    <span class="status-badge status-active">5-Filter Valid</span>
                @else
                    <span class="status-badge status-alert">5-Filter NOT Valid</span>
                @endif

                <div style="margin-top:0.5rem; font-size:0.85rem; color:#555;">
                    Light: {{ $system['lightSourceId'] ?? '—' }} &middot;
                    Photocell: {{ $system['photoCellId'] ?? '—' }} &middot;
                    Op-Amp Card: {{ $system['opAmpCardId'] ?? '—' }} &middot;
                    Data Source: {{ $system['dataSourceId'] ?? '—' }} &middot;
                    Monitor: {{ $system['monitorId'] ?? '—' }}
                </div>

                <div style="margin-top:0.75rem;">
                    {{--
                        Michael, 2026-08-31 -- found live: a 5-Filter
                        import gets all the way to the pane-matching
                        step on the review page before it becomes clear
                        the trailer doesn't actually have all 3 panes on
                        file yet -- a real dead end, discovered late.
                        Caught upfront here instead, before the zip is
                        even uploaded.
                    --}}
                    @if (count($panes) < 3)
                        <p class="hint">This trailer only has {{ count($panes) }} of 3 calibration panes on file -- add the rest below before importing a 5-Filter.</p>
                    @else
                        {{--
                            Michael, 2026-08-31 -- step 1 of the import flow:
                            upload the real Chart Recorder zip here. This
                            posts to import-preview, which renders the
                            review screen -- nothing is saved by this step
                            alone.
                        --}}
                        <form method="POST" action="{{ route('admin.equipment.import-preview', ['testingSystem' => $system['id']]) }}" enctype="multipart/form-data" style="display:inline-block;">
                            @csrf
                            <input type="file" name="file" accept=".zip" required style="font-size:0.8rem;">
                            <button type="submit" class="btn-primary" style="font-size:0.8rem;">Import 5-Filter (.zip)</button>
                        </form>
                    @endif
                </div>

                <details style="margin-top:0.75rem;">
                    <summary style="font-size:0.85rem;">Log Maintenance Event (Significant Repair / Replace)</summary>
                    {{--
                        Michael, 2026-08-31 -- confirmed with Michael:
                        this immediately invalidates the current
                        5-Filter, enforced by CalibrationService.isCurrentlyValid()
                        itself (any maintenance event dated on/after the
                        last calibration invalidates it), independent
                        of whether a new 5-Filter has been entered yet.
                    --}}
                    <form method="POST" action="{{ route('admin.equipment.maintenance-events.store', ['testingSystem' => $system['id']]) }}" class="equip-form">
                        @csrf
                        <label>Event Type</label>
                        <select name="event_type" required>
                            <option value="SIGNIFICANT_REPAIR">Significant Repair</option>
                            <option value="REPLACE">Replace</option>
                        </select>
                        <label>Component Affected</label>
                        <select name="component_affected" required>
                            <option value="MONITOR">Monitor</option>
                            <option value="LIGHT_SOURCE">Light Source</option>
                            <option value="PHOTO_CELL">Photo Cell</option>
                            <option value="OP_AMP_CARD">Op-Amp Card</option>
                            <option value="DATA_SOURCE">Data Source</option>
                        </select>
                        <label>Event Date</label>
                        <input type="date" name="event_date">
                        <button type="submit" class="btn-secondary" style="margin-top:0.5rem; font-size:0.8rem;">Log Event</button>
                    </form>
                </details>
            </div>
        @empty
            <p>No testing systems yet.</p>
        @endforelse

        @if (count($testingSystems) < 2)
            <details style="margin-top:1rem;">
                <summary>+ Add Testing System</summary>
                <form method="POST" action="{{ route('admin.equipment.testing-systems.store', ['trailer' => $trailerRecord['id']]) }}" class="equip-form">
                    @csrf
                    <label>Designation</label>
                    <select name="designation" required>
                        <option value="PRIMARY">Primary</option>
                        <option value="SECONDARY">Secondary</option>
                    </select>
                    <label>Light Source ID</label>
                    <input type="text" name="light_source_id">
                    <label>Photocell ID</label>
                    <input type="text" name="photo_cell_id">
                    <label>Op-Amp Card ID</label>
                    <input type="text" name="op_amp_card_id">
                    <label>Data Source ID</label>
                    <input type="text" name="data_source_id">
                    <label>Monitor ID</label>
                    <input type="text" name="monitor_id">
                    <button type="submit" class="btn-primary" style="margin-top:0.75rem;">Add Testing System</button>
                </form>
            </details>
        @endif
    </div>

    <div class="equip-section">
        <h2>Calibration Panes</h2>
        <table>
            <thead>
                <tr><th>Pane Identifier</th><th>Certified Opacity</th><th>Last NIST Verification</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($panes as $pane)
                    <tr>
                        <td>{{ $pane['paneIdentifier'] ?? '' }}</td>
                        <td>{{ $pane['certifiedOpacityValue'] ?? '' }}%</td>
                        <td>{{ $pane['lastNistVerificationDate'] ?? '' }}</td>
                        <td>
                            {{--
                                Michael, 2026-08-31 -- panes get
                                re-certified annually; confirmed with
                                Michael direct editing is the priority
                                here, not an internal history of past
                                values (the real paper trail already
                                lives outside this system, in physical
                                NIST documentation).
                            --}}
                            <details>
                                <summary style="font-size:0.8rem; cursor:pointer;">Edit</summary>
                                <form method="POST" action="{{ route('admin.equipment.panes.update', ['trailer' => $trailerRecord['id'], 'pane' => $pane['id']]) }}" class="equip-form">
                                    @csrf
                                    @method('PATCH')
                                    <label>Pane Identifier</label>
                                    <input type="text" name="pane_identifier" value="{{ $pane['paneIdentifier'] ?? '' }}" required>
                                    <label>Certified Opacity Value (%)</label>
                                    <input type="number" step="0.01" name="certified_opacity_value" value="{{ $pane['certifiedOpacityValue'] ?? '' }}" required>
                                    <label>Last NIST Verification Date</label>
                                    <input type="date" name="last_nist_verification_date" value="{{ $pane['lastNistVerificationDate'] ?? '' }}" required>
                                    <button type="submit" class="btn-primary" style="margin-top:0.75rem;">Save Changes</button>
                                </form>
                            </details>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4">No calibration panes yet.</td></tr>
                @endforelse
            </tbody>
        </table>

        @if (count($panes) < 3)
            <details style="margin-top:1rem;">
                <summary>+ Add Calibration Pane</summary>
                <form method="POST" action="{{ route('admin.equipment.panes.store', ['trailer' => $trailerRecord['id']]) }}" class="equip-form">
                    @csrf
                    <label>Pane Identifier</label>
                    <input type="text" name="pane_identifier" required>
                    <label>Certified Opacity Value (%)</label>
                    <input type="number" step="0.01" name="certified_opacity_value" required>
                    <label>Last NIST Verification Date</label>
                    <input type="date" name="last_nist_verification_date" required>
                    <button type="submit" class="btn-primary" style="margin-top:0.75rem;">Add Pane</button>
                </form>
            </details>
        @endif
    </div>
@endsection
