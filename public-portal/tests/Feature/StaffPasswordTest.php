<?php

namespace Tests\Feature;

require_once __DIR__.'/../../vendor/autoload.php';

use App\Auth\StaffPrincipal;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

class StaffPasswordTest extends TestCase
{
    public function createApplication()
    {
        $base = dirname(__DIR__, 2);
        $runtime = sys_get_temp_dir().'/caa-staff-password-tests-'.getmypid();
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
        $app->loadEnvironmentFrom('.env.staff-password-tests-does-not-exist');
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        $app['config']->set([
            'app.env' => 'testing', 'app.key' => 'base64:'.base64_encode(str_repeat('x', 32)),
            'cache.default' => 'array', 'session.driver' => 'array',
            'view.compiled' => $runtime.'/storage/framework/views',
            'auth.guards.staff' => ['driver' => 'session', 'provider' => 'test-staff'],
            'auth.providers.test-staff' => ['driver' => 'database', 'table' => 'users'],
            'services.compliance_engine.base_url' => 'http://engine.test/api/v1',
            'services.compliance_engine.timeout' => 5,
        ]);
        $app->detectEnvironment(fn () => 'testing');
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->actingAs(new StaffPrincipal(901, 'Test Staff', 'tstaff', 'STAFF'), 'staff');
    }

    public function test_page_renders_for_logged_in_staff(): void
    {
        $this->get('/admin/password')->assertOk()->assertSee('Change Password')->assertSee('tstaff');
    }

    public function test_changes_password_using_staff_members_own_credentials(): void
    {
        Http::fake(['engine.test/api/v1/auth/me/password' => Http::response(['passwordChanged' => true])]);

        $this->put('/admin/password', [
            'current_password' => 'old-password-1',
            'password' => 'new-password-1',
            'password_confirmation' => 'new-password-1',
        ])->assertRedirect('/admin/password')->assertSessionHas('status', 'Your password has been changed.');

        Http::assertSent(fn (Request $request) =>
            $request->hasHeader('Authorization', 'Basic '.base64_encode('tstaff:old-password-1'))
            && $request['newPassword'] === 'new-password-1');
    }

    public function test_wrong_current_password_shows_error(): void
    {
        Http::fake(['engine.test/api/v1/auth/me/password' => Http::response(null, 401)]);

        $this->from('/admin/password')->put('/admin/password', [
            'current_password' => 'wrong-password',
            'password' => 'new-password-1',
            'password_confirmation' => 'new-password-1',
        ])->assertRedirect('/admin/password')
          ->assertSessionHasErrors(['current_password' => 'Your current password is incorrect.']);
    }

    public function test_validation_failures_never_reach_the_engine(): void
    {
        Http::fake();

        $this->from('/admin/password')->put('/admin/password', [
            'current_password' => 'old-password-1',
            'password' => 'new-password-1',
            'password_confirmation' => 'different-1',
        ])->assertSessionHasErrors('password');

        $this->from('/admin/password')->put('/admin/password', [
            'current_password' => 'same-password-1',
            'password' => 'same-password-1',
            'password_confirmation' => 'same-password-1',
        ])->assertSessionHasErrors('password');

        $this->from('/admin/password')->put('/admin/password', [
            'current_password' => 'old-password-1',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');

        Http::assertNothingSent();
    }
}
