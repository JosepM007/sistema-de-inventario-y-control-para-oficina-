<?php
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'admin') {
    $_SESSION['error'] = "Sin permisos";
    header("Location: dashboard.php");
    exit;
}

require 'db.php';

if (!isset($_GET['id'])) {
    $_SESSION['error'] = "ID no especificado";
    header("Location: dashboard.php");
    exit;
}

$id = intval($_GET['id']);

$sql = "DELETE FROM productos WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $_SESSION['success'] = "Producto eliminado";
} else {
    $_SESSION['error'] = "Error al eliminar";
}

$stmt->close();
$conn->close();

header("Location: dashboard.php");
exit;
?>