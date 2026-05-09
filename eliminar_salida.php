<?php
session_start();

// Solo admin jose puede eliminar salidas
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'admin' || strtolower($_SESSION['usuario']) !== 'jose') {
    $_SESSION['error'] = "Sin permisos";
    header("Location: salidas.php");
    exit;
}

require 'db.php';
if (file_exists('auditoria_fn.php')) require 'auditoria_fn.php';

if (!isset($_GET['id'])) {
    $_SESSION['error'] = "ID no especificado";
    header("Location: salidas.php");
    exit;
}

$id = intval($_GET['id']);
if ($id <= 0) {
    $_SESSION['error'] = "ID inválido";
    header("Location: salidas.php");
    exit;
}

// Obtener datos antes de eliminar (para auditoría)
$stmt = $conn->prepare("SELECT producto_nombre, cantidad_salida, area_destino FROM salidas WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$salida = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$salida) {
    $_SESSION['error'] = "Salida no encontrada";
    $conn->close();
    header("Location: salidas.php");
    exit;
}

$stmt = $conn->prepare("DELETE FROM salidas WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    if (function_exists('registrar_auditoria')) {
        $detalle = "Salida ID {$id} eliminada: producto \"{$salida['producto_nombre']}\", {$salida['cantidad_salida']} uds, área \"{$salida['area_destino']}\".";
        registrar_auditoria($conn, $_SESSION['usuario'], 'ELIMINACION', $detalle, 'salidas');
    }
    $_SESSION['success'] = "Salida #$id eliminada. Recuerda ajustar el stock manualmente si es necesario.";
} else {
    $_SESSION['error'] = "Error al eliminar la salida.";
}

$stmt->close();
$conn->close();
header("Location: salidas.php");
exit;
?>
