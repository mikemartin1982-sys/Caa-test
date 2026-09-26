<?php

namespace Tests\Feature;

require_once __DIR__.'/../../vendor/autoload.php';

use App\Auth\StaffPrincipal;
use RuntimeException;
use App\Models\MailboxMessage;
use App\Models\MailboxMessageReply;
use App\Models\MailboxMessageTemplate;
use App\Services\GraphMailService;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MailboxTest extends TestCase
{
    public function createApplication()
    {
        $base = dirname(__DIR__, 2);
        $runtime = sys_get_temp_dir().'/caa-mailbox-tests-'.getmypid();
        foreach (['bootstrap/cache', 'storage/framework/views', 'storage/logs'] as $dir) {
            if (!is_dir("$runtime/$dir")) mkdir("$runtime/$dir", 0777, true);
        }
        $app = Application::configure(basePath: $base)
            ->withRouting(web: $base.'/routes/web.php')
            ->withMiddleware(fn ($middleware) => $middleware->redirectGuestsTo('/admin/login'))
            ->withExceptions(fn ($exceptions) => null)
            ->create();
        $app->useBootstrapPath($runtime.'/bootstrap');
        $app->useStoragePath($runtime.'/storage');
        $app->loadEnvironmentFrom('.env.mailbox-tests-does-not-exist');
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        $app['config']->set([
            'app.env' => 'testing', 'app.key' => 'base64:'.base64_encode(str_repeat('x', 32)),
            'database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'cache.default' => 'array', 'session.driver' => 'array', 'mailbox.enabled' => true,
            'view.compiled' => $runtime.'/storage/framework/views',
            'auth.guards.staff' => ['driver' => 'session', 'provider' => 'test-staff'],
            'auth.providers.test-staff' => ['driver' => 'database', 'table' => 'users'],
            'graph.tenant_id' => 'test', 'graph.client_id' => 'test',
            'graph.client_secret' => 'test', 'graph.mailbox' => 'test@example.com',
        ]);
        $app->detectEnvironment(fn () => 'testing');
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        foreach (glob(dirname(__DIR__, 2).'/database/migrations/2026_09_19_*.php') as $file) {
            (require $file)->up();
        }
        Http::preventStrayRequests();
    }

    private function staff(bool $admin = false): StaffPrincipal
    {
        return new StaffPrincipal(901, 'Test Staff', 'test', $admin ? 'COMPLIANCE_ADMINISTRATOR' : 'INSTRUCTOR');
    }

    private function message(): MailboxMessage
    {
        return MailboxMessage::create(['graph_message_id' => 'immutable-id', 'from_email' => 'client@example.com', 'body_text' => '<script>alert(1)</script>', 'received_at' => now(), 'status' => 'new']);
    }

    private function raw(string $id): array
    {
        return ['id' => $id, 'receivedDateTime' => '2026-09-19T12:00:00Z', 'body' => ['contentType' => 'text', 'content' => 'Hello'], 'isRead' => true];
    }

    public function test_sync_pages_already_read_mail_and_resumes_without_duplicates(): void
    {
        Http::fake([
            'login.microsoftonline.com/*' => Http::response(['access_token' => 'fake', 'expires_in' => 3600]),
            'graph.microsoft.com/*' => Http::sequence()
                ->push(['value' => [$this->raw('one')], '@odata.nextLink' => 'https://graph.microsoft.com/page2'])
                ->push(['value' => [$this->raw('two')], '@odata.deltaLink' => 'https://graph.microsoft.com/checkpoint'])
                ->push(['value' => [$this->raw('one'), ['id' => 'two', '@removed' => ['reason' => 'deleted']]], '@odata.deltaLink' => 'https://graph.microsoft.com/checkpoint2']),
        ]);
        $graph = app(GraphMailService::class);
        $this->assertCount(2, $graph->fetchNewMessages());
        MailboxMessage::where('graph_message_id', 'one')->update(['status' => 'closed']);
        $this->assertCount(0, $graph->fetchNewMessages());
        $this->assertSame(2, MailboxMessage::count());
        $this->assertSame('closed', MailboxMessage::where('graph_message_id', 'one')->value('status'));
        $this->assertSame('https://graph.microsoft.com/checkpoint2', DB::table('mailbox_sync_states')->value('cursor'));
        Http::assertSent(fn ($r) => $r->url() === 'https://graph.microsoft.com/checkpoint');
        Http::assertNotSent(fn ($r) => $r->method() === 'PATCH');
    }

    public function test_failed_sync_keeps_last_checkpoint(): void
    {
        Http::fake(['login.microsoftonline.com/*' => Http::response(['access_token' => 'fake']), 'graph.microsoft.com/*' => Http::response([], 503)]);
        $graph = app(GraphMailService::class);
        DB::table('mailbox_sync_states')->insert(['source_key' => $graph->sourceKey(), 'cursor' => 'https://graph.microsoft.com/checkpoint']);
        try { $graph->fetchNewMessages(); $this->fail('Expected failed import'); } catch (RuntimeException $e) {
            $this->assertStringContainsString('503', $e->getMessage());
        }
        $this->assertSame('https://graph.microsoft.com/checkpoint', DB::table('mailbox_sync_states')->value('cursor'));
        $this->assertSame(0, MailboxMessage::count());
    }

    public function test_staff_gate_and_safe_message_rendering(): void
    {
        $message = $this->message();
        $this->get('/admin/mailbox')->assertRedirect('/admin/login');
        $this->actingAs($this->staff(), 'web')->get('/admin/mailbox')->assertRedirect('/admin/login');
        $this->actingAs($this->staff(), 'staff')->get('/admin/mailbox/'.$message->id)
            ->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $this->get('/admin/mailbox/templates')->assertForbidden();
        $this->get('/admin/mailbox')->assertOk();
    }

    public function test_reply_is_submitted_once_and_records_external_staff_identity(): void
    {
        Http::fake(['login.microsoftonline.com/*' => Http::response(['access_token' => 'fake']), 'graph.microsoft.com/*' => Http::response('', 202)]);
        $message = $this->message();
        $data = ['body' => 'Reply text', 'request_id' => (string) Str::uuid()];
        $this->actingAs($this->staff(), 'staff');
        $this->post('/admin/mailbox/'.$message->id.'/reply', $data)->assertRedirect();
        $this->post('/admin/mailbox/'.$message->id.'/reply', $data)->assertSessionHasErrors('body');
        $reply = MailboxMessageReply::first();
        $this->assertSame(1, MailboxMessageReply::count());
        $this->assertSame(901, $reply->sent_by);
        $this->assertSame('accepted', $reply->delivery_status);
        $this->assertSame('replied', $message->fresh()->status);
        Http::assertSentCount(2); // One token and one send, despite two submissions.
    }

    public function test_uncertain_send_is_not_retried(): void
    {
        Http::fake(['login.microsoftonline.com/*' => Http::response(['access_token' => 'fake']), 'graph.microsoft.com/*' => Http::response([], 504)]);
        $message = $this->message();
        $data = ['body' => 'Reply text', 'request_id' => (string) Str::uuid()];
        $this->actingAs($this->staff(), 'staff');
        $this->post('/admin/mailbox/'.$message->id.'/reply', $data)->assertSessionHasErrors('body');
        $this->post('/admin/mailbox/'.$message->id.'/reply', $data)->assertSessionHasErrors('body');
        $this->assertSame('unknown', MailboxMessageReply::first()->delivery_status);
        $this->assertSame('new', $message->fresh()->status);
        Http::assertSentCount(2);
    }

    public function test_admin_can_deactivate_saved_reply(): void
    {
        $template = MailboxMessageTemplate::create(['name' => 'Welcome', 'body' => 'Hello', 'is_active' => true]);
        $this->actingAs($this->staff(true), 'staff')->put('/admin/mailbox/templates/'.$template->id, ['name' => 'Welcome', 'body' => 'Hello', 'is_active' => '0'])->assertRedirect();
        $this->assertFalse($template->fresh()->is_active);
        $this->get('/admin/mailbox/templates')->assertOk();
        $this->get('/admin/mailbox/'.$this->message()->id.'/templates/'.$template->id.'/preview')->assertNotFound();
    }

    public function test_dashboard_renders_mailbox_access_and_disabled_gate(): void
    {
        $this->actingAs($this->staff(), 'staff');
        $html = view('admin.dashboard', ['newMailboxCount' => 2, 'todaySessions' => [], 'tomorrowSessions' => []])->render();
        $this->assertStringContainsString('2 new messages', $html);
        $this->assertStringContainsString('/admin/mailbox', $html);
        config(['mailbox.enabled' => false]);
        $this->get('/admin/mailbox')->assertStatus(503);
    }

    public function test_partial_existing_delta_event_does_not_block_import(): void
    {
        $message = $this->message();
        Http::fake([
            'login.microsoftonline.com/*' => Http::response(['access_token' => 'fake']),
            'graph.microsoft.com/*' => Http::response(['value' => [['id' => $message->graph_message_id, 'isRead' => true]], '@odata.deltaLink' => 'https://graph.microsoft.com/checkpoint']),
        ]);
        $this->assertCount(0, app(GraphMailService::class)->fetchNewMessages());
        $this->assertSame('https://graph.microsoft.com/checkpoint', DB::table('mailbox_sync_states')->value('cursor'));
    }

    public function test_expired_checkpoint_is_reset_without_removing_saved_mail(): void
    {
        $this->message();
        $graph = app(GraphMailService::class);
        DB::table('mailbox_sync_states')->insert(['source_key' => $graph->sourceKey(), 'cursor' => 'https://graph.microsoft.com/expired']);
        Http::fake(['login.microsoftonline.com/*' => Http::response(['access_token' => 'fake']), 'graph.microsoft.com/*' => Http::response([], 410)]);
        try { $graph->fetchNewMessages(); $this->fail('Expected expiry failure'); } catch (RuntimeException $e) {
            $this->assertStringContainsString('expired', $e->getMessage());
        }
        $this->assertNull(DB::table('mailbox_sync_states')->value('cursor'));
        $this->assertSame(1, MailboxMessage::count());
    }

    public function test_new_partial_message_is_fetched_before_checkpoint_advances(): void
    {
        Http::fake([
            'login.microsoftonline.com/*' => Http::response(['access_token' => 'fake']),
            'graph.microsoft.com/*' => Http::sequence()
                ->push(['value' => [['id' => 'partial']], '@odata.deltaLink' => 'https://graph.microsoft.com/checkpoint'])
                ->push($this->raw('partial')),
        ]);
        $this->assertCount(1, app(GraphMailService::class)->fetchNewMessages());
        $this->assertSame('Hello', MailboxMessage::first()->body_text);
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/messages/partial'));
    }

    private function delegated(): void
    {
        config(['graph.auth_mode' => 'delegated', 'graph.redirect_uri' => 'https://portal.example.com/admin/mailbox/microsoft/callback']);
    }

    private function startConnection(): array
    {
        $this->actingAs($this->staff(true), 'staff');
        $response = $this->post('/admin/mailbox/microsoft/connect');
        $response->assertRedirect();
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $params);
        return $params;
    }

    private function oauthResponse(): array
    {
        return ['access_token' => 'private-access', 'refresh_token' => 'private-refresh', 'expires_in' => 3600,
            'scope' => 'Mail.Read.Shared Mail.Send.Shared'];
    }

    public function test_delegated_connection_requires_admin_and_rejects_invalid_state(): void
    {
        $this->delegated();
        $this->get('/admin/mailbox/microsoft')->assertRedirect('/admin/login');
        $this->actingAs($this->staff(), 'staff')->post('/admin/mailbox/microsoft/connect')->assertForbidden();
        $params = $this->startConnection();
        $this->assertSame('S256', $params['code_challenge_method']);
        $this->assertArrayHasKey('code_challenge', $params);
        $this->get('/admin/mailbox/microsoft/callback?state=wrong&code=fake')->assertStatus(419);
        Http::assertNothingSent();
    }

    public function test_delegated_connection_encrypts_tokens_and_checks_mailbox_without_sending(): void
    {
        $this->delegated();
        config(['mailbox.enabled' => false]);
        Http::fake(['login.microsoftonline.com/*' => Http::response($this->oauthResponse()), 'graph.microsoft.com/*' => Http::response(['value' => []])]);
        $params = $this->startConnection();
        $this->get('/admin/mailbox/microsoft/callback?'.http_build_query(['state' => $params['state'], 'code' => 'fake']))->assertRedirect(route('admin.mailbox.connection'));
        $row = DB::table('graph_oauth_tokens')->first();
        $this->assertNotSame('private-access', $row->access_token);
        $this->assertNotSame('private-refresh', $row->refresh_token);
        $this->assertSame('private-access', app(\App\Services\GraphTokenBroker::class)->accessToken());
        $this->assertSame(901, $row->authorized_by_staff_id);
        Http::assertSent(function ($r) use ($params) {
            if (!str_contains($r->url(), '/token')) return false;
            $challenge = rtrim(strtr(base64_encode(hash('sha256', $r['code_verifier'], true)), '+/', '-_'), '=');
            return $challenge === $params['code_challenge'];
        });
        Http::assertNotSent(fn ($r) => str_contains($r->url(), 'graph.microsoft.com') && $r->method() !== 'GET');
        $this->get('/admin/mailbox/microsoft')->assertOk()->assertSee('Reconnect Microsoft');
        $this->get('/admin/mailbox/microsoft/callback?'.http_build_query(['state' => $params['state'], 'code' => 'fake']))->assertStatus(419);
        Http::assertSentCount(2);
    }

    public function test_denied_mailbox_access_does_not_save_authorization(): void
    {
        $this->delegated();
        Http::fake(['login.microsoftonline.com/*' => Http::response($this->oauthResponse()), 'graph.microsoft.com/*' => Http::response([], 403)]);
        $params = $this->startConnection();
        $this->get('/admin/mailbox/microsoft/callback?'.http_build_query(['state' => $params['state'], 'code' => 'fake']))->assertSessionHasErrors('connection');
        $this->assertSame(0, DB::table('graph_oauth_tokens')->count());
    }

    public function test_refresh_rotates_tokens_and_revocation_requires_reconnect(): void
    {
        $this->delegated();
        $broker = app(\App\Services\GraphTokenBroker::class);
        $broker->store($this->oauthResponse(), 901);
        DB::table('graph_oauth_tokens')->update(['expires_at' => now()->subMinute()]);
        Http::fake(['login.microsoftonline.com/*' => Http::sequence()
            ->push(['access_token' => 'new-access', 'refresh_token' => 'new-refresh', 'expires_in' => 3600])
            ->push(['error' => 'invalid_grant'], 400)]);
        $this->assertSame('new-access', $broker->accessToken());
        $this->assertSame('new-refresh', \App\Models\GraphOauthToken::first()->refresh_token);
        $this->assertSame('new-access', $broker->accessToken());
        Http::assertSentCount(1);
        DB::table('graph_oauth_tokens')->update(['expires_at' => now()->subMinute()]);
        try { $broker->accessToken(); $this->fail('Expected reconnect requirement'); } catch (RuntimeException $e) {
            $this->assertFalse($broker->isConnected());
        }
    }

    public function test_delegated_import_reads_all_pages_and_repeats_without_duplicates(): void
    {
        $this->delegated();
        app(\App\Services\GraphTokenBroker::class)->store($this->oauthResponse(), 901);
        Http::fake(['graph.microsoft.com/*' => Http::sequence()
            ->push(['value' => [$this->raw('first')], '@odata.nextLink' => 'https://graph.microsoft.com/page2'])
            ->push(['value' => [$this->raw('second')]])
            ->push(['value' => [$this->raw('first'), $this->raw('second')]])]);
        $graph = app(GraphMailService::class);
        $this->assertCount(2, $graph->fetchNewMessages());
        $this->assertNull(DB::table('mailbox_sync_states')->value('cursor'));
        $this->assertCount(0, $graph->fetchNewMessages());
        Http::assertNotSent(fn ($r) => str_contains($r->url(), '/delta') || str_contains($r->url(), 'isRead'));
        $this->assertSame(2, MailboxMessage::count());
    }
}
