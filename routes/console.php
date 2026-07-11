<?php

use App\Console\Commands\PruneExpiredExportsCommand;

Schedule::command('sanctum:prune-expired --hours=24')->daily();
Schedule::command(PruneExpiredExportsCommand::class)->daily();
