<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a notification cannot be delivered to an Apprise API server.
 *
 * Messages are sanitized: credentials are never included, and the target is
 * referenced by host only so a misconfigured URL never leaks secrets.
 */
class AppriseException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $statusCode = null,
    ) {
        parent::__construct($message);
    }
}
