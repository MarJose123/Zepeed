<?php

namespace App\Support;

use Illuminate\Support\Uri;

/**
 * Defines all available email template merge fields.
 * Used by the frontend autocomplete and backend rendering.
 */
final class MergeFields
{
    /**
     * All available merge fields grouped by category.
     *
     * @return array<array{group: string, name: string, desc: string, tag: string, sample: string}>
     */
    public static function all(): array
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
     * Sample data for template preview rendering.
     *
     * @return array<string, string>
     */
    public static function sampleData(): array
    {
        $data = [];
        foreach (self::all() as $field) {
            preg_match('/\$(\w+)/', $field['tag'], $m);
            if (isset($m[1])) {
                $data[$m[1]] = $field['sample'];
            }
        }

        return $data;
    }
}
