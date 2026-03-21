<?php

namespace App\Services\Speedtest\Exceptions;

use App\Enums\SpeedtestServer;
use RuntimeException;
use Throwable;

class SpeedtestException extends RuntimeException
{
    public function __construct(
        public readonly SpeedtestServer $server,
        public readonly SpeedtestFailureReason $reason,
        string $message = '',
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            message : $message ?: $reason->describe($server),
            previous: $previous,
        );
    }

    public static function timeout(SpeedtestServer $server, int $seconds): self
    {
        return new self(
            server : $server,
            reason : SpeedtestFailureReason::Timeout,
            message: "{$server->label()} timed out after {$seconds}s.",
        );
    }

    public static function nonZeroExit(
        SpeedtestServer $server,
        int $code,
        string $stderr,
    ): self {
        return new self(
            server : $server,
            reason : SpeedtestFailureReason::NonZeroExit,
            message: "{$server->label()} exited with code {$code}: {$stderr}",
        );
    }

    public static function invalidJson(SpeedtestServer $server, string $raw): self
    {
        return new self(
            server : $server,
            reason : SpeedtestFailureReason::InvalidJson,
            message: "{$server->label()} returned unparseable output: ".substr($raw, 0, 200),
        );
    }

    public static function missingField(SpeedtestServer $server, string $field): self
    {
        return new self(
            server : $server,
            reason : SpeedtestFailureReason::MissingField,
            message: "{$server->label()} output missing expected field: {$field}",
        );
    }
}
