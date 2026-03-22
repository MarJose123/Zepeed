<?php

namespace App\Events\Speedtest\Test;

use App\Models\Provider;
use App\Services\Speedtest\Exceptions\SpeedtestException as SpeedtestExceptionService;
use Override;

class SpeedtestTestExceptionEvent extends SpeedtestTest
{
    public function __construct(
        Provider $provider,
        public readonly SpeedtestExceptionService $exception,
    ) {
        parent::__construct($provider);
    }

    public function broadcastAs(): string
    {
        return 'speedtest.test.exception';
    }

    #[Override]
    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'reason'  => $this->exception->reason->value,
            'message' => $this->exception->getMessage(),
        ]);
    }
}
