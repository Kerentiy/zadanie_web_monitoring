<?php

declare(strict_types=1);

namespace App;

final class Request
{
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query = [],
        public readonly array $post = [],
    ) {}

    public static function fromGlobals(): self
    {
        $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        return new self($method, rtrim($path, '/') ?: '/', $_GET, $_POST);
    }
}
