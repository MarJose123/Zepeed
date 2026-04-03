<?php

namespace App\Observers;

use App\Models\Webhook;

class WebhooksObserver
{
    /**
     * Handle the "deleted" event.
     *
     * @param Webhook $webhook
     */
    public function deleted(Webhook $webhook): void
    {
        // Delete all the webhook delivery records.
        $webhook->deliveries()->delete();
    }
}
