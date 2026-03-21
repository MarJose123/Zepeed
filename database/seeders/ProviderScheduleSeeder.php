<?php

namespace Database\Seeders;

use App\Enums\SpeedtestServer;
use App\Models\ProviderSchedule;
use Illuminate\Database\Seeder;

class ProviderScheduleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (SpeedtestServer::cases() as $server) {
            $exists = ProviderSchedule::where('provider_slug', $server->value)->exists();

            if (! $exists) {
                // First-time insert only
                ProviderSchedule::create([
                    'provider_slug'    => $server->value,
                    'cron_expression'  => null,  // admin sets via Schedules UI
                    'is_enabled'       => false,
                ]);

                $this->command->info("Schedule created: {$server->label()}");
            } else {
                $this->command->line("Schedule already exists, skipping: {$server->label()}");
            }
        }
    }
}
