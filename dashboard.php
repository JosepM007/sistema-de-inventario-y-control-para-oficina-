<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

require 'db.php';

$total_productos = $conn->query("SELECT COUNT(*) as total FROM productos")->fetch_assoc()['total'];
$total_stock = $conn->query("SELECT SUM(cantidad) as total FROM productos")->fetch_assoc()['total'];
$valor_total = $conn->query("SELECT SUM(cantidad * precio) as total FROM productos")->fetch_assoc()['total'];

if ($_SESSION['rol'] == 'admin') {
    $total_usuarios = $conn->query("SELECT COUNT(*) as total FROM usuarios")->fetch_assoc()['total'];
}

$productos = $conn->query("SELECT * FROM productos ORDER BY nombre ASC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - OfficeStock Pro</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container container-large dashboard-container">
        
        <div class="header">
            <div>
                <h2 style="margin: 0;">OfficeStock Pro</h2>
                <p class="user-info">👤 <?php echo $_SESSION['usuario']; ?> | <?php echo ucfirst($_SESSION['rol']); ?></p>
            </div>
            <div>
                <?php if ($_SESSION['rol'] == 'admin'): ?>
                    <a href="usuarios.php" class="btn btn-success btn-small">👥 Usuarios</a>
                <?php endif; ?>
                <a href="logout.php" class="btn btn-danger btn-small">Cerrar Sesión</a>
            </div>
        </div>
        
        <?php
        if (isset($_SESSION['success'])) {
            echo '<div class="success">' . $_SESSION['success'] . '</div>';
            unset($_SESSION['success']);
        }
        if (isset($_SESSION['error'])) {
            echo '<div class="error">' . $_SESSION['error'] . '</div>';
            unset($_SESSION['error']);
        }
        ?>
        
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_productos; ?></div>
                <div class="stat-label">Total Productos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($total_stock); ?></div>
                <div class="stat-label">Unidades en Stock</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">$<?php echo number_format($valor_total, 2); ?></div>
                <div class="stat-label">Valor Total</div>
            </div>
            <?php if ($_SESSION['rol'] == 'admin'): ?>
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_usuarios; ?></div>
                <div class="stat-label">Usuarios</div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3>Inventario de Productos</h3>
                <?php if ($_SESSION['rol'] == 'admin'): ?>
                    <a href="productos.php" class="btn btn-success btn-small">➕ Agregar Producto</a>
                <?php endif; ?>
            </div>
            
            <?php if ($productos->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Cantidad</th>
                            <th>Precio</th>
                            <th>Total</th>
                            <?php if ($_SESSION['rol'] == 'admin'): ?>
                                <th>Acciones</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($producto = $productos->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $producto['id']; ?></td>
                                <td><strong><?php echo $producto['nombre']; ?></strong></td>
                                <td><?php echo $producto['descripcion']; ?></td>
                                <td><?php echo $producto['cantidad']; ?></td>
                                <td>$<?php echo number_format($producto['precio'], 2); ?></td>
                                <td>$<?php echo number_format($producto['cantidad'] * $producto['precio'], 2); ?></td>
                                <?php if ($_SESSION['rol'] == 'admin'): ?>
                                    <td class="actions">
                                        <a href="editar_producto.php?id=<?php echo $producto['id']; ?>" class="btn btn-secondary btn-small">✏️ Editar</a>
                                        <a href="eliminar_producto.php?id=<?php echo $producto['id']; ?>" 
                                           onclick="return confirm('¿Eliminar este producto?')" 
                                           class="btn btn-danger btn-small">🗑️ Eliminar</a>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="info">No hay productos en el inventario.</div>
            <?php endif; ?>
        </div>
        
        <?php if ($_SESSION['rol'] == 'usuario'): ?>
            <div class="info">
                ℹ️ Solo visualización. Contacta al administrador para modificar el inventario.
            </div>
        <?php endif; ?>
        
    </div>
</body>
</html>
<?php $conn->close(); ?>