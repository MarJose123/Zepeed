<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\Export\ExportCompletedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testMarkAllReadMarksAllNotificationsAsRead(): void
    {
        $user = User::factory()->create();

        $this->createNotification($user);
        $this->createNotification($user);

        $response = $this->actingAs($user)->post(route('notifications.read-all'));

        $response->assertRedirect();
        $this->assertSame(0, $user->unreadNotifications()->count());
        $this->assertCount(2, $user->notifications()->whereNotNull('read_at')->get());
    }

    public function testNotificationsPropAndUnreadCountReflectReadStateAfterMarkAllRead(): void
    {
        $user = User::factory()->create();
        $this->createNotification($user);

        $this->actingAs($user)->post(route('notifications.read-all'));

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('auth.user.unread_count', 0)
            ->has('notifications', 1, fn ($notification) => $notification
                ->where('read_at', fn ($readAt) => $readAt !== null)
                ->etc()
            )
        );
    }

    public function testPartialReloadOfNotificationsAndAuthPropsReturnsReadState(): void
    {
        $user = User::factory()->create();
        $this->createNotification($user);

        $this->actingAs($user)->post(route('notifications.read-all'));

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('auth.user.unread_count', 0)
            ->reloadOnly(['auth', 'notifications'], fn ($reload) => $reload
                ->where('auth.user.unread_count', 0)
                ->has('notifications', 1, fn ($notification) => $notification
                    ->where('read_at', fn ($readAt) => $readAt !== null)
                    ->etc()
                )
            )
        );
    }

    public function testMarkAsReadMarksOnlyTheGivenNotificationAsRead(): void
    {
        $user = User::factory()->create();

        $this->createNotification($user);
        $this->createNotification($user);
        $target = $user->notifications()->first();

        $response = $this->actingAs($user)->post(route('notifications.read', $target));

        $response->assertRedirect();
        $this->assertNotNull($target->fresh()->read_at);
        $this->assertSame(1, $user->unreadNotifications()->count());
    }

    public function testMarkAsReadRefreshesNotificationsAndUnreadCountProps(): void
    {
        $user = User::factory()->create();

        $this->createNotification($user);
        $this->createNotification($user);
        $target = $user->notifications()->whereNull('read_at')->first();

        $this->actingAs($user)->post(route('notifications.read', $target));

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('auth.user.unread_count', 1)
            ->where('notifications', fn ($notifications) => $notifications->count() === 2
                && $notifications->where('id', $target->id)->first()['read_at'] !== null
                && $notifications->whereNull('read_at')->count() === 1
            )
        );
    }

    public function testMarkAsReadCannotMarkAnotherUsersNotification(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $notification = $this->createNotification($other);

        $response = $this->actingAs($user)->post(route('notifications.read', $notification));

        $response->assertForbidden();
        $this->assertNull($notification->fresh()->read_at);
    }

    private function createNotification(User $user): DatabaseNotification
    {
        return $user->notifications()->create([
            'id'   => (string) Str::uuid(),
            'type' => ExportCompletedNotification::class,
            'data' => [
                'module_label' => 'Speedtest',
                'format'       => 'csv',
                'row_count'    => 10,
            ],
        ]);
    }
}
