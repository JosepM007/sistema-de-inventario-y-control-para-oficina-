<?php
session_start();

// Solo admins pueden crear usuarios desde este formulario
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'admin') {
    $_SESSION['error'] = "Sin permisos. Solo los administradores pueden crear usuarios.";
    header("Location: login.php");
    exit;
}

require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = trim($_POST['usuario']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmar = $_POST['confirmar'];
    
    if ($password !== $confirmar) {
        $error = "Las contraseñas no coinciden";
    } elseif (strlen($password) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres";
    } else {
        $sql_check = "SELECT id FROM usuarios WHERE usuario = ? OR email = ?";
        $stmt = $conn->prepare($sql_check);
        $stmt->bind_param("ss", $usuario, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "El usuario o email ya está registrado";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO usuarios (usuario, email, password, rol) VALUES (?, ?, ?, 'usuario')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $usuario, $email, $password_hash);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Cuenta creada exitosamente. Ya puedes iniciar sesión";
                header("Location: login.php");
                exit;
            } else {
                $error = "Error al crear la cuenta";
            }
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
    <title>Crear Cuenta - OfficeStock Pro</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h2>Crear Cuenta Nueva</h2>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <label>Usuario *</label>
            <input type="text" name="usuario" placeholder="Elige un nombre de usuario" required>
            
            <label>Correo Electrónico *</label>
            <input type="email" name="email" placeholder="tu@email.com" required>
            
            <label>Contraseña *</label>
            <input type="password" name="password" placeholder="Mínimo 6 caracteres" required>
            
            <label>Confirmar Contraseña *</label>
            <input type="password" name="confirmar" placeholder="Repite tu contraseña" required>
            
            <button type="submit">Crear Cuenta</button>
            <a href="usuarios.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
</body>
</html>