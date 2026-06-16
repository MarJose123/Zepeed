<?php

namespace App\Support;

use App\Enums\EmailTemplateType;
use Illuminate\Support\Uri;

/**
 * Defines all available email template merge fields.
 * Used by the frontend autocomplete and backend rendering.
 */
final class MergeFields
{
    /**
     * All speedtest merge fields.
     *
     * @return array<array{group: string, name: string, desc: string, tag: string, sample: string}>
     */
    public static function speedtest(): array
    {
        return [
            // Provider & result
            [
                'group'  => 'Provider & result',
                'name'   => 'Provider name',
                'desc'   => 'e.g. Speedtest · Ookla',
                'tag'    => '{{ $provider_name }}',
                'sample' => 'Speedtest · Ookla',
            ],
            [
                'group'  => 'Provider & result',
                'name'   => 'Status',
                'desc'   => 'failed / success / skipped',
                'tag'    => '{{ $status }}',
                'sample' => 'failed',
            ],
            [
                'group'  => 'Provider & result',
                'name'   => 'Download Mbps',
                'desc'   => 'numeric value',
                'tag'    => '{{ $download_mbps }}',
                'sample' => '0.00',
            ],
            [
                'group'  => 'Provider & result',
                'name'   => 'Upload Mbps',
                'desc'   => 'numeric value',
                'tag'    => '{{ $upload_mbps }}',
                'sample' => '0.00',
            ],
            [
                'group'  => 'Provider & result',
                'name'   => 'Ping ms',
                'desc'   => 'numeric value',
                'tag'    => '{{ $ping_ms }}',
                'sample' => '145',
            ],
            [
                'group'  => 'Provider & result',
                'name'   => 'Jitter ms',
                'desc'   => 'numeric value',
                'tag'    => '{{ $jitter_ms }}',
                'sample' => '12',
            ],
            [
                'group'  => 'Provider & result',
                'name'   => 'Packet loss %',
                'desc'   => 'nullable numeric',
                'tag'    => '{{ $packet_loss }}',
                'sample' => '0',
            ],

            // Metadata
            [
                'group'  => 'Metadata',
                'name'   => 'Measured at',
                'desc'   => 'datetime string',
                'tag'    => '{{ $measured_at }}',
                'sample' => '2 Apr 2026 10:30',
            ],
            [
                'group'  => 'Metadata',
                'name'   => 'Failure reason',
                'desc'   => 'nullable string',
                'tag'    => '{{ $failure_reason }}',
                'sample' => 'Connection timed out',
            ],
            [
                'group'  => 'Metadata',
                'name'   => 'Failure message',
                'desc'   => 'nullable detail message',
                'tag'    => '{{ $failure_message }}',
                'sample' => '',
            ],
            [
                'group'  => 'Metadata',
                'name'   => 'ISP',
                'desc'   => 'internet service provider',
                'tag'    => '{{ $isp }}',
                'sample' => 'Comcast',
            ],
            [
                'group'  => 'Metadata',
                'name'   => 'Client IP',
                'desc'   => 'nullable IP address',
                'tag'    => '{{ $client_ip }}',
                'sample' => '203.0.113.1',
            ],

            // Links
            [
                'group'  => 'Links',
                'name'   => 'Dashboard URL',
                'desc'   => 'link to the app dashboard',
                'tag'    => '{{ $dashboard_url }}',
                'sample' => Uri::of('https://app.zepeed.local')->getUri(),
            ],
            [
                'group'  => 'Links',
                'name'   => 'Share URL',
                'desc'   => 'nullable speedtest result link',
                'tag'    => '{{ $share_url }}',
                'sample' => '',
            ],
        ];
    }

