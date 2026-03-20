<?php

namespace App\Services;

use App\Enums\InertiaNotificationType;
use Exception;
use Illuminate\Http\Request;
use Inertia\Inertia;

final class InertiaNotification
{
    protected InertiaNotificationType|string $type;

    protected string $message;

    protected ?string $title = null;

    public function __construct(protected Request $request, protected ?string $key) {}

    public static function make(?string $name = null): InertiaNotification
    {
        return resolve(InertiaNotification::class, [
            'key' => $name ?? 'notification',
        ]);
    }

    public function success(): self
    {
        $this->type = InertiaNotificationType::Success;

        return $this;
    }

    public function error(): self
    {
        $this->type = InertiaNotificationType::Error;

        return $this;
    }

    public function info(): self
    {
        $this->type = InertiaNotificationType::Info;

        return $this;
    }

    public function warning(): self
    {
        $this->type = InertiaNotificationType::Warning;

        return $this;
    }

    public function message(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function title(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function type(InertiaNotificationType|string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @throws Exception
     */
    public function send(): void
    {

        if (! $this->message) {
            throw new Exception('Notification message is required.');
        }

        Inertia::flash($this->key, [
            'type'    => $this->type,
            'title'   => $this->title,
            'message' => $this->message,
        ]);
    }
}
