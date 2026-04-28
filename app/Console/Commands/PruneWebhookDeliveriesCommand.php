<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\WebhookDelivery;
use Illuminate\Console\Command;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class PruneWebhookDeliveriesCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'webhooks:prune
                            {--dry-run : Preview how many rows would be deleted without actually deleting}';

    /**
     * @var string
     */
    protected $description = 'Prune webhook_deliveries rows older than the configured retention window';

    public function handle(): int
    {
        $retentionDays = max(30, (int) Setting::get('webhook_retention_days', 30));
        $extendedFailures = (bool) Setting::get('webhook_extended_retention', true);
        $cutoff = now()->subDays($retentionDays);
        $extendedCutoff = now()->subDays(90);

        $query = WebhookDelivery::query()->where(function (Builder $q) use ($cutoff, $extendedCutoff, $extendedFailures): void {
            if ($extendedFailures) {
                // Failed deliveries use the 90-day extended window regardless.
                $q->where(function (Builder $inner) use ($cutoff): void {
                    $inner->where('status', '!=', 'failed')
                        ->where('created_at', '<', $cutoff);
                })->orWhere(function (Builder $inner) use ($extendedCutoff): void {
                    $inner->where('status', 'failed')
                        ->where('created_at', '<', $extendedCutoff);
                });
            } else {
                $q->where('created_at', '<', $cutoff);
            }
        });

        $total = $query->count();

        if ($total === 0) {
            $this->components->info('No webhook delivery records eligible for pruning found.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->components->warn("[DRY RUN] Would delete {$total} webhook delivery row(s).");

            return self::SUCCESS;
        }

        $this->components->info("Pruning {$total} webhook delivery row(s)...");

        $deleted = 0;

        DB::transaction(function () use ($query, &$deleted): void {
            $query->chunkById(500, function ($chunk) use (&$deleted): void {
                $ids = $chunk->pluck('id')->all();
                $deleted += WebhookDelivery::query()->whereIn('id', $ids)->delete();
            });
        });

        $this->components->success("Pruned {$deleted} webhook delivery row(s) successfully.");

        return self::SUCCESS;
    }
}
