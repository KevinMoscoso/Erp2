<?php
declare(strict_types=1);

namespace Erp2\Model;

use Erp2\Core\Database;

final class Auditoria
{
    public static function log(int $usuarioId, string $accion, string $entidad, int $entidadId, array $detalle = []): void
    {
        if ($usuarioId <= 0) {
            return;
        }

        $accion = trim($accion);
        $entidad = trim($entidad);

        if ($accion === '' || $entidad === '' || $entidadId <= 0) {
            return;
        }

        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');

        $detalleJson = null;
        if (!empty($detalle)) {
            $json = json_encode($detalle, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (is_string($json)) {
                $detalleJson = $json;
            }
        }

        $pdo = Database::pdo();
        $stmt = $pdo->prepare('
            INSERT INTO auditoria (usuario_id, accion, entidad, entidad_id, ip, user_agent, detalle_json)
            VALUES (:usuario_id, :accion, :entidad, :entidad_id, :ip, :user_agent, :detalle_json)
        ');

        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':accion' => $accion,
            ':entidad' => $entidad,
            ':entidad_id' => $entidadId,
            ':ip' => $ip,
            ':user_agent' => $ua,
            ':detalle_json' => $detalleJson,
        ]);
    }
}