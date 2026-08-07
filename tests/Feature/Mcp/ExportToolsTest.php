<?php

namespace Tests\Feature\Mcp;

use App\Enums\ExportFormat;
use App\Enums\ExportModule;
use App\Enums\ExportStatus;
use App\Enums\TokenAbility;
use App\Jobs\GeneratePingResultExportJob;
use App\Jobs\GenerateSpeedResultExportJob;
use App\Mcp\Servers\ZepeedServer;
use App\Mcp\Tools\CreateExport;
use App\Mcp\Tools\GetExport;
use App\Mcp\Tools\ListExports;
use App\Models\ExportRequest;
use App\Models\PingTarget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExportToolsTest extends TestCase
{
    use ActsAsMcpUser, RefreshDatabase;

    public function testCreateSpeedtestExportQueuesJob(): void
    {
        Queue::fake();

        $user = $this->mcpUser([TokenAbility::ExportsCreate->value]);

        $response = ZepeedServer::actingAs($user)
            ->tool(CreateExport::class, [
                'module'    => 'speed_download',
                'format'    => 'csv',
                'date_from' => '2025-01-01',
                'date_to'   => '2025-01-31',
            ]);

        $response
            ->assertOk()
            ->assertHasNoErrors()
            ->assertStructuredContent(function ($json) {
                $json->where('success', true)
                    ->where('export.module', fn ($v) => $v === ExportModule::SpeedDownload)
                    ->where('export.status', fn ($v) => $v === ExportStatus::Pending)
                    ->etc();
            });

        Queue::assertPushed(GenerateSpeedResultExportJob::class);
        Queue::assertNotPushed(GeneratePingResultExportJob::class);
    }

    public function testCreatePingExportQueuesJob(): void
    {
        Queue::fake();

        $user = $this->mcpUser([TokenAbility::ExportsCreate->value]);
        $target = PingTarget::factory()->create();

        $response = ZepeedServer::actingAs($user)
            ->tool(CreateExport::class, [
                'module'    => 'ping_result',
                'format'    => 'json',
                'target'    => $target->id,
                'date_from' => '2025-01-01',
                'date_to'   => '2025-01-31',
            ]);

        $response
            ->assertOk()
            ->assertHasNoErrors()
            ->assertStructuredContent(function ($json) {
                $json->where('export.module', fn ($v) => $v === ExportModule::PingResult)
                    ->etc();
            });

        Queue::assertPushed(GeneratePingResultExportJob::class);
    }

    public function testListExportsIsScopedToAuthenticatedUser(): void
    {
        $user = $this->mcpUser([TokenAbility::ExportsView->value]);
        $other = User::factory()->create();

        ExportRequest::factory()->count(2)->create(['user_id' => $user->id]);
        ExportRequest::factory()->create(['user_id' => $other->id]);

        $response = ZepeedServer::actingAs($user)
            ->tool(ListExports::class, ['per_page' => 10, 'page' => 1]);

        $response
            ->assertOk()
            ->assertHasNoErrors()
            ->assertStructuredContent(function ($json) {
                $json->has('data', 2)
                    ->where('pagination.total', 2)
                    ->etc();
            });
    }

    public function testGetExportReturnsOwnedExport(): void
    {
        $user = $this->mcpUser([TokenAbility::ExportsView->value]);
        $export = ExportRequest::factory()->create([
            'user_id' => $user->id,
            'status'  => ExportStatus::Completed,
        ]);

        $response = ZepeedServer::actingAs($user)
            ->tool(GetExport::class, ['id' => $export->id]);

        $response
            ->assertOk()
            ->assertHasNoErrors()
            ->assertStructuredContent(function ($json) use ($export) {
                $json->where('export.id', $export->id)
                    ->etc();
            });
    }

    public function testGetExportHidesOtherUsersExport(): void
    {
        $user = $this->mcpUser([TokenAbility::ExportsView->value]);
        $other = User::factory()->create();
        $export = ExportRequest::factory()->create(['user_id' => $other->id]);

        $response = ZepeedServer::actingAs($user)
            ->tool(GetExport::class, ['id' => $export->id]);

        $response->assertHasErrors();
    }

    public function testGetExportRejectsExpiredExport(): void
    {
        $user = $this->mcpUser([TokenAbility::ExportsView->value]);
        $export = ExportRequest::factory()->create([
            'user_id'    => $user->id,
            'status'     => ExportStatus::Completed,
            'expires_at' => now()->subDay(),
        ]);

        $response = ZepeedServer::actingAs($user)
            ->tool(GetExport::class, ['id' => $export->id]);

        $response->assertHasErrors();
    }

    public function testGetExportIncludesCsvContentWhenRequested(): void
    {
        Storage::disk('local')->put('exports/test-export.csv', "id,provider\n1,ookla\n");

        $user = $this->mcpUser([TokenAbility::ExportsView->value]);
        $export = ExportRequest::factory()->create([
            'user_id'    => $user->id,
            'module'     => ExportModule::SpeedDownload,
            'format'     => ExportFormat::Csv,
            'status'     => ExportStatus::Completed,
            'file_path'  => 'exports/test-export.csv',
            'expires_at' => now()->addDay(),
        ]);

        $response = ZepeedServer::actingAs($user)
            ->tool(GetExport::class, [
                'id'              => $export->id,
                'include_content' => true,
            ]);

        $response
            ->assertOk()
            ->assertHasNoErrors()
            ->assertStructuredContent(function ($json) {
                $json->where('content', "id,provider\n1,ookla\n")
                    ->where('truncated', false)
                    ->etc();
            });

        Storage::disk('local')->delete('exports/test-export.csv');
    }
}
