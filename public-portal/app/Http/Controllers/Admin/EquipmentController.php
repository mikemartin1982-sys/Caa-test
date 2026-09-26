<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ComplianceEngine\ComplianceEngineClient;
use App\Services\ComplianceEngine\ComplianceEngineConflictException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Michael, 2026-08-31 -- Truck/Trailer Equipment feature. Confirmed
 * with Michael directly: the backend (entities, repositories,
 * controllers) already existed before tonight -- only this, the
 * actual Laravel UI, was ever genuinely missing.
 */
class EquipmentController extends Controller
{
    public function __construct(private ComplianceEngineClient $engine)
    {
    }

    public function index(): View
    {
        return view('admin.equipment.index', [
            'trucks' => $this->engine->listTrucks(),
            'trailers' => $this->engine->listTrailers(),
        ]);
    }

    public function storeTruck(Request $request): RedirectResponse
    {
        // Michael, 2026-08-31 -- matches the real, existing
        // TruckController.CreateTruckRequest exactly (identifier,
        // pairedTrailerId only) -- confirmed no shortCode field exists
        // on that real endpoint at all, and no update/PATCH endpoint
        // exists either to set it afterward. shortCode stays
        // display-only for now, populated some other way (or a real
        // gap worth confirming, not assumed).
        $validated = $request->validate([
            'identifier' => ['required', 'string'],
            'paired_trailer_id' => ['nullable', 'integer'],
        ]);

        try {
            $this->engine->createTruck([
                'identifier' => $validated['identifier'],
                'pairedTrailerId' => $validated['paired_trailer_id'] ?? null,
            ]);
        } catch (ComplianceEngineConflictException $e) {
            return back()->withInput()->with('status', 'Could not add truck: ' . $e->getMessage());
        }

        return redirect()->route('admin.equipment.index')->with('status', 'Truck added.');
    }

    public function storeTrailer(Request $request): RedirectResponse
    {
        // Michael, 2026-08-31 -- same reasoning as storeTruck() above --
        // matches the real, existing TrailerController.CreateTrailerRequest
        // exactly (identifier, equipmentSetDescription only).
        $validated = $request->validate([
            'identifier' => ['required', 'string'],
            'equipment_set_description' => ['nullable', 'string'],
        ]);

        try {
            $this->engine->createTrailer([
                'identifier' => $validated['identifier'],
                'equipmentSetDescription' => $validated['equipment_set_description'] ?? null,
            ]);
        } catch (ComplianceEngineConflictException $e) {
            return back()->withInput()->with('status', 'Could not add trailer: ' . $e->getMessage());
        }

        return redirect()->route('admin.equipment.index')->with('status', 'Trailer added.');
    }

    /**
     * Michael, 2026-08-31 -- the real Trailer detail page: identity,
     * both TestingSystems (Primary/Secondary) each with their own
     * current validity status, and the 3 CalibrationPanes. Built here
     * from several separate calls, not one aggregate endpoint -- no
     * "Trailer detail" endpoint exists on the real, existing Java side
     * (that was part of my own, mistaken rebuild tonight, since
     * deleted), so this stitches together the real, existing endpoints
     * instead.
     */
    public function showTrailer(int $trailer): View
    {
        $trailers = $this->engine->listTrailers();
        $trailerRecord = collect($trailers)->firstWhere('id', $trailer);
        if (!$trailerRecord) {
            abort(404, 'Trailer not found.');
        }

        $systems = $this->engine->getTestingSystems($trailer);
        foreach ($systems as &$system) {
            $system['currentlyValid'] = $this->engine->getCalibrationValidity($system['id']);
        }
        unset($system);

        return view('admin.equipment.trailer-show', [
            'trailerRecord' => $trailerRecord,
            'testingSystems' => $systems,
            'panes' => $this->engine->getTrailerPanes($trailer),
        ]);
    }

    public function storeTestingSystem(Request $request, int $trailer): RedirectResponse
    {
        $validated = $request->validate([
            'designation' => ['required', 'in:PRIMARY,SECONDARY'],
            'light_source_id' => ['nullable', 'string'],
            'photo_cell_id' => ['nullable', 'string'],
            'op_amp_card_id' => ['nullable', 'string'],
            'data_source_id' => ['nullable', 'string'],
            'monitor_id' => ['nullable', 'string'],
        ]);

        try {
            $this->engine->createTestingSystem([
                'trailerId' => $trailer,
                'designation' => $validated['designation'],
                'lightSourceId' => $validated['light_source_id'] ?? null,
                'photoCellId' => $validated['photo_cell_id'] ?? null,
                'opAmpCardId' => $validated['op_amp_card_id'] ?? null,
                'dataSourceId' => $validated['data_source_id'] ?? null,
                'monitorId' => $validated['monitor_id'] ?? null,
            ]);
        } catch (ComplianceEngineConflictException $e) {
            return back()->withInput()->with('status', 'Could not add testing system: ' . $e->getMessage());
        }

        return redirect()->route('admin.equipment.trailers.show', ['trailer' => $trailer])->with('status', 'Testing system added.');
    }

