<?php
/**
 * Función reutilizable para registrar movimientos en la tabla auditoria
 * Incluir este archivo en cualquier PHP que necesite registrar auditoría:
 *   require 'auditoria_fn.php';
 */

function registrar_auditoria($conn, $usuario, $accion, $detalle, $tabla = '') {
    $stmt = $conn->prepare("
        INSERT INTO auditoria (usuario, accion, detalle, tabla_afectada)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param('ssss', $usuario, $accion, $detalle, $tabla);
    $stmt->execute();
    $stmt->close();
}
