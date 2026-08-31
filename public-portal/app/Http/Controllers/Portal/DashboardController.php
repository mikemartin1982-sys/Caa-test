<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\ComplianceEngine\ComplianceEngineClient;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Section 4e: authenticated Client Portal. Client-only access -- staff do
 * not use this portal for their own work (the test account Michael and
 * Rebecca use behaves as an ordinary client account, not a special mode).
 */
class DashboardController extends Controller
{
    public function __construct(protected ComplianceEngineClient $engine)
    {
    }

    public function index(Request $request): View
    {
        $clientId = $request->user()->client_id;

        return view('portal.dashboard', [
            'client' => $this->engine->searchClients((string) $clientId)[0] ?? null,
            'employees' => $this->engine->listClientEmployees($clientId),
        ]);
    }
}
