<?php

namespace App\Providers;

use App\Models\Setting;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Override;
use Throwable;
use URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[Override]
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        self::applyGeneralSettings();

        AboutCommand::add('Zepeed', static fn () => [
            'Version'    => config('app.version'),
            'Build Date' => config('app.build_date'),
        ]);
    }

    /**
     * Apply stored general settings to the running application.
     *
     * Wrapped in a try/catch so a missing or not-yet-migrated settings
     * table never breaks the boot sequence during fresh installs or CI.
     */
    private static function applyGeneralSettings(): void
    {
        try {

            // ── App URL ───────────────────────────────────────────────────
            // Force the root URL and HTTPS scheme on all generated URLs
            // so notification links and share URLs are always correct.
            $appUrl = Setting::get('app_url');

            if (is_string($appUrl) && $appUrl !== '') {
                config(['app.url' => $appUrl]);
                URL::useOrigin($appUrl);

                if (str_starts_with($appUrl, 'https://')) {
                    URL::forceHttps();
                }
            }

            // ── Timezone ──────────────────────────────────────────────────
            // Sets PHP's default timezone AND Carbon's default so all
            // date/time operations, schedules, and displayed timestamps
            // honour the persisted value on every request.
            $timezone = Setting::get('timezone');

            if (is_string($timezone) && $timezone !== '') {
                config(['app.timezone' => $timezone]);
                date_default_timezone_set($timezone);
            }

            // ── Environment ───────────────────────────────────────────────
            // Reflects the stored environment in config so debug output
            // and error detail match what the user configured.
            $env = Setting::get('app_env');

            if (in_array($env, ['production', 'local', 'staging'], true)) {
                config(['app.env'   => $env]);
                config(['app.debug' => $env !== 'production']);
            }
        } catch (Throwable) {
            // Silently fall back to .env defaults during install / CI /
            // before migrations have run.
        }
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(static fn (): Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : Password::min(5)
        );
    }
}
