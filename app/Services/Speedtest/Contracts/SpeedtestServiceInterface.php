<?php

namespace App\Services\Speedtest\Contracts;

use App\Models\Provider;
use App\Services\Speedtest\Data\SpeedtestResult;
use App\Services\Speedtest\Exceptions\SpeedtestException;

interface SpeedtestServiceInterface
{
    /**
     * Execute the speed test and return a normalised result.
     *
     * @throws SpeedtestException On timeout, non-zero exit,
     *                            invalid JSON, or missing fields.
     */
    public function run(): SpeedtestResult;

    /**
     * The Provider model this service is configured against.
     */
    public function provider(): Provider;

    /**
     * Process execution timeout in seconds.
     */
    public function timeout(): int;

    /**
     * Maximum run attempts before the test is marked failed.
     * Enforced as exactly 2 — initial attempt + one retry.
     */
    public function maxAttempts(): int;
}
