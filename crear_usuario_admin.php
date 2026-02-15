<?php
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'admin') {
    $_SESSION['error'] = "Sin permisos";
    header("Location: dashboard.php");
    exit;
}

require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = trim($_POST['usuario']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $rol = $_POST['rol'];
    
    if (strlen($password) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres";
    } else {
        $sql_check = "SELECT id FROM usuarios WHERE usuario = ? OR email = ?";
        $stmt = $conn->prepare($sql_check);
        $stmt->bind_param("ss", $usuario, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "El usuario o email ya existe";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO usuarios (usuario, email, password, rol) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $usuario, $email, $password_hash, $rol);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Usuario creado exitosamente";
                header("Location: usuarios.php");
                exit;
            } else {
                $error = "Error al crear usuario";
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
    <title>Crear Usuario - OfficeStock Pro</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h2>Crear Nuevo Usuario</h2>
        <p style="text-align: center; color: #666; margin-bottom: 20px;">Panel de Administrador</p>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <label>Usuario *</label>
            <input type="text" name="usuario" required>
            
            <label>Correo Electrónico *</label>
            <input type="email" name="email" required>
            
            <label>Contraseña *</label>
            <input type="password" name="password" placeholder="Mínimo 6 caracteres" required>
            
            <label>Rol *</label>
            <select name="rol" required>
                <option value="usuario">Usuario</option>
                <option value="admin">Administrador</option>
            </select>
            
            <button type="submit">Crear Usuario</button>
            <a href="usuarios.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
</body>
</html>