<?php

namespace Tests\Feature\Web;

use App\Enums\SpeedtestServer;
use App\Jobs\RunSpeedtestJob;
use App\Models\Provider;
use App\Services\Speedtest\Contracts\SpeedtestServiceInterface;
use App\Services\Speedtest\Data\SpeedtestResult;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class SpeedtestRunCommandGitHubStarTest extends TestCase
{
    use RefreshDatabase;

    private const string PROMPT = 'Enjoying this project? Would you like to support us by starring the GitHub repository?';

    private const string REPO_URL = 'https://github.com/example/repo';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('zepeed.github_repository_url', self::REPO_URL);
    }

    public function testQueuedRunStillDispatchesJobsAndPromptsAfterSuccess(): void
    {
        Queue::fake();
        Process::fake();
        $provider = $this->createRunnableProvider();

        $this->artisan('app:speedtest-run')
            ->expectsQuestion(self::PROMPT, 'yes')
            ->expectsOutputToContain('GitHub repository: ' . self::REPO_URL)
            ->assertExitCode(0);

        Queue::assertPushed(
            RunSpeedtestJob::class,
            fn (RunSpeedtestJob $job) => $job->provider->is($provider) && $job->runFromConsole,
        );
    }

    public function testSyncRunStillStoresTheResultAndPromptsAfterSuccess(): void
    {
        Process::fake();
        $this->mockSpeedtestService();
        $this->createRunnableProvider();

        $this->artisan('app:speedtest-run', ['--sync' => true])
            ->expectsQuestion(self::PROMPT, 'yes')
            ->expectsOutputToContain('GitHub repository: ' . self::REPO_URL)
            ->assertExitCode(0);

        $this->assertDatabaseHas('speed_results', ['provider_slug' => SpeedtestServer::Ookla->value]);
    }

    public function testPromptIsSkippedWhenTheCommandFails(): void
    {
        Process::fake();

        $this->artisan('app:speedtest-run', ['provider' => 'invalid-slug'])
            ->assertExitCode(1)
            ->doesntExpectOutputToContain('GitHub repository:');

        Process::assertNotRan($this->browserProcessMatcher());
    }

    public function testPromptIsSkippedWhenRunningNonInteractively(): void
    {
        Process::fake();
        $this->mockSpeedtestService();
        $this->createRunnableProvider();

        $this->artisan('app:speedtest-run', ['--sync' => true, '--no-interaction' => true])
            ->assertExitCode(0)
            ->doesntExpectOutputToContain('GitHub repository:');

        Process::assertNotRan($this->browserProcessMatcher());

        $this->assertDatabaseHas('speed_results', ['provider_slug' => SpeedtestServer::Ookla->value]);
    }

    public function testPromptIsSkippedWhenRepositoryUrlIsNotConfigured(): void
    {
        config()->set('zepeed.github_repository_url', null);

        Process::fake();
        $this->mockSpeedtestService();
        $this->createRunnableProvider();

        $this->artisan('app:speedtest-run', ['--sync' => true])
            ->assertExitCode(0)
            ->doesntExpectOutputToContain('GitHub repository:');

        Process::assertNotRan($this->browserProcessMatcher());

        $this->assertDatabaseHas('speed_results', ['provider_slug' => SpeedtestServer::Ookla->value]);
    }

    public function testYesAnswerOpensTheBrowserAndPrintsTheRepositoryUrl(): void
    {
        Process::fake([$this->browserOpener() . ' *' => Process::result()]);
        $this->mockSpeedtestService();
        $this->createRunnableProvider();

        $this->artisan('app:speedtest-run', ['--sync' => true])
            ->expectsQuestion(self::PROMPT, 'yes')
            ->expectsOutputToContain('Thanks for supporting the project! GitHub repository: ' . self::REPO_URL)
            ->assertExitCode(0);

        Process::assertRan($this->browserProcessMatcher());
    }

    public function testNoAnswerDoesNotOpenTheBrowser(): void
    {
        Process::fake();
        $this->mockSpeedtestService();
        $this->createRunnableProvider();

        $this->artisan('app:speedtest-run', ['--sync' => true])
            ->expectsConfirmation(self::PROMPT, 'no')
            ->assertExitCode(0)
            ->doesntExpectOutputToContain('GitHub repository:');

        Process::assertNotRan($this->browserProcessMatcher());
    }

    public function testPressingEnterUsesTheDefaultNoAnswer(): void
    {
        Process::fake();
        $this->mockSpeedtestService();
        $this->createRunnableProvider();

        // The confirmation defaults to "no", so an empty (Enter) answer
        // declines without opening the browser.
        $this->artisan('app:speedtest-run', ['--sync' => true])
            ->expectsQuestion(self::PROMPT, false)
            ->assertExitCode(0)
            ->doesntExpectOutputToContain('GitHub repository:');

        Process::assertNotRan($this->browserProcessMatcher());
    }

    public function testBrowserLaunchFailureStillPrintsTheRepositoryUrlAndSucceeds(): void
    {
        Process::fake([$this->browserOpener() . ' *' => Process::result(exitCode: 1)]);
        $this->mockSpeedtestService();
        $this->createRunnableProvider();

        $this->artisan('app:speedtest-run', ['--sync' => true])
            ->expectsQuestion(self::PROMPT, 'yes')
            ->expectsOutputToContain('GitHub repository: ' . self::REPO_URL)
            ->assertExitCode(0);

        Process::assertRan($this->browserProcessMatcher());
    }

    public function testBrowserLaunchExceptionDoesNotFailTheCommand(): void
    {
        Process::fake(static fn () => throw new RuntimeException('boom'));
        $this->mockSpeedtestService();
        $this->createRunnableProvider();

        $this->artisan('app:speedtest-run', ['--sync' => true])
            ->expectsQuestion(self::PROMPT, 'yes')
            ->expectsOutputToContain('GitHub repository: ' . self::REPO_URL)
            ->assertExitCode(0);
    }

    private function createRunnableProvider(): Provider
    {
        return Provider::factory()
            ->enabled()
            ->withSlug(SpeedtestServer::Ookla)
            ->create();
    }

    private function mockSpeedtestService(): void
    {
        $result = SpeedtestResult::fromNormalised(
            server: SpeedtestServer::Ookla,
            data: [
                'download_mbps' => 100.0,
                'upload_mbps'   => 50.0,
                'ping_ms'       => 10.0,
            ],
            rawJson: '{}',
        );

        $service = Mockery::mock(SpeedtestServiceInterface::class);
        $service->shouldReceive('run')->andReturn($result);

        $this->app->bind(SpeedtestServiceInterface::class, static fn () => $service);
    }

    /**
     * The browser-opening command prefix for the current OS.
     */
    private function browserOpener(): string
    {
        return match (PHP_OS_FAMILY) {
            'Darwin'  => 'open',
            'Windows' => 'start',
            default   => 'xdg-open',
        };
    }

    /**
     * @return Closure(PendingProcess):bool
     */
    private function browserProcessMatcher(): Closure
    {
        return fn ($process) => str_starts_with((string) $process->command, $this->browserOpener() . ' ');
    }
}
