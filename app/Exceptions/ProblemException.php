<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A domain error the client can act on, rendered as an RFC 9457 problem by ProblemDetailsRenderer.
 *
 * Each subclass is one problem type: a stable "type" URI, a fixed "title" and an HTTP status.
 * The exception message becomes the "detail" of this occurrence.
 */
abstract class ProblemException extends RuntimeException
{
    abstract public function type(): string;

    abstract public function title(): string;

    abstract public function status(): int;

    /**
     * Extension members for this problem type (RFC 9457, section 3.2).
     *
     * @return array<string, mixed>
     */
    public function extensions(): array
    {
        return [];
    }
}
