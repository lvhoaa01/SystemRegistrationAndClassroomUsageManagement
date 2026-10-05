<?php
// Cải thiện: áp dụng singleton
declare(strict_types=1);

namespace App\Shared\Database;

use App\Config\Env;
use PDO;

final class Connection
{
    public static function create(): PDO
    {
        $host = Env::get('DB_HOST', '127.0.0.1');
        $port = Env::get('DB_PORT', '3306');
        $database = Env::get('DB_DATABASE', 'hotel_booking');
        $username = Env::get('DB_USERNAME', 'root');
        $password = Env::get('DB_PASSWORD', '');

        return new PDO(
            "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4", // DSN
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        );
    }
}
