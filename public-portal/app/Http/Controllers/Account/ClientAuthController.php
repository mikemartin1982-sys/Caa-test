<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Section 3 / Michael, 2026-08-19: client login (Individual or
 * Organization), backed by the 'client' guard / ClientApiUserProvider --
 * credentials verified live against the Java API's GET /auth/me, never
 * against a local password table. Mirrors StaffAuthController exactly.
 *
 * Deliberately NOT the existing app/Http/Controllers/Portal/
 * DashboardController's world -- that one assumes a separate `users`
 * table with a client_id foreign key (Laravel's default auth
 * scaffolding, never finished/migrated). Confirmed with Michael,
 * 2026-08-19: DIBs only ever allows ONE login per Client Portal, so the
 * Client record itself being the account (this controller's model) is
 * the correct one going forward -- the old Portal\DashboardController
 * world is vestigial and should eventually be reconciled or removed,
 * not built on top of.
 */
class ClientAuthController extends Controller
{
    public function showLoginForm(): View
    {
        return view('account.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::guard('client')->attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->intended(route('account.dashboard'));
        }

        return back()
            ->withErrors(['email' => 'Invalid email or password.'])
            ->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('client')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('account.login');
    }
}