    public function storeCalibrationPane(Request $request, int $trailer): RedirectResponse
    {
        $validated = $request->validate([
            'pane_identifier' => ['required', 'string'],
            'certified_opacity_value' => ['required', 'numeric'],
            'last_nist_verification_date' => ['required', 'date'],
        ]);

        try {
            $this->engine->createCalibrationPane($trailer, [
                'paneIdentifier' => $validated['pane_identifier'],
                'certifiedOpacityValue' => $validated['certified_opacity_value'],
                'lastNistVerificationDate' => $validated['last_nist_verification_date'],
            ]);
        } catch (ComplianceEngineConflictException $e) {
            return back()->withInput()->with('status', 'Could not add calibration pane: ' . $e->getMessage());
        }

        return redirect()->route('admin.equipment.trailers.show', ['trailer' => $trailer])->with('status', 'Calibration pane added.');
    }

    /**
     * Michael, 2026-08-31 -- panes get re-certified annually; direct
     * editing, not a new record each time -- confirmed with Michael
     * the real, authoritative history already lives outside this
     * system (physical NIST documentation).
     */
    public function updateCalibrationPane(Request $request, int $trailer, int $pane): RedirectResponse
    {
        $validated = $request->validate([
            'pane_identifier' => ['required', 'string'],
            'certified_opacity_value' => ['required', 'numeric'],
            'last_nist_verification_date' => ['required', 'date'],
        ]);

        try {
            $this->engine->updateCalibrationPane($pane, [
                'paneIdentifier' => $validated['pane_identifier'],
                'certifiedOpacityValue' => $validated['certified_opacity_value'],
                'lastNistVerificationDate' => $validated['last_nist_verification_date'],
            ]);
        } catch (ComplianceEngineConflictException $e) {
            return back()->withInput()->with('status', 'Could not update calibration pane: ' . $e->getMessage());
        }

        return redirect()->route('admin.equipment.trailers.show', ['trailer' => $trailer])->with('status', 'Calibration pane updated.');
    }

    public function storeMaintenanceEvent(Request $request, int $testingSystem): RedirectResponse
    {
        $validated = $request->validate([
            'event_type' => ['required', 'in:SIGNIFICANT_REPAIR,REPLACE'],
            'component_affected' => ['required', 'in:MONITOR,LIGHT_SOURCE,PHOTO_CELL,OP_AMP_CARD,DATA_SOURCE'],
            'event_date' => ['nullable', 'date'],
        ]);

        try {
            $this->engine->createMaintenanceEvent($testingSystem, [
                'eventType' => $validated['event_type'],
                'componentAffected' => $validated['component_affected'],
                'eventDate' => $validated['event_date'] ?? null,
            ]);
        } catch (\RuntimeException $e) {
            return back()->with('status', 'Could not log maintenance event -- check the server log for the actual cause.');
        }

        return back()->with('status', 'Maintenance event logged. This invalidates the current 5-Filter until a new one is entered.');
    }

