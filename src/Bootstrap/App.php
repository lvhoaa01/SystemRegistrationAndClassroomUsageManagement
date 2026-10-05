<?php

declare(strict_types=1);

namespace App\Bootstrap;

use App\Http\Request;
use App\Http\Response;
use App\Http\Router;

final class App
{
    private function __construct(
        private readonly string $projectRoot,
        private readonly Router $router,
    ) {
    }

    public static function boot(string $projectRoot): self
    {
        date_default_timezone_set('UTC');

        $router = new Router();
        $registerWebRoutes = require $projectRoot . '/src/routes/web.php';
        $registerWebRoutes($router);

        return new self($projectRoot, $router);
    }

    public function run(): void
    {
        try {
            $response = $this->router->dispatch(Request::fromGlobals());
        } catch (\Throwable $exception) {
            $response = Response::json([
                'error' => 'INTERNAL_ERROR',
                'message' => 'Ứng dụng chưa thể xử lý yêu cầu.',
            ], 500);
        }

        $response->send();
    }
}
