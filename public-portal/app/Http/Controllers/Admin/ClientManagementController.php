<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ComplianceEngine\ComplianceEngineClient;
use App\Services\ComplianceEngine\ComplianceEngineConflictException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Michael, 2026-08-22 -- the Client Page: search/list, edit an
 * existing client's fields (previously impossible -- there was no
 * PATCH endpoint on the Java side at all before tonight), and manage
 * Purchase Orders (clients often run one PO across a full year or
 * multiple seasons, not per-session).
 */
class ClientManagementController extends Controller
{
    public function __construct(private ComplianceEngineClient $engine)
    {
    }

    public function index(Request $request): View
    {
        $query = $request->query('q', '');
        $clients = $query !== '' ? $this->engine->searchClients($query) : [];

        return view('admin.clients.index', [
            'clients' => $clients,
            'query' => $query,
        ]);
    }

    public function edit(int $clientId): View
    {
        $purchaseOrders = $this->engine->listPurchaseOrders($clientId);

        // Merge each PO's associated sessions in -- found live while
        // building the view, which expects $po['sessions'] but this
        // wasn't actually being fetched at all. listPurchaseOrders()
        // doesn't include it (a separate endpoint, since a PO can cover
        // many sessions), so it has to be pulled per-PO here.
        foreach ($purchaseOrders as &$po) {
            $po['sessions'] = $this->engine->listPurchaseOrderSessions($clientId, $po['id']);
        }
        unset($po);

        return view('admin.clients.edit', [
            'clientRecord' => $this->engine->getClient($clientId),
            'purchaseOrders' => $purchaseOrders,
        ]);
    }

    /**
     * Michael, 2026-08-23 -- moved to its own page, off the Client
     * edit form -- some clients have 15+ active employees (not
     * counting inactive), and embedding the full roster inline made
     * the edit page unwieldy. Matches DIBs' own real structure too --
     * "View Employees" was always a separate page there, linked from
     * the Client edit page via "Client's Employees »", not inlined.
     */
    public function employees(int $clientId): View
    {
        return view('admin.clients.employees', [
            'clientRecord' => $this->engine->getClient($clientId),
            'employeeRoster' => $this->engine->getStudentRoster($clientId),
        ]);
    }

    /**
     * Michael, 2026-08-25 -- Section 4a extension, piece 3A: the
     * Student page's own certification history -- confirmed with
     * Michael as needing a real, living UI, not just an API endpoint.
     * No dedicated "get single student" Java endpoint exists yet, and
     * doesn't need to -- the roster endpoint already returns every
     * field this page's header needs, so it's fetched once and
     * filtered to this one student here rather than adding a
     * redundant endpoint just for this.
     */
    public function showStudent(int $clientId, int $studentId): View
    {
        $roster = $this->engine->getStudentRoster($clientId);
        $student = collect($roster)->firstWhere('id', $studentId);

        if (!$student) {
            abort(404, 'Student not found for this client.');
        }

        return view('admin.students.show', [
            'clientRecord' => $this->engine->getClient($clientId),
            'student' => $student,
            'certificationHistory' => $this->engine->getStudentCertificationHistory($clientId, $studentId),
            // Michael, 2026-08-30 -- Lecture Certificate Upload feature.
            'lectureCertificateHistory' => $this->engine->getLectureCertificateHistory($studentId),
            'providers' => $this->engine->listProviders(),
        ]);
    }

