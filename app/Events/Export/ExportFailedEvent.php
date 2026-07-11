<?php

namespace App\Events\Export;

use App\Models\ExportRequest;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExportFailedEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly ExportRequest $exportRequest,
    ) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel("exports.{$this->exportRequest->user_id}");
    }

    public function broadcastAs(): string
    {
        return 'export.failed';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'export_id'       => $this->exportRequest->id,
            'module_label'    => $this->exportRequest->module->label(),
            'failure_message' => $this->exportRequest->failure_message,
        ];
    }
}
