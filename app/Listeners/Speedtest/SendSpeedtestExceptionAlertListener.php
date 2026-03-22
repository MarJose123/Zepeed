<?php

namespace App\Listeners\Speedtest;

use App\Events\Speedtest\SpeedtestExceptionEvent;
use App\Models\User;
use App\Notifications\Speedtest\SpeedtestExceptionNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendSpeedtestExceptionAlertListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Only queue this listener if alert_on_failure is enabled for the provider.
     */
    public function shouldQueue(SpeedtestExceptionEvent $event): bool
    {
        return $event->provider->alert_on_failure;
    }

    /**
     * Notify every user via database + email in one pass.
     * The notification class decides which channels each user gets.
     */
    public function handle(SpeedtestExceptionEvent $event): void
    {
        $notification = new SpeedtestExceptionNotification(
            provider: $event->provider,
            exception: $event->exception,
        );

        User::query()->each(fn (User $user) => $user->notify($notification));
    }
}
