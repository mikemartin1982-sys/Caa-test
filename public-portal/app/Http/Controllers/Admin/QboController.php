<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ComplianceEngine\ComplianceEngineClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Michael, 2026-08-25 -- QBO integration, layer 3: the actual "Connect
 * to QuickBooks" flow, replacing the manual OAuth Playground steps
 * used to prove the chain works with our own, real endpoints.
 *
 * The exchange itself happens here (Laravel), matching every other
 * browser-facing route in this project -- the Java side
 * (QboConnectionController) only ever receives the finished token set
 * to store, never talks to Intuit directly. Same
 * Compliance-Administrator-only gate as Staff Management
 * (StaffManagementController's own requireAdmin() pattern), since
 * this is a company-wide connection, not something any staff member
 * should be able to reconnect or disconnect.
 */
class QboController extends Controller
{
    public function __construct(private ComplianceEngineClient $engine)
    {
    }

    private function requireAdmin(Request $request): ?RedirectResponse
    {
        if (!$request->user('staff')->isComplianceAdministrator()) {
            return redirect()->route('admin.dashboard')
                ->with('status', 'Only Compliance Administrators can manage the QuickBooks connection.');
        }
        return null;
    }

    public function status(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) {
            return $redirect;
        }

        return view('admin.qbo.status', [
            'connection' => $this->engine->getQboConnectionStatus(),
        ]);
    }

    /**
     * Michael, 2026-08-25 -- kicks off the actual OAuth redirect. state
     * is a random, one-time value stored in the session and re-checked
     * on callback() -- standard OAuth CSRF protection, so a malicious
     * page can't trick an already-logged-in admin into completing a
     * QuickBooks connection they never actually initiated themselves.
     */
    public function connect(Request $request): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) {
            return $redirect;
        }

        $state = Str::random(40);
        $request->session()->put('qbo_oauth_state', $state);

        $query = http_build_query([
            'client_id' => config('services.qbo.client_id'),
            'redirect_uri' => config('services.qbo.redirect_uri'),
            'response_type' => 'code',
            'scope' => 'com.intuit.quickbooks.accounting',
            'state' => $state,
        ]);

        return redirect(config('services.qbo.authorize_url') . '?' . $query);
    }

    /**
     * Michael, 2026-08-25 -- Intuit redirects the browser back here
     * with a real authorization code and realmId after the admin
     * grants consent on Intuit's own hosted page. Exchanges that code
     * server-to-server for the actual access/refresh tokens, then
     * hands the result to the Java side to store (encrypted -- see
     * QboConnectionController on that side).
     */
    public function callback(Request $request): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) {
            return $redirect;
        }

        $expectedState = $request->session()->pull('qbo_oauth_state');
        if (!$expectedState || $request->query('state') !== $expectedState) {
            return redirect()->route('admin.qbo.status')
                ->with('status', 'Could not connect: the connection request could not be verified. Please try again.');
        }

        $code = $request->query('code');
        $realmId = $request->query('realmId');
        if (!$code || !$realmId) {
            return redirect()->route('admin.qbo.status')
                ->with('status', 'Could not connect: QuickBooks did not return the expected authorization details.');
        }

        $tokenResponse = Http::asForm()
            ->withBasicAuth(config('services.qbo.client_id'), config('services.qbo.client_secret'))
            ->withHeaders(['Accept' => 'application/json'])
            ->post(config('services.qbo.token_url'), [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => config('services.qbo.redirect_uri'),
            ]);

        if ($tokenResponse->failed()) {
            return redirect()->route('admin.qbo.status')
                ->with('status', 'Could not connect: QuickBooks rejected the token exchange (' . $tokenResponse->status() . ').');
        }

        $tokens = $tokenResponse->json();

        try {
            $this->engine->storeQboConnection([
                'realmId' => $realmId,
                'accessToken' => $tokens['access_token'] ?? null,
                'refreshToken' => $tokens['refresh_token'] ?? null,
                'expiresInSeconds' => $tokens['expires_in'] ?? null,
                'refreshTokenExpiresInSeconds' => $tokens['x_refresh_token_expires_in'] ?? null,
                'environment' => config('services.qbo.environment'),
                'connectedByStaffId' => $request->user('staff')->id,
            ]);
        } catch (\RuntimeException $e) {
            return redirect()->route('admin.qbo.status')
                ->with('status', 'Connected to QuickBooks, but saving the connection failed -- check the server log.');
        }

        return redirect()->route('admin.qbo.status')->with('status', 'Connected to QuickBooks successfully.');
    }

    public function disconnect(Request $request): RedirectResponse
    {
        if ($redirect = $this->requireAdmin($request)) {
            return $redirect;
        }

        $this->engine->disconnectQbo();

        return redirect()->route('admin.qbo.status')->with('status', 'Disconnected from QuickBooks.');
    }
}
