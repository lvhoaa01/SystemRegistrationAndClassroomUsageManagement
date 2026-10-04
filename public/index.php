<?php

declare(strict_types=1);

use App\Bootstrap\App;

$projectRoot = dirname(__DIR__);
$composerAutoload = $projectRoot . '/vendor/autoload.php';

if (is_file($composerAutoload)) {
    require $composerAutoload;
} else {
    spl_autoload_register(static function (string $class) use ($projectRoot): void {
        $prefix = 'App\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }

        $relative = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix)));
        $file = $projectRoot . '/src/' . $relative . '.php';
        if (is_file($file)) {
            require $file;
        }
    });
}

App::boot($projectRoot)->run();

