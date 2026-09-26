<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Services\ComplianceEngine\ComplianceEngineClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Section 3 / Michael, 2026-08-19: originally a deliberately simple
 * landing page -- confirms who they are and what they signed up for.
 *
 * Michael, 2026-08-24 -- restructured into separate pages (Home /
 * Manage Employees / Enroll / Edit Acct Info), matching the real,
 * existing client portal's own familiar shape (Michael provided its
 * actual source) rather than the single combined dashboard this was
 * first built as -- clients already know this nav pattern and
 * shouldn't have to relearn it. Each page below used to be one
 * section of that combined dashboard; the underlying logic (including
 * the reconciliation from the orphaned Portal\ namespace -- see
 * ClientAuthController's own docblock -- and every ownership
 * safeguard already built) is unchanged, only split across real,
 * separately-linked pages now.
 */
class AccountDashboardController extends Controller
{
    public function __construct(protected ComplianceEngineClient $engine)
    {
    }

    /** "Client Home" -- a minimal welcome/overview page, matching the real portal's own Home page. */
    public function index(Request $request): View
    {
        return view('account.dashboard', [
            'client' => $request->user(),
        ]);
    }

    /** "Manage Employees" -- its own page now, not a section of Home. */
    public function employees(Request $request): View
    {
        $client = $request->user();

        return view('account.employees', [
            'client' => $client,
            'employees' => $this->engine->listClientEmployees($client->id),
        ]);
    }

    /** "Edit Acct Info" -- its own page now, not a section of Home. */
    public function edit(Request $request): View
    {
        $client = $request->user();

        return view('account.edit', [
            'client' => $client,
            // Michael, 2026-08-24 -- $client (ClientPrincipal) is
            // deliberately minimal, just enough for auth identity
            // (id/name/email/clientType). This edit form needs the
            // full record (company, address, phone, etc.) to pre-fill
            // correctly, so it's fetched separately here, same call
            // the admin side already uses.
            'clientRecord' => $this->engine->getClient($client->id),
        ]);
    }

    /**
     * Michael, 2026-08-24 -- "manage their own client information."
     * Deliberately takes NO client id from the request at all --
     * always acts on $request->user()->id, the authenticated
     * session's own identity. This is a structural safeguard, not
     * just a check: it's not merely that a client is blocked from
     * editing someone else's record, it's that there is no code path
     * here that could even accept a different id to act on, unlike
     * the admin-side equivalent (ClientManagementController::update()),
     * which legitimately takes any client id since staff can edit
     * anyone.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company' => ['nullable', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:2'],
            'zip' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
        ]);

        $this->engine->updateClient($request->user()->id, [
            'company' => $validated['company'] ?? null,
            'firstName' => $validated['first_name'] ?? null,
            'lastName' => $validated['last_name'] ?? null,
            'address' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
            'state' => $validated['state'] ?? null,
            'zip' => $validated['zip'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
        ]);

        return redirect()->route('account.edit')->with('status', 'Your information has been updated.');
    }

    /**
     * Michael, 2026-08-24 -- "add to" (employee management). Same
     * structural safeguard as update() above -- the client id passed
     * to addClientEmployee() is always $request->user()->id, never
     * taken from the request. create() on the Java side always
     * assigns the new student to whatever client id it's given, so
     * this is safe by construction: there's no id in this request a
     * client could substitute to create an employee under someone
     * else's account.
     */
    public function storeEmployee(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        $this->engine->addClientEmployee(
            $request->user()->id,
            $validated['name'],
            $validated['phone'] ?? '',
            $validated['email'],
        );

        return redirect()->route('account.employees')->with('status', 'Employee added.');
    }

    /**
     * Michael, 2026-08-24 -- "mark inactive." $student comes from the
     * URL (unavoidable -- we need to know which employee), but unlike
     * update() above there IS a real id in this request a client
     * could tamper with. Two layers of protection here: this method
     * only ever passes $request->user()->id as the CLIENT id (never
     * trusting anything from the request for that part), AND the Java
     * side now independently verifies that student actually belongs
     * to that client before allowing the change (see
     * StudentController.update()'s own comment on this exact gap,
     * found and fixed while building this feature) -- so even a
     * tampered student id in the URL is rejected server-side, not
     * just hidden by the UI.
     */
    public function updateEmployee(Request $request, int $student): RedirectResponse
    {
        $validated = $request->validate([
            'active' => ['nullable'],
        ]);

        try {
            $this->engine->updateStudent($request->user()->id, $student, [
                'active' => $request->boolean('active'),
            ]);
        } catch (\App\Services\ComplianceEngine\ComplianceEngineConflictException $e) {
            return back()->with('status', 'Could not update that employee.');
        }

        return redirect()->route('account.employees')->with('status', 'Employee updated.');
    }
}
