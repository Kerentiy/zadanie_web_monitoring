<?php

declare(strict_types=1);

namespace App;

/** Application-level result returned by request handlers and rendered by controllers. */
final class ResponseModel
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly array $errors = [],
        public readonly int $status = 200,
        public readonly ?string $redirectTo = null,
    ) {}

    public static function success(string $message, ?string $redirectTo = null): self
    {
        return new self(true, $message, [], 200, $redirectTo);
    }

    public static function failure(string $message, array $errors = [], int $status = 422): self
    {
        return new self(false, $message, $errors, $status);
    }
}