    /**
     * Michael, 2026-08-24 -- "Add Employee" step 2's actual form
     * submission. Reuses the exact same validation and
     * createStudent() call as ManualEnrollController::storeStudent()
     * (Manual Enroll's own inline "add employee" flow) -- same
     * underlying operation, just a standard form-post-and-redirect
     * here instead of AJAX, matching how every other Student action
     * tonight (update/reassign/combine) already works.
     */
    public function storeEmployee(Request $request, int $clientId): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string'],
            'phone' => ['nullable', 'string'],
            'email' => ['required', 'email'],
        ]);

        try {
            $this->engine->createStudent($clientId, $validated);
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('status', 'Could not add employee -- check the server log for the actual cause.');
        }

        return redirect()->route('admin.clients.employees', ['client' => $clientId])->with('status', 'Employee added.');
    }

    /** Michael, 2026-08-23 -- Client Page roster (Layer 2): the inline Active/Inactive toggle and other quick edits from the employee table. */
    public function updateStudent(Request $request, int $clientId, int $student): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string'],
            'phone' => ['nullable', 'string'],
            'email' => ['nullable', 'email'],
        ]);

        try {
            $this->engine->updateStudent($clientId, $student, [
                'name' => $validated['name'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'email' => $validated['email'] ?? null,
                'active' => $request->boolean('active'),
                'lectureFeeExempt' => $request->boolean('lecture_fee_exempt'),
            ]);
        } catch (\RuntimeException $e) {
            return back()->with('status', 'Could not update employee -- check the server log for the actual cause.');
        }

        return back()->with('status', 'Employee updated.');
    }

    /** Michael, 2026-08-24 -- "Reassign Employee" UI, from the Employees roster page. */
    public function reassignStudent(Request $request, int $clientId, int $student): RedirectResponse
    {
        $validated = $request->validate(['new_client_id' => ['required', 'integer']]);

        try {
            $this->engine->reassignStudent($clientId, $student, (int) $validated['new_client_id']);
        } catch (ComplianceEngineConflictException $e) {
            return back()->with('status', 'Could not reassign: ' . $e->getMessage());
        } catch (\RuntimeException $e) {
            return back()->with('status', 'Could not reassign employee -- check the server log for the actual cause.');
        }

        return redirect()->route('admin.clients.employees', ['client' => $clientId])->with('status', 'Employee reassigned.');
    }

    /** Michael, 2026-08-24 -- "Combine Employee" UI, from the Employees roster page. $student is the survivor. */
    public function combineStudent(Request $request, int $clientId, int $student): RedirectResponse
    {
        $validated = $request->validate(['duplicate_student_id' => ['required', 'integer']]);

        try {
            $this->engine->combineStudents($clientId, $student, (int) $validated['duplicate_student_id']);
        } catch (ComplianceEngineConflictException $e) {
            // Michael, 2026-08-24 -- surfaces the real reason a combine
            // was refused (e.g. both students separately enrolled in
            // the same session), not a generic failure -- this is the
            // exact case the backend was built to catch and explain,
            // not just reject silently.
            return back()->with('status', 'Could not combine: ' . $e->getMessage());
        } catch (\RuntimeException $e) {
            return back()->with('status', 'Could not combine employees -- check the server log for the actual cause.');
        }

        return redirect()->route('admin.clients.employees', ['client' => $clientId])->with('status', 'Employees combined.');
    }

    public function update(Request $request, int $clientId): RedirectResponse
    {
        $validated = $request->validate([
            'company' => ['required', 'string'],
            'first_name' => ['nullable', 'string'],
            'last_name' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string'],
            'state' => ['nullable', 'string', 'max:2'],
            'zip' => ['nullable', 'string'],
            'phone' => ['nullable', 'string'],
            'email' => ['nullable', 'email'],
            'lead_source' => ['nullable', 'string'],
            'vr_client' => ['nullable'],
            'lecture_fee_exempt' => ['nullable'],
            'billing_contact_name' => ['nullable', 'string'],
            'billing_email' => ['nullable', 'email'],
            'billing_phone' => ['nullable', 'string'],
        ]);

        try {
            $this->engine->updateClient($clientId, [
                'company' => $validated['company'],
                'firstName' => $validated['first_name'] ?? null,
                'lastName' => $validated['last_name'] ?? null,
                'address' => $validated['address'] ?? null,
                'city' => $validated['city'] ?? null,
                'state' => $validated['state'] ?? null,
                'zip' => $validated['zip'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'email' => $validated['email'] ?? null,
                'leadSource' => $validated['lead_source'] ?? null,
                'prefNewsletter' => $request->boolean('pref_newsletter'),
                'prefClassConfirms' => $request->boolean('pref_class_confirms'),
                'prefCertReminders' => $request->boolean('pref_cert_reminders'),
                'vrClient' => $request->boolean('vr_client'),
                'lectureFeeExempt' => $request->boolean('lecture_fee_exempt'),
                'billingContactName' => $validated['billing_contact_name'] ?? null,
                'billingEmail' => $validated['billing_email'] ?? null,
                'billingPhone' => $validated['billing_phone'] ?? null,
            ]);
        } catch (ComplianceEngineConflictException $e) {
            return back()->withInput()->with('status', 'Could not save: ' . $e->getMessage());
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('status', 'Could not save client -- check the server log for the actual cause.');
        }

        return redirect()->route('admin.clients.edit', ['client' => $clientId])->with('status', 'Client saved.');
    }

    public function storePurchaseOrder(Request $request, int $clientId): RedirectResponse
    {
        $validated = $request->validate([
            'po_number' => ['nullable', 'string', 'max:30'],
            'active' => ['nullable'],
            'expiration_date' => ['nullable', 'date'],
            'short_description' => ['nullable', 'string', 'max:50'],
            'contact_first_name' => ['nullable', 'string', 'max:50'],
            'contact_last_name' => ['nullable', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:50'],
            'starting_amount' => ['nullable', 'numeric'],
            'amount_used' => ['nullable', 'numeric'],
            'threshold_amount' => ['nullable', 'numeric'],
        ]);

        try {
            $this->engine->createPurchaseOrder($clientId, [
                'poNumber' => $validated['po_number'] ?? null,
                'active' => $request->boolean('active'),
                'expirationDate' => $validated['expiration_date'] ?? null,
                'shortDescription' => $validated['short_description'] ?? null,
                'contactFirstName' => $validated['contact_first_name'] ?? null,
                'contactLastName' => $validated['contact_last_name'] ?? null,
                'contactEmail' => $validated['contact_email'] ?? null,
                'startingAmount' => $validated['starting_amount'] ?? 0,
                'amountUsed' => $validated['amount_used'] ?? 0,
                'thresholdAmount' => $validated['threshold_amount'] ?? 0,
            ]);
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('status', 'Could not create PO -- check the server log for the actual cause.');
        }

        return redirect()->route('admin.clients.edit', ['client' => $clientId])->with('status', 'Purchase order created.');
    }

    public function updatePurchaseOrder(Request $request, int $clientId, int $poId): RedirectResponse
    {
        $validated = $request->validate([
            'po_number' => ['nullable', 'string', 'max:30'],
            'expiration_date' => ['nullable', 'date'],
            'short_description' => ['nullable', 'string', 'max:50'],
            'contact_first_name' => ['nullable', 'string', 'max:50'],
            'contact_last_name' => ['nullable', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:50'],
            'starting_amount' => ['nullable', 'numeric'],
            'amount_used' => ['nullable', 'numeric'],
            'threshold_amount' => ['nullable', 'numeric'],
        ]);

        try {
            $this->engine->updatePurchaseOrder($clientId, $poId, [
                'poNumber' => $validated['po_number'] ?? null,
                'active' => $request->boolean('active'),
                'expirationDate' => $validated['expiration_date'] ?? null,
                'shortDescription' => $validated['short_description'] ?? null,
                'contactFirstName' => $validated['contact_first_name'] ?? null,
                'contactLastName' => $validated['contact_last_name'] ?? null,
                'contactEmail' => $validated['contact_email'] ?? null,
                'startingAmount' => $validated['starting_amount'] ?? null,
                'amountUsed' => $validated['amount_used'] ?? null,
                'thresholdAmount' => $validated['threshold_amount'] ?? null,
            ]);
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('status', 'Could not update PO -- check the server log for the actual cause.');
        }

        return redirect()->route('admin.clients.edit', ['client' => $clientId])->with('status', 'Purchase order updated.');
    }

    public function addPurchaseOrderSession(Request $request, int $clientId, int $poId): RedirectResponse
    {
        $validated = $request->validate([
            'session_id' => ['required', 'integer'],
        ]);

        try {
            $this->engine->addPurchaseOrderSession($clientId, $poId, (int) $validated['session_id']);
        } catch (ComplianceEngineConflictException $e) {
            return back()->with('status', 'Could not add session: ' . $e->getMessage());
        } catch (\RuntimeException $e) {
            return back()->with('status', 'Could not add session -- check the server log for the actual cause.');
        }

        return back()->with('status', 'Session added to PO.');
    }

    public function removePurchaseOrderSession(int $clientId, int $poId, int $sessionId): RedirectResponse
    {
        $this->engine->removePurchaseOrderSession($clientId, $poId, $sessionId);
        return back()->with('status', 'Session removed from PO.');
    }
}
