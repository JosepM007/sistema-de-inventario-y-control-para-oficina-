<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'admin') { $_SESSION['error'] = "Sin permisos"; header("Location: dashboard.php"); exit; }
require 'db.php';
if (file_exists('auditoria_fn.php')) require 'auditoria_fn.php';
if (!isset($_GET['id'])) { $_SESSION['error'] = "ID no especificado"; header("Location: dashboard.php"); exit; }

$id = intval($_GET['id']);
if ($id <= 0) { $_SESSION['error'] = "ID inválido"; header("Location: dashboard.php"); exit; }

// Detectar de dónde viene para redirigir correctamente
$from_prov = isset($_GET['from_prov']) ? trim($_GET['from_prov']) : '';
$back_get  = isset($_GET['back_url'])  ? trim($_GET['back_url'])  : '';
$allowed_backs = ['nuevo_inventario.php', 'dashboard.php'];
if (!empty($from_prov)) {
    $redir = "proveedor.php?prov=" . urlencode($from_prov);
} elseif (!empty($back_get) && in_array($back_get, $allowed_backs)) {
    $redir = $back_get;
} else {
    $redir = "dashboard.php";
}

$stmt = $conn->prepare("SELECT nombre FROM productos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$prod = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$prod) { $_SESSION['error'] = "Producto no encontrado"; $conn->close(); header("Location: $redir"); exit; }

$stmt = $conn->prepare("DELETE FROM productos WHERE id = ?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    if (function_exists('registrar_auditoria')) {
        registrar_auditoria($conn, $_SESSION['usuario'], 'ELIMINACION', "Producto eliminado: \"{$prod['nombre']}\" (ID: {$id}).", 'productos');
    }
    $_SESSION['success'] = "Producto \"{$prod['nombre']}\" eliminado correctamente.";
} else {
    $_SESSION['error'] = "Error al eliminar el producto.";
}
$stmt->close(); $conn->close();
header("Location: $redir"); exit;
?>
