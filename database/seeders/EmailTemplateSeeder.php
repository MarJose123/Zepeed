<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'slug'      => 'default-alert',
                'name'      => 'Default alert',
                'subject'   => '⚡ Speedtest alert — {{ $provider_name }} is {{ $status }}',
                'is_system' => true,
                'body'      => implode('', [
                    '<p>Hi,</p>',
                    '<p></p>',
                    '<p>Your speedtest provider ',
                    '<strong><span data-merge-field="{{ $provider_name }}" class="merge-field-pill">{{ $provider_name }}</span></strong>',
                    ' just reported a ',
                    '<strong><span data-merge-field="{{ $status }}" class="merge-field-pill">{{ $status }}</span></strong>',
                    ' result at ',
                    '<span data-merge-field="{{ $measured_at }}" class="merge-field-pill">{{ $measured_at }}</span>',
                    '.</p>',
                    '<p></p>',
                    '<p><strong>Test results:</strong></p>',
                    '<ul>',
                    '<li>Download: <span data-merge-field="{{ $download_mbps }}" class="merge-field-pill">{{ $download_mbps }}</span> Mbps</li>',
                    '<li>Upload: <span data-merge-field="{{ $upload_mbps }}" class="merge-field-pill">{{ $upload_mbps }}</span> Mbps</li>',
                    '<li>Ping: <span data-merge-field="{{ $ping_ms }}" class="merge-field-pill">{{ $ping_ms }}</span> ms</li>',
                    '<li>Jitter: <span data-merge-field="{{ $jitter_ms }}" class="merge-field-pill">{{ $jitter_ms }}</span> ms</li>',
                    '</ul>',
                    // {!! !!} outputs the pre-built anchor tag from buildRenderData() unescaped
                    '<p>View your dashboard: <span data-merge-field="{!! $dashboard_url !!}" class="merge-field-pill">{!! $dashboard_url !!}</span></p>',
                    '<p></p>',
                    '<hr>',
                    '<p></p>',
                    '<p>This alert was sent by Zepeed because a matching alert rule fired.</p>',
                ]),
            ],

            [
                'slug'      => 'speed-threshold-breach',
                'name'      => 'Speed threshold breach',
                'subject'   => '⚠ Speed below threshold — {{ $provider_name }}',
                'is_system' => true,
                'body'      => implode('', [
                    '<p>Hi,</p>',
                    '<p></p>',
                    '<p><span data-merge-field="{{ $provider_name }}" class="merge-field-pill">{{ $provider_name }}</span>',
                    ' reported speeds below your configured threshold at ',
                    '<p></p>',
                    '<span data-merge-field="{{ $measured_at }}" class="merge-field-pill">{{ $measured_at }}</span>.</p>',
                    '<p></p>',
                    '<p><strong>Measured speeds:</strong></p>',
                    '<ul>',
                    '<li>Download: <span data-merge-field="{{ $download_mbps }}" class="merge-field-pill">{{ $download_mbps }}</span> Mbps</li>',
                    '<li>Upload: <span data-merge-field="{{ $upload_mbps }}" class="merge-field-pill">{{ $upload_mbps }}</span> Mbps</li>',
                    '<li>Ping: <span data-merge-field="{{ $ping_ms }}" class="merge-field-pill">{{ $ping_ms }}</span> ms</li>',
                    '</ul>',
                    '<p>Check your full results on your dashboard: <span data-merge-field="{!! $dashboard_url !!}" class="merge-field-pill">{!! $dashboard_url !!}</span></p>',
                    '<p></p>',
                    '<hr>',
                    '<p></p>',
                    '<p>You can adjust your alert thresholds in the alert rules settings.</p>',
                ]),
            ],

            [
                'slug'      => 'provider-failure',
                'name'      => 'Provider failure',
                'subject'   => '🔴 {{ $provider_name }} failed — {{ $measured_at }}',
                'is_system' => true,
                'body'      => implode('', [
                    '<p>Hi,</p>',
                    '<p></p>',
                    '<p>Your speedtest provider ',
                    '<strong><span data-merge-field="{{ $provider_name }}" class="merge-field-pill">{{ $provider_name }}</span></strong>',
                    ' failed to complete a test at ',
                    '<span data-merge-field="{{ $measured_at }}" class="merge-field-pill">{{ $measured_at }}</span>.</p>',
                    '<p></p>',
                    '<p><strong>Failure details:</strong></p>',
                    '<ul>',
                    '<li>Reason: <span data-merge-field="{{ $failure_reason }}" class="merge-field-pill">{{ $failure_reason }}</span></li>',
                    '<li>Message: <span data-merge-field="{{ $failure_message }}" class="merge-field-pill">{{ $failure_message }}</span></li>',
                    '</ul>',
                    '<p>This may indicate a temporary connectivity issue. Zepeed will continue monitoring and alert you of further failures.</p>',
                    '<p></p>',
                    '<p>Dashboard: <span data-merge-field="{!! $dashboard_url !!}" class="merge-field-pill">{!! $dashboard_url !!}</span></p>',
                    '<p></p>',
                    '<hr>',
                    '<p></p>',
                    '<p>Zepeed monitoring</p>',
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
