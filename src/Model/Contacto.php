<?php
declare(strict_types=1);

namespace Erp2\Model;

use Erp2\Core\Database;

final class Contacto
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function listByTercero(int $terceroId): array
    {
        if ($terceroId <= 0) {
            return [];
        }

        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT * FROM contactos WHERE tercero_id = :id ORDER BY id DESC');
        $stmt->execute([':id' => $terceroId]);

        return $stmt->fetchAll();
    }

    public static function create(int $terceroId, array $data): int
    {
        $pdo = Database::pdo();

        $stmt = $pdo->prepare('
            INSERT INTO contactos (tercero_id, nombres, email, telefono)
            VALUES (:tercero_id, :nombre, :email, :telefono)
        ');

        $stmt->execute([
            ':tercero_id' => $terceroId,
            ':nombres' => (string) ($data['nombres'] ?? ''),
            ':email' => (string) ($data['email'] ?? ''),
            ':telefono' => (string) ($data['telefono'] ?? ''),
        ]);

        return (int) $pdo->lastInsertId();
    }

    public static function delete(int $contactoId, int $terceroId): bool
    {
        if ($contactoId <= 0 || $terceroId <= 0) {
            return false;
        }

        $pdo = Database::pdo();
        $stmt = $pdo->prepare('DELETE FROM contactos WHERE id = :cid AND tercero_id = :tid LIMIT 1');
        $stmt->execute([
            ':cid' => $contactoId,
            ':tid' => $terceroId,
        ]);

        return $stmt->rowCount() > 0;
    }
}