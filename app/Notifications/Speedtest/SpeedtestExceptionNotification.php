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

    /**
     * Email channel for users with a verified email.
     * Database channel for all users.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (filled($notifiable->email) && filled($notifiable->email_verified_at)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject("[Zepeed] {$this->provider->slug->label()} speedtest failed")
            ->greeting("Speedtest Exception — {$this->provider->slug->label()}")
            ->line('A speedtest run encountered an exception and could not complete.')
            ->line('**Reason:** '.$this->exception->reason->value)
            ->line('**Message:** '.$this->exception->getMessage())
            ->line('**Provider:** '.$this->provider->slug->label())
            ->line('**Time:** '.now()->toDateTimeString())
            ->action('View Results', url(route('dashboard')))
            ->line('You are receiving this because alert on failure is enabled for this provider.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'provider_slug'  => $this->provider->slug->value,
            'provider_name'  => $this->provider->slug->label(),
            'reason'         => $this->exception->reason->value,
            'message'        => $this->exception->getMessage(),
            'occurred_at'    => now()->toIso8601String(),
        ];
    }

    /**
     * Prevent duplicate notifications — one per provider per minute.
     */
    public function uniqueId(): string
    {
        return $this->provider->slug->value.':'.now()->format('Y-m-d-H-i');
    }
}
