<?php
session_start();
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = trim($_POST['usuario']);
    $password = $_POST['password'];
    
    $sql = "SELECT * FROM usuarios WHERE usuario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['usuario'] = $user['usuario'];
            $_SESSION['rol'] = $user['rol'];
            $_SESSION['user_id'] = $user['id'];
            
            header("Location: dashboard.php");
            exit;
        } else {
            $_SESSION['error'] = "Contraseña incorrecta";
        }
    } else {
        $_SESSION['error'] = "Usuario no encontrado";
    }
    
    $stmt->close();
} else {
    $_SESSION['error'] = "Acceso no autorizado";
}

$conn->close();
header("Location: login.php");
exit;
?>