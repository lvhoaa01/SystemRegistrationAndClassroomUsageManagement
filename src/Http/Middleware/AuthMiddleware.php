<?php

declare(strict_types=1);

namespace App\Http\Middleware;

final class AuthMiddleware
{
    public function assertAuthenticated(): void
    {
        if (empty($_SESSION['user_id'])) {
            throw new \RuntimeException('AUTHENTICATION_REQUIRED');
        } 
    }
}

