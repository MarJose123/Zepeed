<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\SpeedResult;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class PruneSpeedResultsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'speedtest:prune
                            {--dry-run : Preview how many rows would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune speed_results rows older than the configured retention window';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $enabled = (bool) Setting::get('result_auto_purge', false);

        if (! $enabled) {
            $this->components->info('Speed result pruning is disabled. Enable it in General Settings → Data Retention.');

            return self::SUCCESS;
        }

        $retentionDays = max(30, (int) Setting::get('result_retention_days', 90));
        $exemptFailed = (bool) Setting::get('exempt_failed', false);
        $cutoff = now()->subDays($retentionDays);

        $query = SpeedResult::query()->where('created_at', '<', $cutoff);

        if ($exemptFailed) {
            $query->where('status', '!=', 'failed');
        }

        $total = $query->count();

        if ($total === 0) {
            $this->components->info("No speed results older than {$retentionDays} days found. Nothing to prune.");

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->components->warn("[DRY RUN] Would delete {$total} speed result row(s) older than {$retentionDays} days.");

            return self::SUCCESS;
        }

        $this->components->info("Pruning {$total} speed result row(s) older than {$retentionDays} days...");

        // Chunked delete to avoid long table locks on large datasets.
        $deleted = 0;

        DB::transaction(function () use ($query, &$deleted): void {
            $query->chunkById(500, function ($chunk) use (&$deleted): void {
                $ids = $chunk->pluck('id')->all();
                $deleted += SpeedResult::query()->whereIn('id', $ids)->delete();
            });
        });

        $this->components->success("Pruned {$deleted} speed result row(s) successfully.");

        return self::SUCCESS;
    }
}
