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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $cantidad = intval($_POST['cantidad']);
    $precio = floatval($_POST['precio']);
    
    if (!empty($nombre) && $cantidad >= 0 && $precio >= 0) {
        $sql = "UPDATE productos SET nombre=?, descripcion=?, cantidad=?, precio=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssidi", $nombre, $descripcion, $cantidad, $precio, $id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Producto actualizado";
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Error al actualizar";
        }
        $stmt->close();
    } else {
        $error = "Completa todos los campos";
    }
}

$sql = "SELECT * FROM productos WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Producto no encontrado";
    header("Location: dashboard.php");
    exit;
}

$producto = $result->fetch_assoc();
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Producto - OfficeStock Pro</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h2>Editar Producto</h2>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <label>Nombre del Producto *</label>
            <input type="text" name="nombre" value="<?php echo htmlspecialchars($producto['nombre']); ?>" required>
            
            <label>Descripción</label>
            <textarea name="descripcion"><?php echo htmlspecialchars($producto['descripcion']); ?></textarea>
            
            <label>Cantidad *</label>
            <input type="number" name="cantidad" value="<?php echo $producto['cantidad']; ?>" min="0" required>
            
            <label>Precio Unitario * ($)</label>
            <input type="number" name="precio" value="<?php echo $producto['precio']; ?>" min="0" step="0.01" required>
            
            <button type="submit">Actualizar</button>
            <a href="dashboard.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
</body>
</html>