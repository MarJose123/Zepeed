<?php

namespace App\Notifications\Export;

use App\Models\ExportRequest;
use Illuminate\Notifications\Notification;

class ExportCompletedNotification extends Notification
{
    public function __construct(
        public readonly ExportRequest $exportRequest,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return [
            'export_id'    => $this->exportRequest->id,
            'module'       => $this->exportRequest->module->value,
            'module_label' => $this->exportRequest->module->label(),
            'format'       => $this->exportRequest->format->value,
            'row_count'    => $this->exportRequest->row_count,
            'download_url' => route('exports.download', $this->exportRequest),
            'expires_at'   => $this->exportRequest->expires_at?->toIso8601String(),
        ];
    }
}
