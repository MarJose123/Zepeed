<?php

namespace App\Notifications\Export;

use App\Models\ExportRequest;
use Illuminate\Notifications\Notification;

class ExportFailedNotification extends Notification
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
            'export_id'       => $this->exportRequest->id,
            'module_label'    => $this->exportRequest->module->label(),
            'failure_message' => $this->exportRequest->failure_message,
        ];
    }
}
