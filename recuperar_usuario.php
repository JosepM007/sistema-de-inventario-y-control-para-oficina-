<?php
session_start();
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    
    $sql = "SELECT usuario FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $mensaje = "Tu usuario es: <strong>" . $user['usuario'] . "</strong>";
    } else {
        $error = "No existe ninguna cuenta con ese email";
    }
    
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Usuario - OfficeStock Pro</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h2>Recuperar Usuario</h2>
        <p style="text-align: center; color: #666; margin-bottom: 20px;">Ingresa tu correo para recuperar tu usuario</p>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (isset($mensaje)): ?>
            <div class="success"><?php echo $mensaje; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <label>Correo Electrónico</label>
            <input type="email" name="email" placeholder="tu@email.com" required>
            
            <button type="submit">Recuperar Usuario</button>
            <a href="login.php" class="btn btn-secondary">Volver al Login</a>
        </form>
    </div>
</body>
</html>