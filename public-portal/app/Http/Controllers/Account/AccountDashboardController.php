<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Section 3 / Michael, 2026-08-19: a deliberately simple landing page
 * for a logged-in client -- confirms who they are and what they signed
 * up for. The full portal experience (Organization employee management,
 * viewing testing/certification status, etc.) is a real, separate
 * future build, not attempted here -- see the Individual/Organization
 * Registration Plan document for that scope. This exists so account
 * creation has a genuine, working end-to-end path tonight, not a dead
 * end after registering.
 */
class AccountDashboardController extends Controller
{
    public function index(Request $request): View
    {
        return view('account.dashboard', [
            'client' => $request->user(),
        ]);
    }
}
