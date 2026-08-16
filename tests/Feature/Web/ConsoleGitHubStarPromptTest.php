<?php

namespace Tests\Feature\Web;

use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\RequestException;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class ConsoleGitHubStarPromptTest extends TestCase
{
    use RefreshDatabase;

    private const string PROMPT = 'Enjoying this project? Would you like to support us by starring the GitHub repository?';

    private const string REPO_URL = 'https://github.com/example/repo';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('zepeed.github_repository_url', self::REPO_URL);
    }

    // --- app:ookla-list-servers ---

    public function testOoklaListServersPromptsAfterSuccessAndOpensBrowserOnYes(): void
    {
        Http::fake([
            'speedtest.net/*' => Http::response([
                ['id' => 1, 'sponsor' => 'ACME', 'name' => 'Server 1', 'country' => 'US', 'distance' => 10],
            ]),
        ]);
        Process::fake([$this->browserOpener() . ' *' => Process::result()]);

        $this->artisan('app:ookla-list-servers')
            ->expectsQuestion(self::PROMPT, 'yes')
            ->expectsOutputToContain('GitHub repository: ' . self::REPO_URL)
            ->assertExitCode(0);

        Process::assertRan($this->browserProcessMatcher());
    }

    public function testOoklaListServersSkipsPromptWhenRequestFails(): void
    {
        Http::fake(['speedtest.net/*' => Http::response(status: 500)]);

        // `Http::retry(3, ...)` throws on any failed response, so the command
        // aborts before it could ever reach the GitHub star prompt.
        $this->expectException(RequestException::class);

        $this->artisan('app:ookla-list-servers')->run();
    }

    // --- app:realtime-credential ---

    public function testRealtimeCredentialsPromptsAfterWritingCredentials(): void
    {
        Process::fake([$this->browserOpener() . ' *' => Process::result()]);
        $this->fakeEnvironmentFile("APP_NAME=Zepeed\n", written: true);

        $this->artisan('app:realtime-credential')
            ->expectsQuestion(self::PROMPT, 'yes')
            ->expectsOutputToContain('GitHub repository: ' . self::REPO_URL)
            ->assertExitCode(0);

        Process::assertRan($this->browserProcessMatcher());
    }

    public function testRealtimeCredentialsSkipsPromptWhenCredentialsAlreadyExist(): void
    {
        Process::fake();
        $this->fakeEnvironmentFile(
            "PUSHER_APP_ID=123\nPUSHER_APP_KEY=abc\nPUSHER_APP_SECRET=xyz\n",
            written: false,
        );

        $this->artisan('app:realtime-credential')
            ->assertExitCode(0)
            ->doesntExpectOutputToContain('GitHub repository:');

        Process::assertNotRan($this->browserProcessMatcher());
    }

    // --- app:create-user-account ---

    public function testCreateUserAccountDefaultPathNeverPrompts(): void
    {
        Process::fake();
        config()->set('zepeed.default_admin', [
            'name'     => 'Test Admin',
            'email'    => 'admin-' . uniqid() . '@example.com',
            'password' => 'password123',
        ]);

        $this->artisan('app:create-user-account', ['--default' => true])
            ->assertExitCode(0)
            ->doesntExpectOutputToContain('GitHub repository:');

        Process::assertNotRan($this->browserProcessMatcher());
    }

    /**
     * Fake the .env read/write so the real file is never touched.
     */
    private function fakeEnvironmentFile(string $contents, bool $written): void
    {
        File::shouldReceive('missing')->once()->andReturn(false);
        File::shouldReceive('get')->once()->andReturn($contents);
        File::shouldReceive('put')->never();

        if ($written) {
            File::shouldReceive('append')->once();
        } else {
            File::shouldReceive('append')->never();
        }
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
     * @return Closure(PendingProcess): bool
     */
    private function browserProcessMatcher(): Closure
    {
        return fn ($process) => str_starts_with((string) $process->command, $this->browserOpener() . ' ');
    }
}
