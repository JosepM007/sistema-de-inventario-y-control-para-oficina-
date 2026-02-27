<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

require 'db.php';

/* =========================
   CONSULTAS PRINCIPALES
========================= */

$total_productos = $conn->query("SELECT COUNT(*) as total FROM productos")->fetch_assoc()['total'];
$total_stock = $conn->query("SELECT SUM(cantidad) as total FROM productos")->fetch_assoc()['total'];
$valor_total = $conn->query("SELECT SUM(cantidad * precio) as total FROM productos")->fetch_assoc()['total'];

/* ===== NUEVAS CONSULTAS PARA PANEL ===== */

// Productos con bajo stock (menos de 10)
$productos_bajos = $conn->query("SELECT * FROM productos WHERE cantidad < 10");

// Proveedor que más productos suministra
$proveedor_top = $conn->query("
    SELECT proveedores, COUNT(*) as total
    FROM productos
    GROUP BY proveedores
    ORDER BY total DESC
    LIMIT 1
")->fetch_assoc();

// Total categorías (si existe la tabla)
$total_categorias = 0;
if ($conn->query("SHOW TABLES LIKE 'categorias'")->num_rows > 0) {
    $total_categorias = $conn->query("SELECT COUNT(*) as total FROM categorias")->fetch_assoc()['total'];
}

if ($_SESSION['rol'] == 'admin') {
    $total_usuarios = $conn->query("SELECT COUNT(*) as total FROM usuarios")->fetch_assoc()['total'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - OfficeStock Pro</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="layout">

    <!-- SIDEBAR -->
    <div class="sidebar">
        <h2 class="logo">OfficeStock</h2>

        <a href="dashboard.php">🏠 Dashboard</a>

        <?php if ($_SESSION['rol'] == 'admin'): ?>
            <a href="productos.php">📦 Productos</a>
            <a href="usuarios.php">👥 Usuarios</a>
        <?php endif; ?>

        <a href="categorias.php">📂 Categorías</a>
        <a href="proveedores.php">🏢 Proveedores</a>
        <a href="logout.php" class="logout-link">🚪 Cerrar Sesión</a>
    </div>

    <!-- CONTENIDO PRINCIPAL -->
    <div class="main-content">
        

            <div class="header">
                <div>
                    <h2>OfficeStock Pro</h2>
                    <p class="user-info">
                        👤 <?php echo $_SESSION['usuario']; ?> |
                        <?php echo ucfirst($_SESSION['rol']); ?>
                    </p>
                </div>
            </div>

            <!-- TARJETAS -->
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

            <!-- PANEL INTERACTIVO -->
            <div class="control-panel">

                <div class="accordion-item">
                    <button class="accordion-btn">📦 ¿Cuántos productos tengo?</button>
                    <div class="accordion-content">
                        <p>Tienes <strong><?php echo $total_productos; ?></strong> productos registrados.</p>
                    </div>
                </div>

                <div class="accordion-item">
                    <button class="accordion-btn">💰 ¿Cuánto vale mi inventario?</button>
                    <div class="accordion-content">
                        <p>El valor total del inventario es 
                        <strong>$<?php echo number_format($valor_total, 2); ?></strong></p>
                    </div>
                </div>

                <div class="accordion-item">
                    <button class="accordion-btn">⚠️ ¿Qué productos están bajos en stock?</button>
                    <div class="accordion-content">
                        <?php if ($productos_bajos->num_rows > 0): ?>
                            <ul>
                                <?php while ($bajo = $productos_bajos->fetch_assoc()): ?>
                                    <li><?php echo $bajo['nombre']; ?> 
                                    (<?php echo $bajo['cantidad']; ?> unidades)</li>
                                <?php endwhile; ?>
                            </ul>
                        <?php else: ?>
                            <p>No hay productos con bajo stock.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="accordion-item">
                    <button class="accordion-btn">📊 ¿Qué proveedor me abastece más?</button>
                    <div class="accordion-content">
                        <?php if ($proveedor_top): ?>
                            <p>Proveedor principal:
                            <strong><?php echo $proveedor_top['proveedores']; ?></strong>
                            (<?php echo $proveedor_top['total']; ?> productos)</p>
                        <?php else: ?>
                            <p>No hay datos disponibles.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="accordion-item">
                    <button class="accordion-btn">📂 ¿Cuántas categorías tengo?</button>
                    <div class="accordion-content">
                        <p>Tienes <strong><?php echo $total_categorias; ?></strong> categorías registradas.</p>
                    </div>
                </div>

            </div>

        
    </div>
</div>

<!-- SCRIPT INTERACTIVO -->
<script>
document.querySelectorAll(".accordion-btn").forEach(button => {
    button.addEventListener("click", function() {
        const content = this.nextElementSibling;
        content.style.display =
            content.style.display === "block" ? "none" : "block";
    });
});
</script>

</body>
</html>

<?php $conn->close(); ?>