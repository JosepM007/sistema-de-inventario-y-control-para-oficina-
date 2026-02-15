<?php
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'admin') {
    $_SESSION['error'] = "Sin permisos";
    header("Location: dashboard.php");
    exit;
}

require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $cantidad = intval($_POST['cantidad']);
    $precio = floatval($_POST['precio']);
    
    if (!empty($nombre) && $cantidad >= 0 && $precio >= 0) {
        $sql = "INSERT INTO productos (nombre, descripcion, cantidad, precio) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssid", $nombre, $descripcion, $cantidad, $precio);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Producto agregado";
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Error al guardar";
        }
        $stmt->close();
    } else {
        $error = "Completa todos los campos";
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Producto - OfficeStock Pro</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h2>Agregar Producto</h2>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <label>Nombre del Producto *</label>
            <input type="text" name="nombre" required>
            
            <label>Descripción</label>
            <textarea name="descripcion"></textarea>
            
            <label>Cantidad *</label>
            <input type="number" name="cantidad" min="0" required>
            
            <label>Precio Unitario * ($)</label>
            <input type="number" name="precio" min="0" step="0.01" required>
            
            <button type="submit">Guardar</button>
            <a href="dashboard.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
</body>
</html>