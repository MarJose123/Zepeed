<?php

namespace Database\Seeders;

use App\Enums\SpeedtestServer;
use App\Models\Provider;
use Illuminate\Database\Seeder;

class ProviderSeeder extends Seeder
{
    public function run(): void
    {
        collect(SpeedtestServer::cases())->reverse()->each(function(SpeedtestServer $server){
            $exists = Provider::where('slug', $server->value)->exists();

            if (! $exists) {
                // First-time insert only — never run again for this slug
                Provider::create([
                    'slug'             => $server->value,
                    'name'             => $server->label(),
                    'is_enabled'       => false,
                    'alert_on_failure' => false,
                    'server_url'       => null,
                    'server_id'        => null,
                    'extra_flags'      => null,
                    'meta'             => null,
                ]);

                $this->command->info("Provider created: {$server->label()}");
            } else {
                // Already exists — only sync the label in case enum changed
                Provider::where('slug', $server->value)
                    ->update(['name' => $server->label()]);

                $this->command->line("Provider already exists, label synced: {$server->label()}");
            }
        });
    }
}
