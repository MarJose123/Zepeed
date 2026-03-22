<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class RealtimeCredentialsCommand extends Command
{
    protected $signature = 'realtime:credentials
                            {--force : Overwrite existing credentials}';

    protected $description = 'Generate secure Soketi/Pusher credentials and write them to .env';

    public function handle(): int
    {
        $env = app()->environmentFile();

        if (File::missing($env)) {
            $this->components->error("Environment file not found: {$env}");

            return self::FAILURE;
        }

        $contents = File::get($env);

        $alreadySet = collect([
            'PUSHER_APP_ID',
            'PUSHER_APP_KEY',
            'PUSHER_APP_SECRET',
        ])->filter(fn (string $key) => $this->hasValue($contents, $key));

        if ($alreadySet->isNotEmpty() && ! $this->option('force')) {
            $this->components->warn(
                'Realtime credentials already set with values: '.$alreadySet->join(', ')
            );
            $this->components->info('Use --force to overwrite existing credentials.');

            return self::SUCCESS;
        }

        $this->writeCredentials($env, $contents);

        $this->components->info('Realtime credentials written to '.basename($env));

        return self::SUCCESS;
    }

    /**
     * Check if a key exists in the .env file AND has a non-empty value.
     */
    protected function hasValue(string $contents, string $key): bool
    {
        return (bool) preg_match(
            '/^'.preg_quote($key, '/').'=(?!["\']?$).+$/m',
            $contents,
        );
    }

    /**
     * Check if a key exists in the .env file regardless of value.
     */
    protected function keyExists(string $contents, string $key): bool
    {
        return (bool) preg_match(
            '/^'.preg_quote($key, '/').'=/m',
            $contents,
        );
    }

    /**
     * Credits to github.com/laravel/reverb
     *
     * @see https://github.com/laravel/reverb/blob/7a1ef2235cfe085cdf3190d0dcfaed4f7d251734/src/Console/Commands/InstallCommand.php#L47
     */
    protected function writeCredentials(string $env, string $contents): void
    {
        $appId = random_int(100_000, 999_999);
        $appKey = Str::lower(Str::random(20));
        $appSecret = Str::lower(Str::random(20));

        $variables = [
            'PUSHER_APP_ID'       => "PUSHER_APP_ID={$appId}",
            'PUSHER_APP_KEY'      => "PUSHER_APP_KEY={$appKey}",
            'PUSHER_APP_SECRET'   => "PUSHER_APP_SECRET={$appSecret}",
            'PUSHER_HOST'         => 'PUSHER_HOST=soketi',
            'PUSHER_PORT'         => 'PUSHER_PORT=6001',
            'PUSHER_SCHEME'       => 'PUSHER_SCHEME=http',
            'PUSHER_APP_CLUSTER'  => 'PUSHER_APP_CLUSTER=mt1',
            'VITE_PUSHER_APP_KEY' => 'VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"',
            'VITE_PUSHER_HOST'    => 'VITE_PUSHER_HOST=localhost',
            'VITE_PUSHER_PORT'    => 'VITE_PUSHER_PORT="${PUSHER_PORT}"',
            'VITE_PUSHER_SCHEME'  => 'VITE_PUSHER_SCHEME="${PUSHER_SCHEME}"',
        ];

        $toAppend = [];
        $modified = $contents;

        foreach ($variables as $key => $line) {
            $shouldWrite = $this->option('force') || ! $this->hasValue($modified, $key);

            if (! $shouldWrite) {
                continue;
            }

            if ($this->keyExists($modified, $key)) {
                // Key exists but has no value — replace it in place
                $modified = preg_replace(
                    '/^'.preg_quote($key, '/').'=.*$/m',
                    $line,
                    (string) $modified,
                );
            } else {
                // Key doesn't exist — queue for appending
                $toAppend[] = $line;
            }
        }

        // Write back modified contents if any in-place replacements were made
        if ($modified !== $contents) {
            File::put($env, $modified);
        }

        // Append any new keys that didn't exist at all
        if (! empty($toAppend)) {
            $block = trim(implode(PHP_EOL, $toAppend));

            File::append(
                $env,
                Str::endsWith($modified, PHP_EOL)
                    ? PHP_EOL.$block.PHP_EOL
                    : PHP_EOL.PHP_EOL.$block.PHP_EOL,
            );
        }
    }
}
