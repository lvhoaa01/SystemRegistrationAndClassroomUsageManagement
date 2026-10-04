<?php

declare(strict_types=1);

namespace App\Shared\Clock;

use DateTimeImmutable;
use DateTimeZone;

final class Clock
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
    }
}

