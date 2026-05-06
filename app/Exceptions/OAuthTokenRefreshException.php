<?php

namespace App\Exceptions;

use RuntimeException;

class OAuthTokenRefreshException extends RuntimeException
{
    public function __construct(
        public readonly string $provider,
        string $message = 'Unable to refresh OAuth token.',
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }
}
