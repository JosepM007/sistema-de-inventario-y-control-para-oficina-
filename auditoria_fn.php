<?php
/**
 * Función reutilizable para registrar movimientos en la tabla auditoria
 * Incluir este archivo en cualquier PHP que necesite registrar auditoría:
 *   require 'auditoria_fn.php';
 */

function registrar_auditoria($conn, $usuario, $accion, $detalle, $tabla = '') {
    // Auto-crear tabla si no existe (evita errores en instalaciones nuevas)
    $conn->query("
        CREATE TABLE IF NOT EXISTS `auditoria` (
            `id`             INT(11) NOT NULL AUTO_INCREMENT,
            `usuario`        VARCHAR(100) DEFAULT NULL,
            `accion`         VARCHAR(50)  DEFAULT NULL,
            `detalle`        TEXT         DEFAULT NULL,
            `tabla_afectada` VARCHAR(100) DEFAULT NULL,
            `fecha`          DATETIME     DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $stmt = $conn->prepare("
        INSERT INTO auditoria (usuario, accion, detalle, tabla_afectada)
        VALUES (?, ?, ?, ?)
    ");
    if ($stmt) {
        $stmt->bind_param('ssss', $usuario, $accion, $detalle, $tabla);
        $stmt->execute();
        $stmt->close();
    }
}
