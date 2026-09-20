<?php

namespace App\Http\Controllers;

use App\Services\GraphTokenBroker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GraphAuthController extends Controller
{
    private function authorizeStaff(Request $request): void
    {
        abort_unless($request->user('staff')?->isComplianceAdministrator(), 403);
    }

    public function show(Request $request, GraphTokenBroker $broker)
    {
        $this->authorizeStaff($request);
        return view('mailbox.connection', [
            'configured' => $broker->configured(),
            'connected' => $broker->isConnected(),
            'storageReady' => $broker->storageReady(),
        ]);
    }

    public function connect(Request $request, GraphTokenBroker $broker)
    {
        $this->authorizeStaff($request);
        if (config('graph.auth_mode') !== 'delegated' || !$broker->configured() || !$broker->storageReady()) {
            return back()->withErrors(['connection' => 'Microsoft delegated connection settings are not ready.']);
        }
        $state = Str::random(64);
        $verifier = Str::random(96);
        $request->session()->put('graph_oauth', [
            'state' => $state, 'verifier' => $verifier, 'created_at' => time(),
            'staff_id' => $request->user('staff')->getAuthIdentifier(),
            'connection_key' => $broker->connectionKey(),
        ]);
        return redirect()->away($broker->endpoint('authorize').'?'.http_build_query([
            'client_id' => config('graph.client_id'), 'response_type' => 'code',
            'redirect_uri' => config('graph.redirect_uri'), 'response_mode' => 'query',
            'scope' => config('graph.delegated_scopes'), 'state' => $state,
            'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '='),
            'code_challenge_method' => 'S256', 'prompt' => 'select_account',
        ]));
    }

    public function callback(Request $request, GraphTokenBroker $broker)
    {
        $this->authorizeStaff($request);
        $pending = $request->session()->pull('graph_oauth');
        $state = $request->query('state');
        abort_unless(is_array($pending) && is_string($state)
            && hash_equals($pending['state'], $state)
            && time() - $pending['created_at'] <= 600
            && $pending['staff_id'] === $request->user('staff')->getAuthIdentifier()
            && $pending['connection_key'] === $broker->connectionKey(), 419, 'Microsoft sign-in expired or could not be verified. Start again.');
        if ($request->query('error')) {
            return redirect()->route('admin.mailbox.connection')->withErrors(['connection' => 'Microsoft sign-in was cancelled or consent was denied. Your organization may require administrator approval.']);
        }
        $code = $request->query('code');
        if (config('graph.auth_mode') !== 'delegated' || !$broker->configured() || !is_string($code) || $code === '') {
            return redirect()->route('admin.mailbox.connection')->withErrors(['connection' => 'Microsoft sign-in could not be completed. Start again.']);
        }
        try {
            Cache::lock('graph_oauth_'.$broker->connectionKey(), 120)->block(5, function () use ($broker, $code, $pending, $request) {
                $response = Http::asForm()->timeout(30)->post($broker->endpoint('token'), [
                    'client_id' => config('graph.client_id'), 'client_secret' => config('graph.client_secret'),
                    'grant_type' => 'authorization_code', 'code' => $code,
                    'redirect_uri' => config('graph.redirect_uri'), 'code_verifier' => $pending['verifier'],
                    'scope' => config('graph.delegated_scopes'),
                ]);
                if (!$response->successful()) throw new \RuntimeException('Token exchange failed.');
                $data = $response->json();
                $scopes = array_map(fn ($s) => strtolower(str_replace('https://graph.microsoft.com/', '', $s)), explode(' ', $data['scope'] ?? ''));
                if (!in_array('mail.send.shared', $scopes, true) || (!in_array('mail.read.shared', $scopes, true) && !in_array('mail.readwrite.shared', $scopes, true))) {
                    throw new \RuntimeException('Required permissions were not granted.');
                }
                if (empty($data['access_token'])) throw new \RuntimeException('No access token.');
                // Read-only preflight: verify access to the configured mailbox,
                // never send an email just to establish a connection.
                $probe = Http::withToken($data['access_token'])->timeout(30)->get(
                    'https://graph.microsoft.com/v1.0/users/'.rawurlencode(config('graph.mailbox')).'/mailFolders/'.rawurlencode(config('graph.inbox_folder')).'/messages',
                    ['$top' => 1, '$select' => 'id']
                );
                if (!$probe->successful()) throw new \RuntimeException('Mailbox access failed.');
                $broker->store($data, $request->user('staff')->getAuthIdentifier());
            });
        } catch (\Throwable $e) {
            // OAuth responses and transport errors can contain credentials.
            return redirect()->route('admin.mailbox.connection')->withErrors(['connection' => 'Microsoft connection failed. Check app settings, consent, and your access to the shared mailbox, then try again.']);
        }
        return redirect()->route('admin.mailbox.connection')->with('status', 'Microsoft connected and mailbox read access verified. Send As permission still needs a test reply.');
    }
}
