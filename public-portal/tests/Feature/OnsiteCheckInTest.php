<?php

namespace Tests\Feature;

require_once __DIR__.'/../../vendor/autoload.php';

use App\Http\Controllers\OnsiteController;
use App\Services\ComplianceEngine\ComplianceEngineClient;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OnsiteCheckInTest extends TestCase
{
    public function createApplication()
    {
        $base = dirname(__DIR__, 2);
        $runtime = sys_get_temp_dir().'/caa-onsite-tests-'.getmypid();
        foreach (['bootstrap/cache', 'storage/framework/views', 'storage/logs'] as $dir) {
            if (!is_dir("$runtime/$dir")) mkdir("$runtime/$dir", 0777, true);
        }

        $app = Application::configure(basePath: $base)
            ->withRouting()
            ->withExceptions(fn ($exceptions) => null)
            ->create();
        $app->useBootstrapPath($runtime.'/bootstrap');
        $app->useStoragePath($runtime.'/storage');
        $app->loadEnvironmentFrom('.env.onsite-tests-does-not-exist');
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        $app['config']->set([
            'app.env' => 'testing',
            'app.key' => 'base64:'.base64_encode(str_repeat('x', 32)),
            'session.driver' => 'array',
            'view.compiled' => $runtime.'/storage/framework/views',
        ]);
        $app->detectEnvironment(fn () => 'testing');

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->app['router']->get('/onsite/{sessionId}', fn () => 'roster')->name('onsite.show');
        $this->app['router']->get('/onsite/{sessionId}/waiting', fn () => 'waiting')->name('onsite.waiting');
        $this->app['router']->getRoutes()->refreshNameLookups();
    }

    public function test_successful_check_in_marks_blank_enrollment_arrived(): void
    {
        $engine = new FakeOnsiteComplianceEngineClient(null);
        $result = $this->checkIn($engine);

        $this->assertTrue($result->isRedirect(route('onsite.waiting', ['sessionId' => 14, 'enrollmentId' => 126])));
        $this->assertSame([[126, 'ARR']], $engine->statusUpdates);
    }

    public function test_repeated_check_in_is_idempotent(): void
    {
        $engine = new FakeOnsiteComplianceEngineClient('ARR');
        $result = $this->checkIn($engine);

        $this->assertTrue($result->isRedirect(route('onsite.waiting', ['sessionId' => 14, 'enrollmentId' => 126])));
        $this->assertSame([], $engine->statusUpdates);
    }

    public function test_terminal_enrollment_cannot_check_in(): void
    {
        $engine = new FakeOnsiteComplianceEngineClient('DNC');
        $result = $this->checkIn($engine);

        $this->assertTrue($result->isRedirect(route('onsite.show', ['sessionId' => 14])));
        $this->assertSame([], $engine->statusUpdates);
    }

    public function test_enrollment_from_another_session_cannot_check_in(): void
    {
        $engine = new FakeOnsiteComplianceEngineClient(null, []);
        $result = $this->checkIn($engine);

        $this->assertTrue($result->isRedirect(route('onsite.show', ['sessionId' => 14])));
        $this->assertSame([], $engine->statusUpdates);
    }

    private function checkIn(FakeOnsiteComplianceEngineClient $engine): View|\Illuminate\Http\RedirectResponse
    {
        $request = Request::create('/onsite/14/check-in', 'POST', ['enrollment_id' => 126]);
        $request->setLaravelSession($this->app['session.store']);

        return (new OnsiteController($engine))->checkIn($request, 14);
    }
}

class FakeOnsiteComplianceEngineClient extends ComplianceEngineClient
{
    public array $statusUpdates = [];

    public function __construct(
        private readonly ?string $rosterStatus,
        private readonly ?array $roster = null,
    ) {
    }

    public function getSession(int $sessionId): array
    {
        return ['id' => $sessionId, 'onsiteStage' => 'SIGN_IN'];
    }

    public function getRoster(int $sessionId): array
    {
        return $this->roster ?? [[
            'enrollmentId' => 126,
            'studentName' => 'Test Student',
            'rosterStatus' => $this->rosterStatus,
        ]];
    }

    public function updateRosterStatus(int $enrollmentId, string $rosterStatus): array
    {
        $this->statusUpdates[] = [$enrollmentId, $rosterStatus];
        return ['id' => $enrollmentId, 'rosterStatus' => $rosterStatus];
    }
}
