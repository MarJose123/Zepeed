<?php

namespace App\Actions\App;

use App\Data\GeneralSettingsData;
use App\Models\DowntimeLog;
use App\Models\Setting;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;

final class UpdateGeneralSettings
{
    /**
     * Persist all general settings and apply side effects.
     *
     * Read the old maintenance state BEFORE persisting, so the diff is correct.
     */
    public function handle(GeneralSettingsData $data): void
    {
        $was = (bool) Setting::get('maintenance_enabled', false);

        foreach ($data->toArray() as $key => $value) {
            Setting::set($key, $value);
        }

        self::syncMaintenanceMode($was, $data->maintenance_enabled, $data);
    }

    /**
     * Fire artisan down/up only when the toggle actually flips.
     */
    private static function syncMaintenanceMode(
        bool $was,
        bool $is,
        GeneralSettingsData $data,
    ): void {
        if ($was === $is) {
            return;
        }

        $actor = Auth::user()->email ?? 'system';

        if ($is) {
            // Auto-generate a bypass secret if none was provided so the admin
            // is never locked out after enabling maintenance mode.
            $secret = ($data->bypass_secret !== null && $data->bypass_secret !== '')
                ? $data->bypass_secret
                : self::generateBypassSecret();

            // Persist the (possibly auto-generated) secret so it is readable
            // by the controller for the post-save redirect.
            Setting::set('bypass_secret', $secret);

            $args = ['--secret' => $secret];

            if ($data->retry_after_value > 0) {
                $args['--retry'] = $data->retry_after_unit === 'minutes'
                    ? $data->retry_after_value * 60
                    : $data->retry_after_value;
            }

            if ($data->maintenance_redirect !== null && $data->maintenance_redirect !== '') {
                $args['--redirect'] = $data->maintenance_redirect;
            }

            DowntimeLog::query()->create([
                'event'        => 'DOWN',
                'triggered_by' => $actor,
                'duration'     => null,
                'timestamp'    => now(),
            ]);

            Artisan::call('down', $args);

            return;
        }

        Artisan::call('up');

        $lastDown = DowntimeLog::query()
            ->where('event', 'DOWN')
            ->whereNull('duration')
            ->orderByDesc('timestamp')
            ->first();

        if ($lastDown !== null) {
            $lastDown->update([
                'duration' => Date::parse($lastDown->timestamp)
                    ->diffForHumans(now(), CarbonInterface::DIFF_ABSOLUTE, true, 1),
            ]);
        }

        DowntimeLog::query()->create([
            'event'        => 'UP',
            'triggered_by' => $actor,
            'duration'     => null,
            'timestamp'    => now(),
        ]);
    }

    /**
     * Generate a cryptographically random bypass secret.
     */
    private static function generateBypassSecret(): string
    {
        return bin2hex(random_bytes(12)); // 24-char hex string
    }

    /**
     * @param int $page
     *
     * @return array{
     *     data: list<array{event:string,triggered_by:string,duration:string|null,timestamp:string}>,
     *     current_page: int,
     *     last_page: int,
     *     total: int,
     * }
     */
    public function downtimeLogs(int $page = 1): array
    {
        $paginator = DowntimeLog::query()
            ->orderBy('timestamp')
            ->paginate(perPage: 10, page: $page);

        $data = $paginator->getCollection()
            ->map(static fn (DowntimeLog $log): array => [
                'event'        => (string) $log->event,
                'triggered_by' => (string) $log->triggered_by,
                'duration'     => $log->duration !== null ? (string) $log->duration : null,
                'timestamp'    => (string) $log->getRawOriginal('timestamp'),
            ])
            ->values()
            ->all();

        return [
            'data'         => $data,
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
            'total'        => $paginator->total(),
        ];
    }
}
