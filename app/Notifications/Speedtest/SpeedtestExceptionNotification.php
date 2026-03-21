<?php

namespace App\Notifications\Speedtest;

use App\Models\Provider;
use App\Services\Speedtest\Exceptions\SpeedtestException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SpeedtestExceptionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Provider $provider,
        public readonly SpeedtestException $exception,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['mail'];

        if (config('speedtest.webhook_url')) {
            // TODO: Implement webhook notifications
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject("[Zepeed] {$this->provider->slug->label()} test failed")
            ->greeting('Speed test failed')
            ->line("{$this->provider->slug->label()} could not complete its scheduled run.")
            ->line("**Reason:** {$this->exception->reason->describe($this->provider->slug)}")
            ->line("**Detail:** {$this->exception->getMessage()}")
            ->action('View Results', url('/results'))
            ->line('This alert was triggered because failure notifications are enabled for this provider.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'provider'  => $this->provider->slug->value,
            'reason'    => $this->exception->reason->value,
            'message'   => $this->exception->getMessage(),
            'failed_at' => now()->toIso8601String(),
        ];
    }
}
