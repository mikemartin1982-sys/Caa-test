<?php

namespace App\Services;

use App\Models\GraphOauthToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class GraphTokenBroker
{
    public function connectionKey(): string
    {
        return hash('sha256', config('graph.tenant_id').'|'.config('graph.client_id').'|'.strtolower(config('graph.mailbox')));
    }

    public function configured(): bool
    {
        foreach (['tenant_id', 'client_id', 'client_secret', 'redirect_uri', 'mailbox'] as $key) {
            if (!is_string(config("graph.$key")) || config("graph.$key") === '') return false;
        }
        $uri = config('graph.redirect_uri');
        return parse_url($uri, PHP_URL_SCHEME) === 'https'
            || (parse_url($uri, PHP_URL_SCHEME) === 'http' && in_array(parse_url($uri, PHP_URL_HOST), ['localhost', '127.0.0.1'], true));
    }

    public function endpoint(string $action): string
    {
        if (!$this->configured()) throw new RuntimeException('Microsoft connection settings are incomplete.');
        return 'https://login.microsoftonline.com/'.rawurlencode(config('graph.tenant_id')).'/oauth2/v2.0/'.$action;
    }

    public function isConnected(): bool
    {
        return $this->storageReady() && GraphOauthToken::whereKey($this->connectionKey())->where('needs_reconnect', false)->exists();
    }

    public function storageReady(): bool
    {
        return Schema::hasTable('graph_oauth_tokens');
    }

    public function store(array $data, int $staffId, ?string $previousRefresh = null): void
    {
        $refresh = $data['refresh_token'] ?? $previousRefresh;
        if (empty($data['access_token']) || !is_string($data['access_token']) || !is_string($refresh) || $refresh === '' || !is_numeric($data['expires_in'] ?? null) || $data['expires_in'] <= 0) {
            throw new RuntimeException('Microsoft returned an incomplete authorization response.');
        }
        GraphOauthToken::updateOrCreate(['connection_key' => $this->connectionKey()], [
            'access_token' => $data['access_token'], 'refresh_token' => $refresh,
            'expires_at' => now()->addSeconds((int) $data['expires_in']),
            'authorized_by_staff_id' => $staffId, 'needs_reconnect' => false,
        ]);
    }

    public function accessToken(): string
    {
        // Serializes refresh and reauthorization, including refresh-token rotation.
        return Cache::lock('graph_oauth_'.$this->connectionKey(), 120)->block(5, function () {
            $token = GraphOauthToken::find($this->connectionKey());
            if (!$token || $token->needs_reconnect) throw new RuntimeException('A mailbox administrator must reconnect Microsoft.');
            if ($token->expires_at->isAfter(now()->addMinutes(2))) return $token->access_token;
            $response = Http::asForm()->timeout(30)->post($this->endpoint('token'), [
                'client_id' => config('graph.client_id'), 'client_secret' => config('graph.client_secret'),
                'grant_type' => 'refresh_token', 'refresh_token' => $token->refresh_token,
                'scope' => config('graph.delegated_scopes'),
            ]);
            if (!$response->successful()) {
                if (in_array($response->json('error'), ['invalid_grant', 'interaction_required', 'consent_required'], true)) {
                    $token->update(['needs_reconnect' => true]);
                }
                throw new RuntimeException('Microsoft authorization could not be refreshed. Check the connection page.');
            }
            $data = $response->json();
            $this->store($data, $token->authorized_by_staff_id, $token->refresh_token);
            return $data['access_token'];
        });
    }
}
