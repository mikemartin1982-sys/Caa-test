<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ComplianceEngine\ComplianceEngineClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Michael, 2026-09-27 -- self-service "Change Password" for the
 * logged-in staff member. Previously the only way to change a staff
 * password was the emailed Forgot Password flow, which needs an email
 * on file. The current password is verified by Java itself (see
 * ComplianceEngineClient::changeOwnStaffPassword()), never here.
 */
class StaffPasswordController extends Controller
{
    public function __construct(protected ComplianceEngineClient $engine)
    {
    }

    public function edit(): View
    {
        return view('admin.password.edit');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ], [
            'password.different' => 'Your new password must be different from your current password.',
        ]);

        $changed = $this->engine->changeOwnStaffPassword(
            auth('staff')->user()->username,
            $validated['current_password'],
            $validated['password'],
        );

        if (!$changed) {
            return back()->withErrors(['current_password' => 'Your current password is incorrect.']);
        }

        $request->session()->regenerate();

        return redirect()->route('admin.password.edit')->with('status', 'Your password has been changed.');
    }
}
