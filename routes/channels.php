<?php

use Illuminate\Support\Facades\Broadcast;

// Any authenticated user can subscribe to per-provider events
Broadcast::channel('speedtest.{providerSlug}',
    fn ($user, string $providerSlug) => auth()->check());

// For on-demand speedtest testing of the provider
Broadcast::channel('speedtest.test.{providerSlug}',
    fn ($user, string $providerSlug) => auth()->check());

// Authenticated dashboard — presence channel so all active sessions
// receive refresh broadcasts after a speedtest completes
Broadcast::channel('dashboard', function ($user): array|false {
    if (! auth()->check()) {
        return false;
    }

    return ['id' => $user->id];
});