    /**
     * All ping merge fields.
     *
     * @return array<array{group: string, name: string, desc: string, tag: string, sample: string}>
     */
    public static function ping(): array
    {
        return [
            // Target & result
            [
                'group'  => 'Target & result',
                'name'   => 'Target label',
                'desc'   => 'e.g. Primary DNS',
                'tag'    => '{{ $target_label }}',
                'sample' => 'Primary DNS',
            ],
            [
                'group'  => 'Target & result',
                'name'   => 'Target host',
                'desc'   => 'hostname or IP address',
                'tag'    => '{{ $target_host }}',
                'sample' => '8.8.8.8',
            ],
            [
                'group'  => 'Target & result',
                'name'   => 'Status',
                'desc'   => 'success / partial / failed',
                'tag'    => '{{ $status }}',
                'sample' => 'failed',
            ],
            [
                'group'  => 'Target & result',
                'name'   => 'Packets sent',
                'desc'   => 'total packets transmitted',
                'tag'    => '{{ $packets_sent }}',
                'sample' => '4',
            ],
            [
                'group'  => 'Target & result',
                'name'   => 'Packets received',
                'desc'   => 'packets received back',
                'tag'    => '{{ $packets_received }}',
                'sample' => '0',
            ],
            [
                'group'  => 'Target & result',
                'name'   => 'Packet loss %',
                'desc'   => 'percentage of lost packets',
                'tag'    => '{{ $packet_loss_percent }}',
                'sample' => '100',
            ],

            // Latency
            [
                'group'  => 'Latency',
                'name'   => 'Min latency (ms)',
                'desc'   => 'minimum round-trip time',
                'tag'    => '{{ $min_ms }}',
                'sample' => '—',
            ],
            [
                'group'  => 'Latency',
                'name'   => 'Avg latency (ms)',
                'desc'   => 'average round-trip time',
                'tag'    => '{{ $avg_ms }}',
                'sample' => '—',
            ],
            [
                'group'  => 'Latency',
                'name'   => 'Max latency (ms)',
                'desc'   => 'maximum round-trip time',
                'tag'    => '{{ $max_ms }}',
                'sample' => '—',
            ],
            [
                'group'  => 'Latency',
                'name'   => 'Std deviation (ms)',
                'desc'   => 'latency standard deviation',
                'tag'    => '{{ $stddev_ms }}',
                'sample' => '—',
            ],

            // Failure
            [
                'group'  => 'Failure',
                'name'   => 'Failure reason',
                'desc'   => 'timeout / unreachable / dns_failed',
                'tag'    => '{{ $failure_reason }}',
                'sample' => 'timeout',
            ],

            // Alert context
            [
                'group'  => 'Alert context',
                'name'   => 'Alert rule name',
                'desc'   => 'name of the triggered rule',
                'tag'    => '{{ $rule_name }}',
                'sample' => 'High latency alert',
            ],
            [
                'group'  => 'Alert context',
                'name'   => 'Triggered at',
                'desc'   => 'datetime the alert fired',
                'tag'    => '{{ $triggered_at }}',
                'sample' => '2 Apr 2026 10:30',
            ],

            // Links
            [
                'group'  => 'Links',
                'name'   => 'Dashboard URL',
                'desc'   => 'link to the app dashboard',
                'tag'    => '{{ $dashboard_url }}',
                'sample' => Uri::of('https://app.zepeed.local')->getUri(),
            ],
        ];
    }

    /**
     * All fields — used when template_type is unknown (backwards compat).
     *
     * @return array<array{group: string, name: string, desc: string, tag: string, sample: string}>
     */
    public static function all(): array
    {
        return array_merge(self::speedtest(), self::ping());
    }

    /**
     * Return the correct field set for a given template type.
     *
     * @return array<array{group: string, name: string, desc: string, tag: string, sample: string}>
     */
    public static function forType(EmailTemplateType $type): array
    {
        return match ($type) {
            EmailTemplateType::Ping      => self::ping(),
            EmailTemplateType::Speedtest => self::speedtest(),
        };
    }

    /**
     * Sample data for template preview rendering.
     *
     * @param EmailTemplateType|null $type null falls back to all fields
     *
     * @return array<string, string>
     */
    public static function sampleData(?EmailTemplateType $type = null): array
    {
        $fields = $type !== null ? self::forType($type) : self::all();

        $data = [];
        foreach ($fields as $field) {
            preg_match('/\$(\w+)/', $field['tag'], $m);
            if (isset($m[1])) {
                $data[$m[1]] = $field['sample'];
            }
        }

        return $data;
    }
}
