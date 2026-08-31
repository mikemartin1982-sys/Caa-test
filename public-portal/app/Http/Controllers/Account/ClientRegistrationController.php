<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Services\ComplianceEngine\ComplianceEngineClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Section 3 / Michael, 2026-08-19: public, self-serve account creation --
 * general prospective-client registration, independent of which testing
 * path (VR or traditional staff-scheduled smoke school) they end up on.
 * Both Individual and Organization get a real login-capable account at
 * creation, and pick their testing-path preference right at signup.
 */
class ClientRegistrationController extends Controller
{
    public function __construct(protected ComplianceEngineClient $engine)
    {
    }

    public function showRegistrationForm(): View
    {
        return view('account.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_type' => ['required', 'in:INDIVIDUAL,ORGANIZATION'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'company' => ['required_if:client_type,ORGANIZATION', 'nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'testing_path' => ['required', 'in:VR,TRADITIONAL'],
        ]);

        try {
            $this->engine->registerClient([
                'clientType' => $validated['client_type'],
                'firstName' => $validated['first_name'],
                'lastName' => $validated['last_name'],
                'company' => $validated['company'] ?? null,
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => $validated['password'],
                'vrPreferred' => $validated['testing_path'] === 'VR',
            ]);
        } catch (\App\Services\ComplianceEngine\ComplianceEngineConflictException $e) {
            // e.g. "An account with this email already exists" -- see
            // ClientController.register()'s 409 case on the Java side.
            return back()
                ->withErrors(['email' => $e->getMessage()])
                ->onlyInput('client_type', 'first_name', 'last_name', 'company', 'email', 'phone', 'testing_path');
        }

        // Auto-login on successful registration -- a fresh account with
        // no existing session to preserve, so there's no meaningful
        // "intended" page to redirect back to; straight to the
        // credentials just set is the natural next step.
        Auth::guard('client')->attempt([
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);
        $request->session()->regenerate();

        return redirect()->route('account.dashboard');
    }
}
