<?php
session_start();
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $nueva = $_POST['nueva'];
    $confirmar = $_POST['confirmar'];
    
    if ($nueva !== $confirmar) {
        $error = "Las contraseñas no coinciden";
    } elseif (strlen($nueva) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres";
    } else {
        $sql = "SELECT id FROM usuarios WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $password_hash = password_hash($nueva, PASSWORD_DEFAULT);
            $sql_update = "UPDATE usuarios SET password = ? WHERE email = ?";
            $stmt = $conn->prepare($sql_update);
            $stmt->bind_param("ss", $password_hash, $email);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Contraseña actualizada exitosamente";
                header("Location: login.php");
                exit;
            } else {
                $error = "Error al actualizar la contraseña";
            }
        } else {
            $error = "No existe ninguna cuenta con ese email";
        }
        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - OfficeStock Pro</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h2>Recuperar Contraseña</h2>
        <p style="text-align: center; color: #666; margin-bottom: 20px;">Ingresa tu email y nueva contraseña</p>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <label>Correo Electrónico</label>
            <input type="email" name="email" placeholder="tu@email.com" required>
            
            <label>Nueva Contraseña</label>
            <input type="password" name="nueva" placeholder="Mínimo 6 caracteres" required>
            
            <label>Confirmar Contraseña</label>
            <input type="password" name="confirmar" placeholder="Repite la contraseña" required>
            
            <button type="submit">Actualizar Contraseña</button>
            <a href="login.php" class="btn btn-secondary">Volver al Login</a>
        </form>
    </div>
</body>
</html>