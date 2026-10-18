<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates that a URL or host does not resolve to an internal IP address,
 * preventing Server-Side Request Forgery (SSRF) attacks.
 *
 * Blocks private ranges (10/8, 172.16/12, 192.168/16), loopback, link-local,
 * and cloud metadata endpoints (169.254.169.254).
 */
class NotInternalIp implements ValidationRule
{
    /**
     * Internal and reserved IP ranges to block.
     *
     * @var array<int, string>
     */
    private array $blockedRanges = [
        '10.0.0.0/8',           // Private
        '172.16.0.0/12',        // Private
        '192.168.0.0/16',       // Private
        '127.0.0.0/8',          // Loopback
        '0.0.0.0/8',            // Current network
        '169.254.0.0/16',       // Link-local / cloud metadata
        '::1/128',              // IPv6 loopback
        'fc00::/7',             // IPv6 unique local
        'fe80::/10',            // IPv6 link-local
    ];

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $host = $this->extractHost((string) $value);

        if ($host === null) {
            return;
        }

        // If the host is already an IP address, check it directly
        $ip = $this->resolveHost($host);

        if ($ip === null) {
            return;
        }

        if ($this->isInternalIp($ip)) {
            $fail('The :attribute must not target an internal or reserved IP address.');
        }
    }

    /**
     * Extract the host from a URL or return the value if it's a plain host.
     */
    private function extractHost(string $value): ?string
    {
        // If it looks like a URL, extract the host
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            $parsed = parse_url($value, PHP_URL_HOST);

            return $parsed !== false && $parsed !== '' ? $parsed : null;
        }

        // Otherwise treat the value as a plain host
        return $value !== '' ? $value : null;
    }

    /**
     * Resolve a host to an IP address.
     */
    private function resolveHost(string $host): ?string
    {
        // If it's already an IP address, return it
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return $host;
        }

        // Resolve DNS
        $ip = gethostbyname($host);

        // gethostbyname returns the input string if resolution fails
        if ($ip === $host || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return null;
        }

        return $ip;
    }

    /**
     * Check if an IP address falls within any blocked range.
     */
    private function isInternalIp(string $ip): bool
    {
        $ipLong = ip2long($ip);

        if ($ipLong === false) {
            // IPv6 address — basic check for known problematic ranges
            return str_starts_with($ip, '::1')
                || str_starts_with($ip, 'fc')
                || str_starts_with($ip, 'fe80');
        }

        foreach ($this->blockedRanges as $range) {
            if (str_contains($range, '/')) {
                [$network, $mask] = explode('/', $range);
                $mask = (int) $mask;

                $networkLong = ip2long($network);
                if ($networkLong === false) {
                    continue;
                }

                $maskLong = -1 << (32 - $mask);
                if (($ipLong & $maskLong) === ($networkLong & $maskLong)) {
                    return true;
                }
            } elseif (ip2long($range) !== false && $ipLong === ip2long($range)) {
                return true;
            }
        }

        return false;
    }
}
