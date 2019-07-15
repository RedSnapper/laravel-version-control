<?php

namespace Redsnapper\LaravelVersionControl\Exceptions;

use RuntimeException;
use Throwable;

class VersionControlException extends RuntimeException
{
    /**
     * {@inheritDoc}
     */
    public function __construct(int $code = 0, Throwable $previous = null)
    {
        $message = 'You cant save that information, its in violation of version control.';
        parent::__construct($message, $code, $previous);
    }
}
