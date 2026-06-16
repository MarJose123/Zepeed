<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class PingEmailTemplateSeeder extends Seeder
{
    /**
     * Seed the default system email templates for ping alert rules.
     *
     * All templates use `template_type = 'ping'` and `is_system = true`.
     * Merge fields are wrapped in Tiptap-compatible `data-merge-field` spans
     * to match the editor pill format used by the existing speedtest templates.
     */
    public function run(): void
    {
        $templates = [
            // ── 1. Generic ping alert ─────────────────────────────────────
            [
                'slug'          => 'ping-default-alert',
                'name'          => 'Ping — default alert',
                'template_type' => 'ping',
                'is_system'     => true,
                'subject'       => '🔔 Ping alert — {{ $target_label }} is {{ $status }}',
                'body'          => implode('', [
                    '<p>Hi,</p>',
                    '<p></p>',
                    '<p>A ping alert rule fired for target ',
                    '<strong><span data-merge-field="{{ $target_label }}" class="merge-field-pill">{{ $target_label }}</span></strong>',
                    ' (<span data-merge-field="{{ $target_host }}" class="merge-field-pill">{{ $target_host }}</span>)',
                    ' with status ',
                    '<strong><span data-merge-field="{{ $status }}" class="merge-field-pill">{{ $status }}</span></strong>',
                    ' at ',
                    '<span data-merge-field="{{ $triggered_at }}" class="merge-field-pill">{{ $triggered_at }}</span>',
                    '.</p>',
                    '<p></p>',
                    '<p><strong>Ping results:</strong></p>',
                    '<ul>',
                    '<li>Packets sent: <span data-merge-field="{{ $packets_sent }}" class="merge-field-pill">{{ $packets_sent }}</span></li>',
                    '<li>Packets received: <span data-merge-field="{{ $packets_received }}" class="merge-field-pill">{{ $packets_received }}</span></li>',
                    '<li>Packet loss: <span data-merge-field="{{ $packet_loss_percent }}" class="merge-field-pill">{{ $packet_loss_percent }}</span>%</li>',
                    '<li>Min / Avg / Max: <span data-merge-field="{{ $min_ms }}" class="merge-field-pill">{{ $min_ms }}</span> / <span data-merge-field="{{ $avg_ms }}" class="merge-field-pill">{{ $avg_ms }}</span> / <span data-merge-field="{{ $max_ms }}" class="merge-field-pill">{{ $max_ms }}</span> ms</li>',
                    '</ul>',
                    '<p></p>',
                    '<p>View your dashboard: <span data-merge-field="{!! $dashboard_url !!}" class="merge-field-pill">{!! $dashboard_url !!}</span></p>',
                    '<p></p>',
                    '<hr>',
                    '<p></p>',
                    '<p>This alert was sent by Zepeed because a matching ping alert rule fired.</p>',
                ]),
            ],

            // ── 2. Target unreachable ─────────────────────────────────────
            [
                'slug'          => 'ping-target-unreachable',
                'name'          => 'Ping — target unreachable',
                'template_type' => 'ping',
                'is_system'     => true,
                'subject'       => '🔴 {{ $target_label }} is unreachable — {{ $triggered_at }}',
                'body'          => implode('', [
                    '<p>Hi,</p>',
                    '<p></p>',
                    '<p>Zepeed could not reach ',
                    '<strong><span data-merge-field="{{ $target_label }}" class="merge-field-pill">{{ $target_label }}</span></strong>',
                    ' (<span data-merge-field="{{ $target_host }}" class="merge-field-pill">{{ $target_host }}</span>)',
                    ' at ',
                    '<span data-merge-field="{{ $triggered_at }}" class="merge-field-pill">{{ $triggered_at }}</span>',
                    '.</p>',
                    '<p></p>',
                    '<p><strong>Failure details:</strong></p>',
                    '<ul>',
                    '<li>Status: <span data-merge-field="{{ $status }}" class="merge-field-pill">{{ $status }}</span></li>',
                    '<li>Failure reason: <span data-merge-field="{{ $failure_reason }}" class="merge-field-pill">{{ $failure_reason }}</span></li>',
                    '<li>Packet loss: <span data-merge-field="{{ $packet_loss_percent }}" class="merge-field-pill">{{ $packet_loss_percent }}</span>%</li>',
                    '<li>Packets sent: <span data-merge-field="{{ $packets_sent }}" class="merge-field-pill">{{ $packets_sent }}</span></li>',
                    '<li>Packets received: <span data-merge-field="{{ $packets_received }}" class="merge-field-pill">{{ $packets_received }}</span></li>',
                    '</ul>',
                    '<p></p>',
                    '<p>This may indicate a network outage, firewall block, or host failure. Zepeed will continue monitoring and notify you when connectivity is restored.</p>',
                    '<p></p>',
                    '<p>Dashboard: <span data-merge-field="{!! $dashboard_url !!}" class="merge-field-pill">{!! $dashboard_url !!}</span></p>',
                    '<p></p>',
                    '<hr>',
                    '<p></p>',
                    '<p>Zepeed monitoring — ping alert rule: <span data-merge-field="{{ $rule_name }}" class="merge-field-pill">{{ $rule_name }}</span></p>',
                ]),
            ],

            // ── 3. High latency ───────────────────────────────────────────
            [
                'slug'          => 'ping-high-latency',
                'name'          => 'Ping — high latency',
                'template_type' => 'ping',
                'is_system'     => true,
                'subject'       => '⚠ High latency detected — {{ $target_label }} avg {{ $avg_ms }} ms',
                'body'          => implode('', [
                    '<p>Hi,</p>',
                    '<p></p>',
                    '<p>Zepeed detected high latency for target ',
                    '<strong><span data-merge-field="{{ $target_label }}" class="merge-field-pill">{{ $target_label }}</span></strong>',
                    ' (<span data-merge-field="{{ $target_host }}" class="merge-field-pill">{{ $target_host }}</span>)',
                    ' at ',
                    '<span data-merge-field="{{ $triggered_at }}" class="merge-field-pill">{{ $triggered_at }}</span>',
                    '.</p>',
                    '<p></p>',
                    '<p><strong>Latency breakdown:</strong></p>',
                    '<ul>',
                    '<li>Min: <span data-merge-field="{{ $min_ms }}" class="merge-field-pill">{{ $min_ms }}</span> ms</li>',
                    '<li>Avg: <span data-merge-field="{{ $avg_ms }}" class="merge-field-pill">{{ $avg_ms }}</span> ms</li>',
                    '<li>Max: <span data-merge-field="{{ $max_ms }}" class="merge-field-pill">{{ $max_ms }}</span> ms</li>',
                    '<li>Std deviation: <span data-merge-field="{{ $stddev_ms }}" class="merge-field-pill">{{ $stddev_ms }}</span> ms</li>',
                    '</ul>',
                    '<p></p>',
                    '<p><strong>Packet info:</strong></p>',
                    '<ul>',
                    '<li>Packet loss: <span data-merge-field="{{ $packet_loss_percent }}" class="merge-field-pill">{{ $packet_loss_percent }}</span>%</li>',
                    '<li>Packets sent / received: <span data-merge-field="{{ $packets_sent }}" class="merge-field-pill">{{ $packets_sent }}</span> / <span data-merge-field="{{ $packets_received }}" class="merge-field-pill">{{ $packets_received }}</span></li>',
                    '</ul>',
                    '<p></p>',
                    '<p>You may want to investigate network congestion or routing issues to this host.</p>',
                    '<p></p>',
                    '<p>Dashboard: <span data-merge-field="{!! $dashboard_url !!}" class="merge-field-pill">{!! $dashboard_url !!}</span></p>',
                    '<p></p>',
                    '<hr>',
                    '<p></p>',
                    '<p>Zepeed monitoring — ping alert rule: <span data-merge-field="{{ $rule_name }}" class="merge-field-pill">{{ $rule_name }}</span></p>',
                ]),
            ],

            // ── 4. Packet loss detected ───────────────────────────────────
            [
                'slug'          => 'ping-packet-loss',
                'name'          => 'Ping — packet loss detected',
                'template_type' => 'ping',
                'is_system'     => true,
                'subject'       => '📉 Packet loss on {{ $target_label }} — {{ $packet_loss_percent }}% lost',
                'body'          => implode('', [
                    '<p>Hi,</p>',
                    '<p></p>',
                    '<p>Packet loss was detected for target ',
                    '<strong><span data-merge-field="{{ $target_label }}" class="merge-field-pill">{{ $target_label }}</span></strong>',
                    ' (<span data-merge-field="{{ $target_host }}" class="merge-field-pill">{{ $target_host }}</span>)',
                    ' at ',
                    '<span data-merge-field="{{ $triggered_at }}" class="merge-field-pill">{{ $triggered_at }}</span>',
                    '.</p>',
                    '<p></p>',
                    '<p><strong>Packet statistics:</strong></p>',
                    '<ul>',
                    '<li>Packets sent: <span data-merge-field="{{ $packets_sent }}" class="merge-field-pill">{{ $packets_sent }}</span></li>',
                    '<li>Packets received: <span data-merge-field="{{ $packets_received }}" class="merge-field-pill">{{ $packets_received }}</span></li>',
                    '<li>Packet loss: <span data-merge-field="{{ $packet_loss_percent }}" class="merge-field-pill">{{ $packet_loss_percent }}</span>%</li>',
                    '</ul>',
                    '<p></p>',
                    '<p><strong>Latency (received packets):</strong></p>',
                    '<ul>',
                    '<li>Min / Avg / Max: <span data-merge-field="{{ $min_ms }}" class="merge-field-pill">{{ $min_ms }}</span> / <span data-merge-field="{{ $avg_ms }}" class="merge-field-pill">{{ $avg_ms }}</span> / <span data-merge-field="{{ $max_ms }}" class="merge-field-pill">{{ $max_ms }}</span> ms</li>',
                    '</ul>',
                    '<p></p>',
                    '<p>Intermittent packet loss can indicate a degraded network path, hardware fault, or ISP instability. Zepeed will keep monitoring this target.</p>',
                    '<p></p>',
                    '<p>Dashboard: <span data-merge-field="{!! $dashboard_url !!}" class="merge-field-pill">{!! $dashboard_url !!}</span></p>',
                    '<p></p>',
                    '<hr>',
                    '<p></p>',
                    '<p>Zepeed monitoring — ping alert rule: <span data-merge-field="{{ $rule_name }}" class="merge-field-pill">{{ $rule_name }}</span></p>',
                ]),
            ],
        ];

        foreach ($templates as $template) {
            EmailTemplate::query()->updateOrCreate(
                ['slug' => $template['slug']],
                $template,
            );
        }
    }
}
