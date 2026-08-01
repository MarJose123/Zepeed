<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ExportFormat;
use App\Enums\ExportModule;
use App\Enums\ExportStatus;
use App\Enums\SpeedtestServer;
use App\Jobs\GeneratePingResultExportJob;
use App\Jobs\GenerateSpeedResultExportJob;
use App\Models\ExportRequest;
use App\Models\PingTarget;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that an authenticated user can queue a speedtest export.
     */
    public function testAuthenticatedUserCanQueueSpeedtestExport(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        Provider::factory()->withSlug(SpeedtestServer::Ookla)->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->postJson('/api/v1/exports', [
                'module'    => 'speed_download',
                'format'    => 'csv',
                'provider'  => 'ookla',
                'date_from' => now()->subDays(7)->format('Y-m-d'),
                'date_to'   => now()->format('Y-m-d'),
            ]);

        $response->assertStatus(202)
            ->assertJsonPath('success', true)
            ->assertJsonPath('code', 202)
            ->assertJsonPath('data.module', 'speed_download')
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('export_requests', [
            'user_id' => $user->id,
            'module'  => ExportModule::SpeedDownload->value,
            'format'  => ExportFormat::Csv->value,
            'status'  => ExportStatus::Pending->value,
        ]);

        Queue::assertPushed(GenerateSpeedResultExportJob::class);
        Queue::assertNotPushed(GeneratePingResultExportJob::class);
    }

    /**
     * Test that an authenticated user can queue a ping export.
     */
    public function testAuthenticatedUserCanQueuePingExport(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $target = PingTarget::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->postJson('/api/v1/exports', [
                'module'    => 'ping_result',
                'format'    => 'json',
                'target'    => $target->id,
                'date_from' => now()->subDays(7)->format('Y-m-d'),
                'date_to'   => now()->format('Y-m-d'),
            ]);

        $response->assertStatus(202)
            ->assertJsonPath('data.module', 'ping_result');

        Queue::assertPushed(GeneratePingResultExportJob::class);
        Queue::assertNotPushed(GenerateSpeedResultExportJob::class);
    }

    /**
     * Test that export creation validates the date range.
     */
    public function testExportCreationValidatesDateRange(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->postJson('/api/v1/exports', [
                'module'    => 'speed_download',
                'format'    => 'csv',
                'date_from' => now()->format('Y-m-d'),
                'date_to'   => now()->subDay()->format('Y-m-d'),
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['date_to']]);
    }

    /**
     * Test that the provider filter is rejected for ping exports.
     */
    public function testProviderFilterIsRejectedForPingExports(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        Provider::factory()->withSlug(SpeedtestServer::Ookla)->create();

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->postJson('/api/v1/exports', [
                'module'    => 'ping_result',
                'format'    => 'csv',
                'provider'  => 'ookla',
                'date_from' => now()->subDay()->format('Y-m-d'),
                'date_to'   => now()->format('Y-m-d'),
            ]);

        $response->assertUnprocessable()
            ->assertJsonStructure(['errors' => ['provider']]);
    }

    /**
     * Test that an authenticated user only sees their own exports.
     */
    public function testUserOnlySeesTheirOwnExports(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');
        $otherUser = User::factory()->create();

        ExportRequest::factory()->count(2)->create(['user_id' => $user->id]);
        ExportRequest::factory()->count(3)->create(['user_id' => $otherUser->id]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/exports');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'module',
                        'format',
                        'status',
                        'filters',
                        'download_url',
                        'created_at',
                    ],
                ],
                'meta',
            ]);

        $this->assertEquals(2, $response['meta']['total']);
    }

    /**
     * Test that an authenticated user can view one of their exports.
     */
    public function testUserCanViewTheirExport(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $export = ExportRequest::factory()->completed()->create(['user_id' => $user->id]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson("/api/v1/exports/{$export->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $export->id)
            ->assertJsonPath('data.status', 'completed');
    }

    /**
     * Test that a user cannot view another user's export.
     */
    public function testUserCannotViewAnotherUsersExport(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');
        $otherUser = User::factory()->create();

        $export = ExportRequest::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson("/api/v1/exports/{$export->id}");

        $response->assertNotFound()
            ->assertJsonPath('success', false);
    }

    /**
     * Test that a completed export can be downloaded.
     */
    public function testCompletedExportCanBeDownloaded(): void
    {
        Storage::disk('local')->put('exports/test-export.csv', "id,provider\n1,ookla\n");

        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $export = ExportRequest::factory()->create([
            'user_id'    => $user->id,
            'module'     => ExportModule::SpeedDownload,
            'format'     => ExportFormat::Csv,
            'status'     => ExportStatus::Completed,
            'file_path'  => 'exports/test-export.csv',
            'expires_at' => now()->addDay(),
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson("/api/v1/exports/{$export->id}/download");

        $response->assertOk();
        $this->assertStringContainsString('id,provider', $response->streamedContent());

        Storage::disk('local')->delete('exports/test-export.csv');
    }

    /**
     * Test that a pending export cannot be downloaded.
     */
    public function testPendingExportCannotBeDownloaded(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $export = ExportRequest::factory()->create(['user_id' => $user->id]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson("/api/v1/exports/{$export->id}/download");

        $response->assertNotFound()
            ->assertJsonPath('success', false);
    }

    /**
     * Test that an expired export cannot be downloaded.
     */
    public function testExpiredExportCannotBeDownloaded(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $export = ExportRequest::factory()->create([
            'user_id'    => $user->id,
            'status'     => ExportStatus::Completed,
            'file_path'  => 'exports/stale.csv',
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson("/api/v1/exports/{$export->id}/download");

        $response->assertGone()
            ->assertJsonPath('success', false);
    }

    /**
     * Test that exports can be filtered by status.
     */
    public function testExportsCanBeFilteredByStatus(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        ExportRequest::factory()->count(2)->create(['user_id' => $user->id, 'status' => ExportStatus::Completed]);
        ExportRequest::factory()->count(1)->create(['user_id' => $user->id, 'status' => ExportStatus::Failed]);

        $response = $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
            ->getJson('/api/v1/exports?status=completed');

        $response->assertOk();
        $this->assertEquals(2, $response['meta']['total']);
    }

    /**
     * Test that unauthenticated request returns 401.
     */
    public function testUnauthenticatedRequestReturns401(): void
    {
        $response = $this->getJson('/api/v1/exports');

        $response->assertUnauthorized();
    }
}
