<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ComplianceEngine\ComplianceEngineClient;
use App\Services\ComplianceEngine\ComplianceEngineForbiddenException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Michael, 2026-08-22 -- lets Compliance Administrators (Derek, Joe)
 * create staff logins and edit existing ones through a real form,
 * rather than the raw API calls every staff account so far has gone
 * through. The Java side (StaffUserController) already enforces
 * Compliance-Administrator-only via @PreAuthorize -- this controller
 * gates page ACCESS the same way, so a non-admin sees a clean message
 * instead of a form they'd only find out they can't submit after
 * filling it in.
 */
class StaffManagementController extends Controller
{
    public function __construct(private ComplianceEngineClient $engine)
    {
    }

    private function requireAdmin(Request $request): ?RedirectResponse
    {
        if (!$request->user('staff')->isComplianceAdministrator()) {
            return redirect()->route('admin.dashboard')
                ->with('status', 'Only Compliance Administrators can manage staff accounts.');
        }
        return null;
    }

    public function index(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) {
            return $redirect;
        }

        return view('admin.staff.index', [
            'staff' => $this->engine->listStaff(),
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) {
            return $redirect;
        }

        return view('admin.staff.create');
    }

    public function store(Request $request): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) {
            return $redirect;
        }

        $validated = $request->validate([
            'name' => ['required', 'string'],
            'username' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:STAFF,COMPLIANCE_ADMINISTRATOR'],
            'initials' => ['nullable', 'string'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string'],
            'mobile_phone' => ['nullable', 'string'],
            'job_title' => ['nullable', 'string'],
        ]);

        try {
            $this->engine->createStaff([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'password' => $validated['password'],
                'role' => $validated['role'],
                'initials' => $validated['initials'] ?? null,
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'mobilePhone' => $validated['mobile_phone'] ?? null,
                'jobTitle' => $validated['job_title'] ?? null,
                // Required on the Java side (2026-08-22, found live
                // during testing) -- every Laravel-to-Java call
                // authenticates as the shared service account, never
                // the real logged-in staff member, so Java has no other
                // way to know who's actually creating this account.
                'actingStaffId' => $request->user('staff')->id,
            ]);
        } catch (ComplianceEngineForbiddenException $e) {
            return back()->withInput()->with('status', 'Not permitted: ' . $e->getMessage());
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('status', 'Could not create staff account -- check the server log for the actual cause.');
        }

        return redirect()->route('admin.staff.index')->with('status', 'Staff account created.');
    }

    public function edit(Request $request, int $staffId): View|RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) {
            return $redirect;
        }

        return view('admin.staff.edit', [
            'staffMember' => $this->engine->getStaffUser($staffId),
        ]);
    }

    public function update(Request $request, int $staffId): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) {
            return $redirect;
        }

        $validated = $request->validate([
            'name' => ['required', 'string'],
            'role' => ['required', 'in:STAFF,COMPLIANCE_ADMINISTRATOR'],
            'initials' => ['nullable', 'string'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string'],
            'mobile_phone' => ['nullable', 'string'],
            'job_title' => ['nullable', 'string'],
        ]);

        try {
            $this->engine->updateStaff($staffId, [
                'name' => $validated['name'],
                'role' => $validated['role'],
                'initials' => $validated['initials'] ?? null,
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'mobilePhone' => $validated['mobile_phone'] ?? null,
                'jobTitle' => $validated['job_title'] ?? null,
                'actingStaffId' => $request->user('staff')->id,
            ]);
        } catch (ComplianceEngineForbiddenException $e) {
            return back()->withInput()->with('status', 'Not permitted: ' . $e->getMessage());
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('status', 'Could not update staff account -- check the server log for the actual cause.');
        }

        return redirect()->route('admin.staff.index')->with('status', 'Staff account updated.');
    }
}
