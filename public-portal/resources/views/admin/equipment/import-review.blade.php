@extends('layouts.admin')

@section('title', '5-Filter Import Review - Admin')

@push('styles')
<style>
    .review-section { margin-top: 1.5rem; border: 1px solid #e5e7eb; border-radius: 0.375rem; padding: 1rem; }
    .review-section h3 { margin-top: 0; }
    .pass-fail label { margin-right: 1rem; font-size: 0.85rem; }
    .warning-banner { background: #fff3cd; border: 1px solid #ffc107; border-radius: 0.375rem; padding: 0.75rem; margin-bottom: 1rem; font-size: 0.85rem; }
</style>
@endpush

@section('content')
    <h1>5-Filter Calibration Review</h1>

    <div class="warning-banner">
        This data was parsed directly from the uploaded Chart Recorder file --
        nothing here was computed or judged automatically beyond the pane
        readings' own tolerance math (deterministic arithmetic against each
        pane's real certified value). The six items below are your own
        determination, not a suggestion from this system.
    </div>

    <div class="review-section">
        <h3>Parsed From File</h3>
        <div class="info-row"><strong>Trailer Name</strong> {{ $parsed['systemInfo']['trailerName'] ?? '' }}</div>
        <div class="info-row"><strong>Light Source</strong> {{ $parsed['systemInfo']['lightSource'] ?? '' }}</div>
        <div class="info-row"><strong>Photocell ID</strong> {{ $parsed['systemInfo']['photoCellId'] ?? '' }}</div>
        <div class="info-row"><strong>Op-Amp Card ID</strong> {{ $parsed['systemInfo']['opAmpCardId'] ?? '' }}</div>
        <div class="info-row"><strong>Monitor ID</strong> {{ $parsed['systemInfo']['monitorId'] ?? '' }}</div>
        <div class="info-row"><strong>Data Source ID</strong> {{ $parsed['systemInfo']['dataSourceId'] ?? '' }}</div>
        <div class="info-row"><strong>Date of Calibration</strong> {{ $parsed['dateOfCalibration'] ?? '' }}</div>
        <div class="info-row"><strong>Operator</strong> {{ $parsed['operatorName'] ?? '' }}</div>
        <div class="info-row"><strong>Reviewer (per file)</strong> {{ $parsed['reviewerName'] ?? '' }}</div>

        {{--
            Michael, 2026-08-31 -- offering to sync the Testing System's
            real component IDs to what was just parsed, rather than
            requiring a separate, manual edit step. Defaulted on, but
            genuinely reviewer-confirmed, not automatic -- the parsed
            values above are already shown plainly before this checkbox
            is ever reached.
        --}}
    </div>

    <div class="review-section">
        <h3>Raw Values (no pass/fail determined here)</h3>
        <div class="info-row"><strong>Volts to Light Source</strong> {{ $parsed['rawValues']['voltsToLightSource'] ?? '' }}</div>
        <div class="info-row"><strong>Volts to Smoke Generator</strong> {{ $parsed['rawValues']['voltsToSmokeGenerator'] ?? '' }}</div>
        <div class="info-row"><strong>Response Time Readings (sec)</strong> {{ implode(', ', $parsed['rawValues']['responseTimeSeconds'] ?? []) }}</div>
        <p class="hint">Drift, range/voltage, and angle data aren't captured in this export -- confirm those directly against the source file or Stacktest before marking the items below.</p>
    </div>

    <form method="POST" action="{{ route('admin.equipment.submit-calibration', ['testingSystem' => $testingSystemId]) }}">
        @csrf
        <input type="hidden" name="trailer_id" value="{{ $trailerId }}">
        <input type="hidden" name="parsed_light_source" value="{{ $parsed['systemInfo']['lightSource'] ?? '' }}">
        <input type="hidden" name="parsed_photo_cell_id" value="{{ $parsed['systemInfo']['photoCellId'] ?? '' }}">
        <input type="hidden" name="parsed_op_amp_card_id" value="{{ $parsed['systemInfo']['opAmpCardId'] ?? '' }}">
        <input type="hidden" name="parsed_monitor_id" value="{{ $parsed['systemInfo']['monitorId'] ?? '' }}">
        <input type="hidden" name="parsed_data_source_id" value="{{ $parsed['systemInfo']['dataSourceId'] ?? '' }}">

        {{--
            Michael, 2026-08-31 -- found live: this checkbox originally
            sat in the "Parsed From File" section, which is entirely
            outside this <form> (a display-only block) -- meaning it
            always rendered checked, but its state never actually
            reached the server at all. Moved here, genuinely inside the
            form, alongside the same parsed_* hidden fields it controls.
        --}}
        <div style="margin-bottom:1.5rem;">
            <label><input type="checkbox" name="sync_component_ids" value="1" checked> Update this Testing System's component IDs to match the parsed values above</label>
        </div>

        <div class="review-section">
            <h3>Pane Readings</h3>
            <p class="hint">Confirm which real calibration pane corresponds to each group below -- this isn't matched automatically.</p>

            @foreach (['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'] as $key => $label)
                <div style="margin-top:1rem;">
                    <strong>{{ $label }}</strong>
                    <select name="{{ $key }}_pane_id" id="{{ $key }}_pane_id" class="pane-select" required style="margin-left:0.5rem;">
                        <option value="">-- Select the real pane --</option>
                        @foreach ($trailerPanes as $pane)
                            <option value="{{ $pane['id'] }}">{{ $pane['paneIdentifier'] ?? '' }} ({{ $pane['certifiedOpacityValue'] ?? '' }}%)</option>
                        @endforeach
                    </select>
                    <table>
                        <tbody>
                            <tr>
                                @foreach ($parsed['paneReadings'][$key] ?? [] as $i => $value)
                                    <td>
                                        {{ $value }}
                                        <input type="hidden" name="{{ $key }}_values[]" value="{{ $value }}">
                                    </td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>
            @endforeach

            {{--
                Michael, 2026-08-31 -- found live: nothing stopped the
                same physical pane from being picked for more than one
                of the three groups, which produced a real 500 (Chart
                Recorder's own database has a unique constraint per
                pane+sequence, and two groups sharing a pane collide
                directly on it). Server-side validation now prevents
                the bad submission cleanly either way, but disabling
                an already-picked pane in the other two dropdowns stops
                the mistake from being made at all, not just caught
                after the fact.
            --}}
            <script>
            (function () {
                const selects = document.querySelectorAll('.pane-select');

                function syncOptions() {
                    const chosen = Array.from(selects).map(s => s.value).filter(v => v !== '');
                    selects.forEach(select => {
                        Array.from(select.options).forEach(opt => {
                            if (opt.value === '') return;
                            opt.disabled = chosen.includes(opt.value) && opt.value !== select.value;
                        });
                    });
                }

                selects.forEach(select => select.addEventListener('change', syncOptions));
                syncOptions();
            })();
            </script>
        </div>

        <div class="review-section">
            <h3>Your Determination</h3>
            <p class="hint">Each of these six is your own pass/fail judgment, not something this system infers.</p>

            @foreach ([
                'light_source_voltage_pass' => 'Light Source Voltage (±5% of nominal rated voltage)',
                'photocell_spectral_response_pass' => 'Photocell Spectral Response (±3% opacity)',
                'angle_of_view_pass' => 'Angle of View (15° maximum total angle)',
                'angle_of_projection_pass' => 'Angle of Projection (15° maximum total angle)',
                'calibration_error_pass' => 'Calibration Error (±1% opacity drift over 30 minutes)',
                'response_time_pass' => 'Response Time (±5 seconds)',
            ] as $field => $label)
                <div style="margin-top:0.75rem;">
                    <div>{{ $label }}</div>
                    <div class="pass-fail">
                        <label><input type="radio" name="{{ $field }}" value="1" required> Pass</label>
                        <label><input type="radio" name="{{ $field }}" value="0"> Fail</label>
                    </div>
                </div>
            @endforeach

            <div style="margin-top:1rem;">
                {{--
                    Michael, 2026-08-31 -- real dropdown now, matching
                    CalibrationTrigger's actual, confirmed enum values.
                --}}
                <label for="trigger_reason">Trigger Reason</label>
                <select name="trigger_reason" id="trigger_reason" required>
                    <option value="">-- Select a reason --</option>
                    <option value="SCHEDULED_6_MONTH">Scheduled (6-Month)</option>
                    <option value="FOLLOWING_REPAIR">Following a Significant Repair</option>
                    <option value="FOLLOWING_REPLACE">Following a Replace</option>
                </select>
            </div>
        </div>

        <button type="submit" class="btn-primary" style="margin-top:1.5rem;">Save 5-Filter Calibration Record</button>
    </form>
@endsection