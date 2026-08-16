<?php

namespace App\Console\Commands\Concerns;

use Illuminate\Support\Facades\Process;
use Throwable;

/**
 * Adds an optional "star the GitHub repository" prompt to a console command.
 *
 * The prompt runs only after the command's primary action has completed
 * successfully, only when the command is executed interactively, and only
 * when a repository URL is configured via `zepeed.github_repository_url`.
 *
 * Usage:
 *
 *     class MyCommand extends Command
 *     {
 *         use PromptsForGitHubStar;
 *
 *         public function handle(): int
 *         {
 *             // ... primary action ...
 *
 *             $this->promptForGitHubStar();
 *
 *             return self::SUCCESS;
 *         }
 *     }
 */
trait PromptsForGitHubStar
{
    /**
     * Ask the user whether they would like to star the GitHub repository.
     *
     * Skips silently when the repository URL is not configured, when the
     * command is not running interactively (e.g. `--no-interaction`, CI,
     * Docker, scheduled/cron runs), or when the user declines.
     *
     * Opening the browser is best-effort — a failure never fails the command;
     * the repository URL is echoed either way so the user can open it manually.
     */
    protected function promptForGitHubStar(): void
    {
        $url = config('zepeed.github_repository_url');

        if (! is_string($url) || trim($url) === '') {
            return;
        }

        if (! $this->input->isInteractive()) {
            return;
        }

        $supported = $this->confirm(
            'Enjoying this project? Would you like to support us by starring the GitHub repository?',
            default: false,
        );

        if (! $supported) {
            return;
        }

        $this->openGitHubRepositoryInBrowser($url);

        $this->info("Thanks for supporting the project! GitHub repository: {$url}");
    }

    /**
     * Attempt to open the given URL in the user's default browser.
     *
     * Uses the platform's standard opener command. Any failure (missing
     * binary, non-zero exit, exception) is swallowed so the command itself
     * never fails because the browser could not be launched.
     */
    protected function openGitHubRepositoryInBrowser(string $url): void
    {
        $opener = match (PHP_OS_FAMILY) {
            'Darwin'  => 'open',
            'Windows' => 'start ""',
            default   => 'xdg-open',
        };

        try {
            Process::run($opener . ' ' . escapeshellarg($url));
        } catch (Throwable) {
            // Best-effort — the repository URL is echoed to the terminal
            // regardless, so the user can open it manually.
        }
    }
}
