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
    header("Location: usuarios.php");
    exit;
}

$id = intval($_GET['id']);

$sql_check = "SELECT usuario FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($sql_check);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Usuario no encontrado";
    header("Location: usuarios.php");
    exit;
}

$usuario_eliminar = $result->fetch_assoc();

if ($usuario_eliminar['usuario'] == $_SESSION['usuario']) {
    $_SESSION['error'] = "No puedes eliminar tu propia cuenta";
    header("Location: usuarios.php");
    exit;
}

$sql = "DELETE FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $_SESSION['success'] = "Usuario eliminado";
} else {
    $_SESSION['error'] = "Error al eliminar";
}

$stmt->close();
$conn->close();

header("Location: usuarios.php");
exit;
?>