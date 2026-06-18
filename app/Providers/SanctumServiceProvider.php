<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;
use Override;

class SanctumServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    #[Override]
    public function register(): void {}

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
    }
}
