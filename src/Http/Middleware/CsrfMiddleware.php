<?php

declare(strict_types=1);

namespace App\Http\Middleware;

final class CsrfMiddleware
{
    public function assertValid(?string $token): void
    {
        $expected = $_SESSION['csrf_token'] ?? null;
        if (!is_string($expected) || !is_string($token) || !hash_equals($expected, $token)) {
            throw new \RuntimeException('CSRF_TOKEN_INVALID');
        }
    }
}

