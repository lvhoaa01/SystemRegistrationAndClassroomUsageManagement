<?php

declare(strict_types=1);

use App\Http\Request;
use App\Http\Response;
use App\Http\Router;

return static function (Router $router): void {
    $router->get('/', static fn (Request $request): Response => Response::html(
        '<!doctype html><html lang="vi"><meta charset="utf-8"><title>NTU Room Booking</title>'
        . '<h1>NTU CNTT Room Booking</h1><p>Khung Modular Monolith đã sẵn sàng.</p></html>'
    ));
};

