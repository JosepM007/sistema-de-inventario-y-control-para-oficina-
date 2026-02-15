<?php
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'admin') {
    $_SESSION['error'] = "Sin permisos";
    header("Location: dashboard.php");
    exit;
}

require 'db.php';

if (!isset($_GET['id']) || !isset($_GET['rol'])) {
    $_SESSION['error'] = "Parámetros inválidos";
    header("Location: usuarios.php");
    exit;
}

$id = intval($_GET['id']);
$nuevo_rol = $_GET['rol'];

if ($nuevo_rol != 'admin' && $nuevo_rol != 'usuario') {
    $_SESSION['error'] = "Rol inválido";
    header("Location: usuarios.php");
    exit;
}

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

$usuario_cambiar = $result->fetch_assoc();

if ($usuario_cambiar['usuario'] == $_SESSION['usuario']) {
    $_SESSION['error'] = "No puedes cambiar tu propio rol";
    header("Location: usuarios.php");
    exit;
}

$sql = "UPDATE usuarios SET rol = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $nuevo_rol, $id);

if ($stmt->execute()) {
    $rol_texto = ($nuevo_rol == 'admin') ? 'Administrador' : 'Usuario';
    $_SESSION['success'] = "Rol actualizado a: " . $rol_texto;
} else {
    $_SESSION['error'] = "Error al cambiar rol";
}

$stmt->close();
$conn->close();

header("Location: usuarios.php");
exit;
?>