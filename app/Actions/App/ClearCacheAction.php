<?php

namespace App\Actions\App;

use Illuminate\Support\Facades\Artisan;
use InvalidArgumentException;

class ClearCacheAction
{
    private const array COMMANDS = [
        'app'    => 'cache:clear',
        'config' => 'config:clear',
        'route'  => 'route:clear',
        'view'   => 'view:clear',
    ];

    /**
     * Run the artisan clear command for the given cache type.
     *
     * @throws InvalidArgumentException
     */
    public function handle(string $type): void
    {
        if (! array_key_exists($type, self::COMMANDS)) {
            throw new InvalidArgumentException("Unknown cache type: {$type}");
        }

        Artisan::call(self::COMMANDS[$type]);
    }
}
