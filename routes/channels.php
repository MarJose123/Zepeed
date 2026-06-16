<?php

use Illuminate\Support\Facades\Broadcast;

// Authenticated users can listen on any provider's channel
Broadcast::channel('speedtest.{providerSlug}',
    static fn ($user, string $providerSlug) => auth()->check());

// For on-demand speedtest testing of the provider
Broadcast::channel('speedtest.test.{providerSlug}',
    static fn ($user, string $providerSlug) => auth()->check());

// Presence channel for authenticated dashboard — all active sessions
// receive the refresh broadcast after a speedtest completes
Broadcast::channel('dashboard', static function ($user): array|false {
    if (! auth()->check()) {
        return false;
    }

    return ['id' => $user->id];
});

Broadcast::channel('ping.results', static fn ($user) => auth()->check());
