<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ComplianceEngine\ComplianceEngineClient;
use App\Services\ComplianceEngine\ComplianceEngineConflictException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Section 3: staff login, backed by the 'staff' guard / StaffApiUserProvider
 * -- credentials are verified live against the Java API's GET /auth/me,
 * never against a local password table (see App\Auth\StaffApiUserProvider).
 *
 * Michael, 2026-08-31 -- Password Reset feature added here, not a new,
 * separate controller -- "log in" and "recover access to your login"
 * are closely related concerns, and this controller was already small.
 * Does no real logic of its own -- ComplianceEngineClient's own
 * requestStaffPasswordReset()/resetStaffPassword() already do the real
 * work (StaffPasswordResetService, Java-side); this is just the HTTP
 * entry points onto them.
 */
class StaffAuthController extends Controller
{
    public function __construct(private ComplianceEngineClient $engine)
    {
    }

    public function showLoginForm(): View
    {
        return view('admin.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::guard('staff')->attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->intended(route('admin.dashboard'));
        }

        return back()
            ->withErrors(['username' => 'Invalid staff credentials.'])
            ->onlyInput('username');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('staff')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }

    public function showForgotPasswordForm(): View
    {
        return view('admin.password-forgot');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string'],
        ]);

        $result = $this->engine->requestStaffPasswordReset($validated['username']);

        return back()->with('status', $result['message'] ?? 'If that username exists and has an email on file, a password reset link has been sent.');
    }

    public function showResetForm(Request $request): View
    {
        return view('admin.password-reset', ['token' => $request->query('token', '')]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            $this->engine->resetStaffPassword($validated['token'], $validated['password']);
        } catch (ComplianceEngineConflictException $e) {
            return back()->withErrors(['password' => $e->getMessage()]);
        }

        return redirect()->route('admin.login')->with('status', 'Your password has been reset. You can log in now.');
    }
}
