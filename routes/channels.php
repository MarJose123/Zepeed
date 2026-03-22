<?php

use Illuminate\Support\Facades\Broadcast;

// Authenticated users can listen on any provider's channel
Broadcast::channel('speedtest.{providerSlug}',
    // Any authenticated user can subscribe
    fn ($user, string $providerSlug) => auth()->check());
// for on-demand speedtest testing of the provider
Broadcast::channel('speedtest.test.{providerSlug}',
    // Any authenticated user can subscribe
    fn ($user, string $providerSlug) => auth()->check());
