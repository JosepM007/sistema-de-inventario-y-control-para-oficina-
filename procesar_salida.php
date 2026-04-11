<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}
require 'db.php';
require 'auditoria_fn.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: salidas.php");
    exit;
}

$producto_id  = intval($_POST['producto_id']    ?? 0);
$cantidad     = intval($_POST['cantidad_salida'] ?? 0);
$area_destino = trim($_POST['area_destino']      ?? '');
$motivo       = trim($_POST['motivo']            ?? '');
$usuario      = $_SESSION['usuario'];

if ($producto_id <= 0 || $cantidad <= 0 || empty($area_destino)) {
    $_SESSION['error'] = "Por favor completa todos los campos obligatorios.";
    header("Location: salidas.php");
    exit;
}

$stmt = $conn->prepare("SELECT id, nombre, cantidad FROM productos WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $producto_id);
$stmt->execute();
$prod = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$prod) {
    $_SESSION['error'] = "El producto seleccionado no existe.";
    header("Location: salidas.php");
    exit;
}

if ($cantidad > intval($prod['cantidad'])) {
    $_SESSION['error'] = "No hay suficiente stock. Stock actual: " . $prod['cantidad'] . " unidades.";
    header("Location: salidas.php");
    exit;
}

$stmt = $conn->prepare("INSERT INTO salidas (producto_id, producto_nombre, cantidad_salida, motivo, area_destino, usuario) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param('isisss', $producto_id, $prod['nombre'], $cantidad, $motivo, $area_destino, $usuario);

if (!$stmt->execute()) {
    $_SESSION['error'] = "Error al registrar la salida: " . $conn->error;
    $stmt->close();
    $conn->close();
    header("Location: salidas.php");
    exit;
}
$stmt->close();

$nuevo_stock = intval($prod['cantidad']) - $cantidad;
$upd = $conn->prepare("UPDATE productos SET cantidad = ? WHERE id = ?");
$upd->bind_param('ii', $nuevo_stock, $producto_id);
$upd->execute();
$upd->close();

// Registrar en auditoría
$detalle = "Salida de {$cantidad} unidad(es) de \"{$prod['nombre']}\" hacia: {$area_destino}. Stock: {$prod['cantidad']} → {$nuevo_stock}.";
if ($motivo) $detalle .= " Motivo: {$motivo}";
registrar_auditoria($conn, $usuario, 'SALIDA', $detalle, 'productos');

$conn->close();
$_SESSION['success'] = "Salida registrada. Se retiraron {$cantidad} unidad(es) de \"{$prod['nombre']}\". Stock: {$nuevo_stock} unidades.";
header("Location: salidas.php");
exit;
