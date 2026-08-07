<?php

namespace Tests\Feature\Mcp;

use App\Enums\MaintenanceWindowType;
use App\Enums\TokenAbility;
use App\Mcp\Servers\ZepeedServer;
use App\Mcp\Tools\CreateMaintenanceWindow;
use App\Mcp\Tools\DeleteMaintenanceWindow;
use App\Mcp\Tools\ToggleGlobalPause;
use App\Mcp\Tools\UpdateMaintenanceWindow;
use App\Models\MaintenanceWindow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceWindowToolsTest extends TestCase
{
    use ActsAsMcpUser, RefreshDatabase;

    public function testCreateOneTimeMaintenanceWindow(): void
    {
        $user = $this->mcpUser([TokenAbility::MaintenanceCreate->value]);

        $response = ZepeedServer::actingAs($user)
            ->tool(CreateMaintenanceWindow::class, [
                'label'     => 'Deploy window',
                'type'      => 'one_time',
                'providers' => ['ookla'],
                'starts_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'ends_at'   => now()->addDays(2)->format('Y-m-d H:i:s'),
                'notes'     => 'Maintenance',
            ]);

        $response
            ->assertOk()
            ->assertHasNoErrors()
            ->assertStructuredContent(function ($json) {
                $json->where('success', true)
                    ->where('maintenance_window.type', fn ($v) => $v === MaintenanceWindowType::OneTime)
                    ->where('maintenance_window.label', 'Deploy window')
                    ->etc();
            });

        $this->assertDatabaseHas('maintenance_windows', ['label' => 'Deploy window']);
    }

    public function testCreateRecurringMaintenanceWindow(): void
    {
        $user = $this->mcpUser([TokenAbility::MaintenanceCreate->value]);

        $response = ZepeedServer::actingAs($user)
            ->tool(CreateMaintenanceWindow::class, [
                'label'            => 'Nightly window',
                'type'             => 'recurring',
                'providers'        => ['all'],
                'cron_expression'  => '0 2 * * *',
                'duration_minutes' => 60,
            ]);

        $response
            ->assertOk()
            ->assertHasNoErrors()
            ->assertStructuredContent(function ($json) {
                $json->where('maintenance_window.type', fn ($v) => $v === MaintenanceWindowType::Recurring)
                    ->where('maintenance_window.cron_expression', '0 2 * * *')
                    ->etc();
            });
    }

    public function testCreateMaintenanceWindowRejectsOverlappingOneTimeWindow(): void
    {
        $user = $this->mcpUser([TokenAbility::MaintenanceCreate->value]);

        MaintenanceWindow::factory()->create([
            'type'      => MaintenanceWindowType::OneTime,
            'is_active' => true,
            'providers' => ['ookla'],
            'starts_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'ends_at'   => now()->addDays(3)->format('Y-m-d H:i:s'),
        ]);

        $response = ZepeedServer::actingAs($user)
            ->tool(CreateMaintenanceWindow::class, [
                'label'     => 'Overlapping window',
                'type'      => 'one_time',
                'providers' => ['ookla'],
                'starts_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'ends_at'   => now()->addDays(4)->format('Y-m-d H:i:s'),
            ]);

        $response->assertHasErrors();
        $this->assertDatabaseMissing('maintenance_windows', ['label' => 'Overlapping window']);
    }

    public function testUpdateMaintenanceWindow(): void
    {
        $user = $this->mcpUser([TokenAbility::MaintenanceUpdate->value]);
        $window = MaintenanceWindow::factory()->create([
            'type'      => MaintenanceWindowType::Indefinite,
            'providers' => ['all'],
            'is_active' => true,
        ]);

        $response = ZepeedServer::actingAs($user)
            ->tool(UpdateMaintenanceWindow::class, [
                'id'        => $window->id,
                'label'     => 'Renamed window',
                'type'      => 'indefinite',
                'providers' => ['all'],
                'is_active' => false,
            ]);

        $response
            ->assertOk()
            ->assertHasNoErrors()
            ->assertStructuredContent(function ($json) {
                $json->where('maintenance_window.label', 'Renamed window')
                    ->etc();
            });

        $this->assertSame('Renamed window', $window->refresh()->label);
        $this->assertFalse($window->refresh()->is_active);
    }

    public function testDeleteMaintenanceWindow(): void
    {
        $user = $this->mcpUser([TokenAbility::MaintenanceDelete->value]);
        $window = MaintenanceWindow::factory()->create();

        $response = ZepeedServer::actingAs($user)
            ->tool(DeleteMaintenanceWindow::class, ['id' => $window->id]);

        $response
            ->assertOk()
            ->assertHasNoErrors()
            ->assertStructuredContent(function ($json) {
                $json->where('success', true)
                    ->etc();
            });

        $this->assertDatabaseMissing('maintenance_windows', ['id' => $window->id]);
    }

    public function testToggleGlobalPauseActivatesAndDeactivates(): void
    {
        $user = $this->mcpUser([TokenAbility::MaintenanceUpdate->value]);

        $response = ZepeedServer::actingAs($user)
            ->tool(ToggleGlobalPause::class);

        $response
            ->assertOk()
            ->assertHasNoErrors()
            ->assertStructuredContent(function ($json) {
                $json->where('is_paused', true)
                    ->etc();
            });

        $this->assertTrue(
            MaintenanceWindow::query()
                ->ofType(MaintenanceWindowType::Indefinite)
                ->whereJsonContains('providers', 'all')
                ->first()
                ->is_active
        );

        $response = ZepeedServer::actingAs($user)
            ->tool(ToggleGlobalPause::class);

        $response
            ->assertOk()
            ->assertHasNoErrors()
            ->assertStructuredContent(function ($json) {
                $json->where('is_paused', false)
                    ->etc();
            });
    }
}
