<?php
declare(strict_types=1);

namespace Erp2\Controller;

use Erp2\Core\Database;

final class HealthController
{
    public function index(): void
    {
        // Si aún no has creado la DB, comenta estas 2 líneas
        $pdo = Database::pdo();
        $pdo->query('SELECT 1');

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    }
}