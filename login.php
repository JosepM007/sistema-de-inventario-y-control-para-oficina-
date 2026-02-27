<?php
session_start();

if (isset($_SESSION['usuario'])) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - OfficeStock Pro</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="login-page">
    <div class="container">
        <img src="https://cdn-icons-png.flaticon.com/512/2910/2910791.png" alt="Logo" class="logo">
        
        <h2>OfficeStock Pro</h2>
        <p style="text-align: center; color: #666; margin-bottom: 20px;">Control de Inventario Empresarial</p>
        
        <?php
        if (isset($_SESSION['error'])) {
            echo '<div class="error">' . $_SESSION['error'] . '</div>';
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            echo '<div class="success">' . $_SESSION['success'] . '</div>';
            unset($_SESSION['success']);
        }
        ?>
        
        <form action="validar_login.php" method="POST">
            <input type="text" name="usuario" placeholder="Usuario" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            <button type="submit">Iniciar Sesión</button>
        </form>
        
        <div class="nav-links">
            <a href="recuperar_usuario.php">¿Olvidaste tu usuario?</a><br>
            <a href="recuperar_password.php">¿Olvidaste tu contraseña?</a>
        </div>
    </div>
</body>


</html>