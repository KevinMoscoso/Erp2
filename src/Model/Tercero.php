<?php
declare(strict_types=1);

namespace Erp2\Model;

use Erp2\Core\Database;
use PDO;

final class Tercero
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function search(string $q): array
    {
        $q = trim($q);

        $pdo = Database::pdo();

        if ($q === '') {
            $stmt = $pdo->prepare('SELECT * FROM terceros WHERE estado = 1 ORDER BY id DESC LIMIT 200');
            $stmt->execute();
            return $stmt->fetchAll();
        }

        $like = '%' . $q . '%';
        $stmt = $pdo->prepare('
            SELECT *
            FROM terceros
            WHERE estado = 1
              AND (
                nombre_comercial LIKE :q
                OR identificacion LIKE :q
                OR email LIKE :q
              )
            ORDER BY id DESC
            LIMIT 200
        ');
        $stmt->execute([':q' => $like]);

        return $stmt->fetchAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT * FROM terceros WHERE id = :id AND estado = 1 LIMIT 1');
        $stmt->execute([':id' => $id]);

        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public static function create(array $data): int
    {
        $pdo = Database::pdo();

        $stmt = $pdo->prepare('
            INSERT INTO terceros (tipo, nombre_comercial, identificacion, email, estado)
            VALUES (:tipo, :nombre_comercial, :identificacion, :email, 1)
        ');

        $stmt->execute([
            ':tipo' => (string) ($data['tipo'] ?? ''),
            ':nombre_comercial' => (string) ($data['nombre_comercial'] ?? ''),
            ':identificacion' => (string) ($data['identificacion'] ?? ''),
            ':email' => (string) ($data['email'] ?? ''),
        ]);

        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        if ($id <= 0) {
            return false;
        }

        $pdo = Database::pdo();
        $stmt = $pdo->prepare('
            UPDATE terceros
            SET tipo = :tipo,
                nombre_comercial = :nombre_comercial,
                identificacion = :identificacion,
                email = :email
            WHERE id = :id AND estado = 1
            LIMIT 1
        ');

        $stmt->execute([
            ':tipo' => (string) ($data['tipo'] ?? ''),
            ':nombre_comercial' => (string) ($data['nombre_comercial'] ?? ''),
            ':identificacion' => (string) ($data['identificacion'] ?? ''),
            ':email' => (string) ($data['email'] ?? ''),
            ':id' => $id,
        ]);

        return $stmt->rowCount() > 0;
    }

    public static function softDelete(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        $pdo = Database::pdo();
        $stmt = $pdo->prepare('UPDATE terceros SET estado = 0 WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() > 0;
    }
}