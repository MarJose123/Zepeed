<?php

namespace App\Console\Commands;

use App\Models\ExportRequest;
use Illuminate\Console\Command;

final class PruneExpiredExportsCommand extends Command
{
    protected $signature = 'app:exports-prune {--dry-run : Preview deletions without removing files}';
    protected $description = 'Delete export files and rows that have passed their 7-day expiry.';

    public function handle(): int
    {
        $expired = ExportRequest::query()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();

        if ($expired->isEmpty()) {
            $this->components->info('No expired exports found.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->components->warn("[DRY RUN] Would delete {$expired->count()} expired export(s).");

            return self::SUCCESS;
        }

        $deleted = 0;

        foreach ($expired as $export) {
            if ($export->file_path) {
                $fullPath = storage_path("app/private/{$export->file_path}");
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
            }

            $export->delete();
            $deleted++;
        }

        $this->components->success("Pruned {$deleted} expired export(s).");

        return self::SUCCESS;
    }
}