    /**
     * Michael, 2026-08-31 -- step 1 of the import/review flow. Upload
     * the real Chart Recorder zip, get back the parsed data plus this
     * trailer's real CalibrationPanes -- nothing saved yet. Confirmed
     * with Michael: the reviewer confirms which real pane is Low/
     * Medium/High themselves on the next screen, not an automatic
     * guess.
     */
    public function importPreview(Request $request, int $testingSystem): View|RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file'],
        ]);

        try {
            $result = $this->engine->previewCalibrationImport($testingSystem, $request->file('file'));
        } catch (ComplianceEngineConflictException $e) {
            return back()->with('status', 'Could not read this file: ' . $e->getMessage());
        }

        return view('admin.equipment.import-review', [
            'testingSystemId' => $testingSystem,
            'trailerId' => $result['trailerId'],
            'parsed' => $result['parsed'],
            'trailerPanes' => $result['trailerPanes'],
        ]);
    }

    /**
     * Michael, 2026-08-31 -- step 2, the actual submit: the reviewer
     * has confirmed pane matches and entered their own six pass/fail
     * judgments here. This is the ONLY place those six booleans get
     * supplied -- nothing upstream (the parser, this controller)
     * computes or guesses at them, confirmed with Michael as the whole
     * point of this feature.
     *
     * triggerReason is a plain text field for now, not a real dropdown
     * -- CalibrationTrigger's actual enum values were never confirmed
     * tonight, and guessing them risks the exact class of mismatch
     * already hit twice tonight already. Worth fixing once those
     * values are confirmed.
     */
    public function submitCalibration(Request $request, int $testingSystem): RedirectResponse
    {
        $validated = $request->validate([
            'trigger_reason' => ['required', 'string'],
            'light_source_voltage_pass' => ['required', 'boolean'],
            'photocell_spectral_response_pass' => ['required', 'boolean'],
            'angle_of_view_pass' => ['required', 'boolean'],
            'angle_of_projection_pass' => ['required', 'boolean'],
            'calibration_error_pass' => ['required', 'boolean'],
            'response_time_pass' => ['required', 'boolean'],
            'low_pane_id' => ['required', 'integer'],
            'medium_pane_id' => ['required', 'integer'],
            'high_pane_id' => ['required', 'integer'],
            'low_values' => ['required', 'array'],
            'medium_values' => ['required', 'array'],
            'high_values' => ['required', 'array'],
        ]);

        // Michael, 2026-08-31 -- found live: Chart Recorder's own
        // pane_readings table has a real unique constraint on
        // (calibration_record_id, calibration_pane_id, sequence_number)
        // -- if the same physical pane gets selected for more than one
        // of the three groups here, both groups independently number
        // their own readings 1-5, so two groups sharing a pane collide
        // directly on that constraint. Nothing previously stopped the
        // same pane from being picked twice across the three dropdowns
        // -- caught here now, as a clean, anticipated error, rather
        // than a raw SQL constraint violation reaching the person as
        // an unhandled 500.
        $paneIds = [$validated['low_pane_id'], $validated['medium_pane_id'], $validated['high_pane_id']];
        if (count(array_unique($paneIds)) !== 3) {
            return back()->withInput()->with('status', 'Low, Medium, and High must each be a different pane -- the same pane was selected more than once.');
        }

        $paneReadings = [];
        foreach (['low', 'medium', 'high'] as $group) {
            $paneId = $validated["{$group}_pane_id"];
            foreach (array_values($validated["{$group}_values"]) as $i => $value) {
                $paneReadings[] = [
                    'calibrationPaneId' => $paneId,
                    'sequenceNumber' => $i + 1,
                    'recordedValue' => $value,
                ];
            }
        }

        try {
            $this->engine->submitCalibrationRecord($testingSystem, [
                'triggerReason' => $validated['trigger_reason'],
                'lightSourceVoltagePass' => $request->boolean('light_source_voltage_pass'),
                'photocellSpectralResponsePass' => $request->boolean('photocell_spectral_response_pass'),
                'angleOfViewPass' => $request->boolean('angle_of_view_pass'),
                'angleOfProjectionPass' => $request->boolean('angle_of_projection_pass'),
                'calibrationErrorPass' => $request->boolean('calibration_error_pass'),
                'responseTimePass' => $request->boolean('response_time_pass'),
                'paneReadings' => $paneReadings,
            ]);
        } catch (ComplianceEngineConflictException $e) {
            return back()->withInput()->with('status', 'Could not save this 5-Filter record: ' . $e->getMessage());
        }

        $status = '5-Filter calibration record saved.';

        // Michael, 2026-08-31 -- opt-in, reviewer-confirmed sync of the
        // TestingSystem's own component IDs to what was parsed from
        // this same import -- deliberately run AFTER the 5-Filter
        // record above already saved successfully, and deliberately
        // best-effort: a failure here shouldn't undo or block the more
        // important record that already saved, just say so honestly.
        if ($request->boolean('sync_component_ids')) {
            try {
                $this->engine->updateTestingSystem($testingSystem, [
                    'lightSourceId' => $request->input('parsed_light_source'),
                    'photoCellId' => $request->input('parsed_photo_cell_id'),
                    'opAmpCardId' => $request->input('parsed_op_amp_card_id'),
                    'dataSourceId' => $request->input('parsed_data_source_id'),
                    'monitorId' => $request->input('parsed_monitor_id'),
                ]);
                $status .= ' Component IDs updated to match this import.';
            } catch (\Throwable $e) {
                $status .= ' (Could not update component IDs -- the 5-Filter record itself still saved.)';
            }
        }

        return redirect()->route('admin.equipment.trailers.show', ['trailer' => $request->input('trailer_id')])
            ->with('status', $status);
    }
}
