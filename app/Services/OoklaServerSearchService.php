<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class OoklaServerSearchService
{
    private const string CACHE_KEY = 'ookla_servers_list';

    private const int CACHE_MINUTES = 60 * 24;

    private const int API_LIMIT = 100;

    private const string API_URL = 'https://www.speedtest.net/api/js/servers';

    /**
     * Search the cached server list by name, sponsor, or country.
     *
     * On a cache miss the full list is fetched from the Ookla API first,
     * then cached for 24 hours before filtering is applied.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function search(string $query): Collection
    {
        return self::all()->filter(
            static fn (array $server): bool => self::matches($server, $query)
        )->take(20)->values();
    }

    /**
     * Force-refresh the cached server list and return it.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function refreshCache(): Collection
    {
        Cache::forget(self::CACHE_KEY);

        return self::all();
    }

    /**
     * Return (or hydrate) the full cached server list.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private static function all(): Collection
    {
        /** @var Collection<int, array<string, mixed>> $servers */
        $servers = Cache::remember(
            key: self::CACHE_KEY,
            ttl: now()->addMinutes(self::CACHE_MINUTES),
            callback: static fn (): Collection => self::fetchFromApi(),
        );

        return $servers;
    }

    /**
     * Call the Ookla API and return the result as a Collection.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private static function fetchFromApi(): Collection
    {
        return Http::retry(3, 250)
            ->get(self::API_URL, [
                'engine'           => 'js',
                'https_functional' => true,
                'limit'            => self::API_LIMIT,
            ])
            ->throw()
            ->collect();
    }

    /**
     * Determine if a server record matches the given query string.
     *
     * @param array<string, mixed> $server
     */
    private static function matches(array $server, string $query): bool
    {
        return collect([
            (string) ($server['name'] ?? ''),
            (string) ($server['sponsor'] ?? ''),
            (string) ($server['country'] ?? ''),
        ])->contains(
            static fn (string $field): bool => Str::containsAll($field, [$query], ignoreCase: true)
        );
    }
}
