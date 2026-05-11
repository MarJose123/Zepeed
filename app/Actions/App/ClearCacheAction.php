<?php

namespace App\Actions\App;

use Illuminate\Support\Facades\Artisan;
use InvalidArgumentException;

class ClearCacheAction
{
    private const array ALLOWED = ['optimize', 'optimize:clear'];

    /**
     * Run the requested artisan cache command.
     *
     * @throws InvalidArgumentException
     */
    public function handle(string $command): void
    {
        if (! in_array($command, self::ALLOWED, true)) {
            throw new InvalidArgumentException("Unknown cache command: {$command}");
        }

        Artisan::call($command);
    }
}
