<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}
require 'db.php';

$proveedores_validos = ['Amazon', 'Walmart', 'Siman', 'HP', 'Samsung', 'Apple', 'Lenovo'];

$prov = isset($_GET['prov']) ? trim($_GET['prov']) : '';

if (!in_array($prov, $proveedores_validos)) {
    header("Location: proveedores.php");
    exit;
}

// Consulta directa sin prepared statement
$prov_safe = $conn->real_escape_string($prov);
$result = $conn->query("SELECT id, nombre, descripcion, cantidad, precio FROM productos WHERE proveedores = '$prov_safe' ORDER BY nombre ASC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title><?php echo htmlspecialchars($prov); ?> - OfficeStock Pro</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="layout">

    <div class="sidebar">
        <h2 class="logo">OfficeStock</h2>
        <a href="dashboard.php">🏠 Dashboard</a>
        <?php if ($_SESSION['rol'] == 'admin'): ?>
            <a href="productos.php">📦 Productos</a>
            <a href="usuarios.php">👥 Usuarios</a>
        <?php endif; ?>
        <a href="categorias.php">📂 Categorías</a>
        <a href="proveedores.php" class="active">🏢 Proveedores</a>
        <a href="logout.php" class="logout-link">🚪 Cerrar Sesión</a>
    </div>

    <div class="main-content">
        <div class="header">
            <div>
                <h2>🏢 <?php echo htmlspecialchars($prov); ?></h2>
                <p class="user-info">👤 <?php echo htmlspecialchars($_SESSION['usuario']); ?> | <?php echo ucfirst($_SESSION['rol']); ?></p>
            </div>
        </div>

        <div style="padding: 20px;">

            <?php if (!$result || $result->num_rows == 0): ?>
                <p style="color:#fff8;">No hay productos registrados para este proveedor.</p>
            <?php else: ?>
                <p style="color:#bbb; margin-bottom:16px; font-size:0.9rem;">
                    <?php echo $result->num_rows; ?> producto(s) encontrados
                </p>
                <div class="card">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Cantidad</th>
                                <th>Precio</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['nombre']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['descripcion']); ?></td>
                                <td><?php echo $row['cantidad']; ?></td>
                                <td>$<?php echo number_format($row['precio'], 2); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <br>
            <a href="proveedores.php" class="btn btn-secondary">⬅ Volver a Proveedores</a>
        </div>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>
