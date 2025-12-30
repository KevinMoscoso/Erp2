<?php
declare(strict_types=1);

namespace Erp2\Core;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $host = (string)($_ENV['DB_HOST'] ?? '127.0.0.1');
        $port = (string)($_ENV['DB_PORT'] ?? '3306');
        $db   = (string)($_ENV['DB_NAME'] ?? '');
        $user = (string)($_ENV['DB_USER'] ?? '');
        $pass = (string)($_ENV['DB_PASS'] ?? '');

        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

        try {
            self::$pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo "DB connection error";
            if ((int)($_ENV['APP_DEBUG'] ?? 0) === 1) {
                echo "<pre>" . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</pre>";
            }
            exit;
        }

        return self::$pdo;
    }
}