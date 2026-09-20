<?php

namespace App\Services;

use App\Models\MailboxMessage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GraphMailService
{
    protected function client()
    {
        foreach (['tenant_id', 'client_id', 'client_secret', 'mailbox'] as $key) {
            if (!config("graph.$key")) {
                throw new RuntimeException('Microsoft mailbox configuration is incomplete.');
            }
        }
        if (config('graph.auth_mode') === 'delegated') {
            $token = app(GraphTokenBroker::class)->accessToken();
            return Http::withToken($token)->acceptJson()->timeout(30)
                ->withHeaders(['Prefer' => 'IdType="ImmutableId", outlook.body-content-type="text"']);
        }
        if (config('graph.auth_mode') !== 'application') {
            throw new RuntimeException('Unsupported Microsoft authentication mode.');
        }
        $key = 'graph_token_'.hash('sha256', config('graph.tenant_id').config('graph.client_id').config('graph.client_secret'));
        $token = Cache::get($key);
        if (!$token) {
            $response = Http::asForm()->timeout(30)->post('https://login.microsoftonline.com/'.rawurlencode(config('graph.tenant_id')).'/oauth2/v2.0/token', [
                'client_id' => config('graph.client_id'),
                'client_secret' => config('graph.client_secret'),
                'scope' => 'https://graph.microsoft.com/.default',
                'grant_type' => 'client_credentials',
            ]);
            if (!$response->successful() || !is_string($response->json('access_token'))) {
                throw new RuntimeException('Microsoft mailbox authentication failed.');
            }
            $token = $response->json('access_token');
            Cache::put($key, $token, max(1, (int) $response->json('expires_in', 3600) - 120));
        }
        return Http::withToken($token)->acceptJson()->timeout(30)
            ->withHeaders(['Prefer' => 'IdType="ImmutableId", outlook.body-content-type="text"']);
    }

    public function sourceKey(): string
    {
        return hash('sha256', config('graph.auth_mode').'|'.config('graph.tenant_id').'|'.strtolower(config('graph.mailbox')).'|'.config('graph.inbox_folder'));
    }

    protected function mailboxUrl(): string
    {
        return 'https://graph.microsoft.com/v1.0/users/'.rawurlencode(config('graph.mailbox'));
    }

    /** Incremental inbox sync, independent of Outlook read/unread status. */
    public function fetchNewMessages(): array
    {
        $lock = Cache::lock('mailbox_sync_'.$this->sourceKey(), 3600);
        if (!$lock->get()) {
            throw new RuntimeException('A mailbox import is already running.');
        }
        try {
            $key = $this->sourceKey();
            $delegated = config('graph.auth_mode') === 'delegated';
            $initialUrl = $this->mailboxUrl().'/mailFolders/'.rawurlencode(config('graph.inbox_folder')).'/messages'.($delegated ? '' : '/delta').'?$select=id,conversationId,subject,from,receivedDateTime,bodyPreview,body';
            if ($delegated) $initialUrl .= '&$top=100&$orderby=receivedDateTime desc';
            $url = DB::table('mailbox_sync_states')->where('source_key', $key)->value('cursor')
                ?: $initialUrl;
            $created = [];
            // Save each complete page. A large initial import resumes on the next run.
            for ($page = 0; $page < 50; $page++) {
                if (parse_url($url, PHP_URL_SCHEME) !== 'https' || parse_url($url, PHP_URL_HOST) !== 'graph.microsoft.com') {
                    throw new RuntimeException('Invalid Microsoft synchronization cursor.');
                }
                $response = $this->client()->get($url);
                if ($response->status() === 410) {
                    DB::table('mailbox_sync_states')->where('source_key', $key)->update(['cursor' => null]);
                    throw new RuntimeException('Microsoft synchronization checkpoint expired; the next import will rebuild it without duplicating saved messages.');
                }
                if (!$response->successful()) {
                    throw new RuntimeException('Microsoft mailbox import failed (HTTP '.$response->status().').');
                }
                $data = $response->json();
                $cursor = $data['@odata.nextLink'] ?? $data['@odata.deltaLink'] ?? null;
                if ((!$delegated && !is_string($cursor)) || ($cursor !== null && !is_string($cursor)) || !is_array($data['value'] ?? null)) {
                    throw new RuntimeException('Microsoft returned an incomplete synchronization response.');
                }
                // Delta events can contain only changed fields. Resolve a new
                // partial message before committing this page's checkpoint.
                foreach ($data['value'] as &$raw) {
                    if (isset($raw['@removed']) || empty($raw['id']) || MailboxMessage::where('graph_message_id', $raw['id'])->exists()) {
                        continue;
                    }
                    if (!isset($raw['receivedDateTime'], $raw['body'])) {
                        $detail = $this->client()->get($this->mailboxUrl().'/messages/'.rawurlencode($raw['id']));
                        if (!$detail->successful()) {
                            throw new RuntimeException('Unable to retrieve a changed mailbox message.');
                        }
                        $raw = $detail->json();
                    }
                }
                unset($raw);
                DB::transaction(function () use ($data, $key, $cursor, &$created) {
                    foreach ($data['value'] as $raw) {
                        if (isset($raw['@removed'])) {
                            continue; // Preserve CRM records when mail is moved or deleted.
                        }
                        if (!empty($raw['id']) && MailboxMessage::where('graph_message_id', $raw['id'])->exists()) {
                            continue;
                        }
                        if (empty($raw['id']) || empty($raw['receivedDateTime'])) {
                            throw new RuntimeException('Microsoft returned an incomplete message.');
                        }
                        $body = $raw['body']['content'] ?? $raw['bodyPreview'] ?? '';
                        if (strtolower($raw['body']['contentType'] ?? 'text') === 'html') {
                            $body = html_entity_decode(strip_tags($body), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        }
                        $message = MailboxMessage::firstOrCreate(['graph_message_id' => $raw['id']], [
                            'graph_conversation_id' => $raw['conversationId'] ?? null,
                            'from_name' => $raw['from']['emailAddress']['name'] ?? null,
                            'from_email' => $raw['from']['emailAddress']['address'] ?? 'unknown@unknown',
                            'subject' => $raw['subject'] ?? null,
                            'body_text' => $body,
                            'received_at' => $raw['receivedDateTime'],
                            'status' => 'new',
                        ]);
                        if ($message->wasRecentlyCreated) {
                            $created[] = $message;
                        }
                    }
                    DB::table('mailbox_sync_states')->updateOrInsert(['source_key' => $key], ['cursor' => $cursor, 'synced_at' => now()]);
                });
                if (!isset($data['@odata.nextLink'])) {
                    break;
                }
                $url = $cursor;
            }
            return $created;
        } finally {
            $lock->release();
        }
    }

    /** Do not automatically retry sends: a timeout may follow acceptance. */
    public function sendReply(MailboxMessage $message, string $body): void
    {
        $response = $this->client()->post($this->mailboxUrl().'/messages/'.rawurlencode($message->graph_message_id).'/reply', [
            'message' => ['body' => ['contentType' => 'Text', 'content' => $body]],
        ]);
        if ($response->status() !== 202) {
            throw new RuntimeException('Microsoft did not confirm acceptance of the reply.');
        }
    }
}
