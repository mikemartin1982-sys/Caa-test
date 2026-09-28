<?php

namespace Tests\Feature;

require_once __DIR__.'/../../vendor/autoload.php';

use App\Http\Controllers\PublicCalendarController;
use App\Services\ComplianceEngine\ComplianceEngineClient;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase;

class PublicCalendarTest extends TestCase
{
    public function createApplication()
    {
        $base = dirname(__DIR__, 2);
        $runtime = sys_get_temp_dir().'/caa-public-calendar-tests-'.getmypid();
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
        $app->loadEnvironmentFrom('.env.public-calendar-tests-does-not-exist');
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        $app['config']->set([
            'app.env' => 'testing', 'app.key' => 'base64:'.base64_encode(str_repeat('x', 32)),
            'cache.default' => 'array', 'session.driver' => 'array',
            'view.compiled' => $runtime.'/storage/framework/views',
        ]);
        $app->detectEnvironment(fn () => 'testing');
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-27 10:00', 'America/Chicago'));
        $this->app->instance(ComplianceEngineClient::class, new class extends ComplianceEngineClient {
            public function __construct() {}
            public function listSessions(array $filters = []): array
            {
                return [
                    ['id' => 1, 'addressCity' => 'Reno', 'addressState' => 'NV'],
                    ['id' => 2, 'addressCity' => 'Decatur', 'addressState' => 'AL'],
                    ['id' => 3, 'addressCity' => 'Canceled Town', 'addressState' => 'TX', 'canceled' => true],
                    ['id' => 4, 'addressCity' => 'August Start', 'addressState' => 'TX'],
                    ['id' => 5, 'addressCity' => 'Online Lecture', 'addressState' => 'AL', 'format' => 'ONLINE'],
                ];
            }
            public function getSessionDateRanges(array $sessionIds): array
            {
                $ranges = [
                    1 => ['2026-10-06', '2026-10-06'],
                    2 => ['2026-09-30', '2026-10-01'],
                    3 => ['2026-10-10', '2026-10-10'],
                    4 => ['2026-08-01', '2026-08-02'],
                    5 => ['2037-12-30', '2037-12-30'],
                ];
                return collect($sessionIds)->filter(fn ($id) => isset($ranges[$id]))
                    ->map(fn ($id) => ['sessionId' => $id, 'firstDate' => $ranges[$id][0], 'lastDate' => $ranges[$id][1]])
                    ->values()->all();
            }
            public function getSession(int $sessionId): array
            {
                return match ($sessionId) {
                    8507 => ['id' => 8507, 'schoolType' => 'PUBLIC', 'published' => true, 'locationName' => 'Reno Training Site', 'addressCity' => 'Reno', 'addressState' => 'NV'],
                    8577 => ['id' => 8577, 'schoolType' => 'SEMI_PRIVATE', 'published' => true, 'addressState' => 'TX'],
                    default => throw new \RuntimeException('Compliance Engine call failed [404]: '),
                };
            }
            public function getSessionCalendar(string $startDate, string $endDate, ?string $schoolType = null): array
            {
                $e = fn ($id, $date, $primary = true, $published = true, $canceled = false) =>
                    ['id' => $id, 'date' => $date, 'isPrimaryDay' => $primary, 'published' => $published, 'canceled' => $canceled];
                return ['entries' => [
                    $e(1, '2026-09-01'),
                    $e(2, '2026-09-01'), $e(2, '2026-09-02', false),
                    $e(3, '2026-09-08', true, true, true),
                    $e(99, '2026-09-09', true, false),
                    $e(4, '2026-09-01', false),
                ]];
            }
        });
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_shows_each_public_school_once_on_its_first_day(): void
    {
        $html = $this->get('/calendar')->assertOk()->assertSee('September 2026')->getContent();

        $this->assertSame(2, substr_count($html, 'Decatur, AL'), 'once in the grid, once in the phone list');
        $this->assertStringContainsString('Reno, NV', $html);
        $this->assertStringNotContainsString('Canceled Town', $html);
        $this->assertStringNotContainsString('August Start', $html);
        $this->assertStringContainsString('Holiday: Labor Day', $html);
    }

    public function test_cannot_browse_before_the_current_month(): void
    {
        $this->get('/calendar?month=2020-01')->assertOk()->assertSee('September 2026')
            ->assertSee('<span aria-disabled="true">&lt; Previous Month</span>', false);
        $this->get('/calendar?month=not-a-month')->assertOk()->assertSee('September 2026');
        $this->get('/calendar?month=2026-10')->assertOk()->assertSee('October 2026')
            ->assertSee('calendar?month=2026-09', false);
    }

    public function test_training_map_preselects_a_valid_state_only(): void
    {
        $this->get('/training-map?state=nv')->assertOk()->assertSee('var preselected = "NV"', false);
        $this->get('/training-map?state=ZZ')->assertOk()->assertSee('var preselected = null', false);
    }

    public function test_home_page_links_every_state_to_the_training_map(): void
    {
        $html = $this->get('/')->assertOk()
            ->assertSee('Visible Emission Support Services')
            ->assertSee('100% Digital')
            ->getContent();

        $this->assertSame(50, substr_count($html, '/training-map?state='));
        $this->assertStringContainsString('/training-map?state=WY', $html);
    }

    public function test_session_page_accepts_our_links_and_live_site_links(): void
    {
        $this->get('/smoke-schools/nv/8507')->assertOk()->assertSee('Reno Training Site');
        $this->get('/smoke-schools/nv/reno-09-01-2026-8507')->assertOk()->assertSee('Reno Training Site');
    }

    public function test_session_page_is_not_found_for_missing_or_non_public_sessions(): void
    {
        $this->get('/smoke-schools/nv/9999')->assertNotFound();
        $this->get('/smoke-schools/nv/no-id-here')->assertNotFound();
        $this->get('/smoke-schools/tx/new-braunfels-09-22-2026-8577')->assertNotFound();
    }

    public function test_terms_and_privacy_pages_link_to_each_other_and_from_the_footer(): void
    {
        $terms = $this->get('/terms')->assertOk()->assertSee('28. Contact CAA')->assertSee('id="eula"', false)->getContent();
        $this->assertStringNotContainsString('compliance-assurance.com/privacy.php', $terms);
        $this->assertStringContainsString('/privacy"', $terms);

        $this->get('/privacy')->assertOk()->assertSee('Changes to this policy')
            ->assertSee('Terms and Conditions &raquo;', false)
            ->assertSee('Privacy Policy &raquo;', false);
    }

    public function test_existing_clients_hub_links_the_client_resources(): void
    {
        $html = $this->get('/existing-clients')->assertOk()->assertSee('Existing Client Services')->getContent();

        foreach (['/account/login', '/certs', '/certs/find-student-number', '/digital-student', '/lecture'] as $path) {
            $this->assertStringContainsString('href="http://localhost'.$path.'"', $html, "missing tile link to $path");
        }
        $this->assertStringContainsString('>Log in<', $html);
        $this->assertStringContainsString('href="http://localhost/existing-clients" class="nav-btn nav-btn-blue"', $html);
    }

    public function test_find_a_smoke_school_page_and_its_entry_points(): void
    {
        $html = $this->get('/find-a-smoke-school')->assertOk()->assertSee('Find Smoke School Training')->getContent();
        foreach (['/calendar', '/smoke-schools', '/training-map', '/account/register', '/private-smoke-schools'] as $path) {
            $this->assertStringContainsString('href="http://localhost'.$path.'"', $html, "missing link to $path");
        }

        $this->get('/')->assertSee('href="http://localhost/find-a-smoke-school" class="btn-secondary btn-full"', false);
    }

    public function test_list_shows_upcoming_schools_with_dates_sorted_by_date(): void
    {
        $html = $this->get('/smoke-schools')->assertOk()->getContent();

        $this->assertStringContainsString('Sep 30 – Oct 1, 2026', $html);
        $this->assertStringContainsString('Tue, Oct 6, 2026', $html);
        $this->assertLessThan(strpos($html, 'Reno, NV'), strpos($html, 'Decatur, AL'), 'Decatur (Sep 30) before Reno (Oct 6)');
        $this->assertStringNotContainsString('Canceled Town', $html);
        $this->assertStringNotContainsString('August Start', $html);
    }

    public function test_list_sorts_by_state_including_the_live_sites_parameter_name(): void
    {
        foreach (['/smoke-schools?orderby=state', '/smoke-schools?orderby=school_state'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertLessThan(strpos($html, 'Reno, NV'), strpos($html, 'Decatur, AL'), "$url: Alabama before Nevada");
            $this->assertStringContainsString('<tr class="ls-state-heading"><td colspan="3">Alabama</td></tr>', $html);
        }
        $this->get('/smoke-schools?orderby=field_start')->assertOk()->assertSee('<span class="ls-active" aria-current="true">Date</span>', false);
    }

    public function test_date_range_labels(): void
    {
        $this->assertSame('Tue, Sep 1, 2026', PublicCalendarController::dateRangeLabel('2026-09-01', '2026-09-01'));
        $this->assertSame('Sep 1–2, 2026', PublicCalendarController::dateRangeLabel('2026-09-01', '2026-09-02'));
        $this->assertSame('Dec 31, 2026 – Jan 1, 2027', PublicCalendarController::dateRangeLabel('2026-12-31', '2027-01-01'));
    }

    public function test_online_lecture_page_and_online_sessions_stay_off_school_listings(): void
    {
        $this->get('/online-self-paced-lecture')->assertOk()
            ->assertSee('Online Self-Paced Visible Emissions Lecture Course')
            ->assertSee('href="http://localhost/lecture" class="btn-secondary btn-full"', false)
            ->assertSee('href="http://localhost/account/register"', false);

        $this->get('/smoke-schools')->assertOk()->assertDontSee('Online Lecture');
        $this->get('/training-map?state=AL')->assertOk()->assertDontSee('Online Lecture');
    }

    public function test_alt152a_state_map_shows_statuses_regions_and_only_existing_letters(): void
    {
        $html = $this->get('/vr-states')->assertOk()->assertSee('State Acceptance Map')->getContent();

        $this->assertMatchesRegularExpression('/data-state-detail="SD" data-status="Not Accepted" data-accepted="0"/', $html);
        $this->assertMatchesRegularExpression('/data-state-detail="DE" data-status="Accepted Conditionally" data-accepted="1"/', $html);
        $this->assertStringContainsString('Mojave Desert AQMD', $html);
        $this->assertStringContainsString('Casper', $html);
        $this->assertSame(50, substr_count($html, 'data-state-detail='));
        // No acceptance-letter PDFs are in the repo, so no letter links may render.
        $this->assertStringNotContainsString('ALT-152A-Acceptance/', $html);
    }

    public function test_alt152a_implementation_list_cards_open_the_map_for_that_state(): void
    {
        $html = $this->get('/vr-smoke-school-states')->assertOk()->assertSee('Virtual Smoke School Acceptance by State')->getContent();

        $this->assertSame(50, substr_count($html, 'class="vl-card"'));
        $this->assertStringContainsString('href="http://localhost/vr-states?state=TX"', $html);
        $this->assertStringContainsString('<span class="vl-badge vl-badge-accepted"><i></i>Conditionally Accepted</span>', $html);
        $this->assertStringContainsString('<span class="vl-badge vl-badge-not"><i></i>Not Accepted</span>', $html);

        $this->get('/vr-states?state=tx')->assertOk()->assertSee('var preselected = "TX"', false);
        $this->get('/vr-states?state=ZZ')->assertOk()->assertSee('var preselected = null', false);
    }

    public function test_vr_testimonials_page_lists_all_eight(): void
    {
        $html = $this->get('/vr-testimonials')->assertOk()->assertSee('VirtualOpacity<sup>&reg;</sup> Testimonials', false)->getContent();

        $this->assertSame(8, substr_count($html, 'class="vt-card"'));
        $this->assertStringContainsString('Major E&amp;C consulting group', $html);
        $this->assertStringContainsString("I hope I don&#039;t ever have to go back to the field school.", $html);
    }

    public function test_federal_holidays_fall_on_the_right_dates(): void
    {
        $h = PublicCalendarController::federalHolidays(2027);
        $this->assertSame('Memorial Day', $h['2027-05-31']);
        $this->assertSame('Labor Day', $h['2027-09-06']);
        $this->assertSame('Thanksgiving Day', $h['2027-11-25']);
        $this->assertSame('Martin Luther King Jr. Day', $h['2027-01-18']);
    }
}
