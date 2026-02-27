<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}
require 'db.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Proveedores - OfficeStock Pro</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
    .providers {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
        padding: 20px;
    }
    .provider-card {
        background: rgba(0, 4, 255, 0.16);
        border-radius: 12px;
        padding: 22px 24px;
        width: 280px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        text-align: left;
        display: flex;
        flex-direction: column;
        gap: 8px;
        transition: transform .18s ease, box-shadow .18s ease;
    }
    .provider-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 16px 36px rgba(0,0,0,0.28);
    }
    .provider-card .prov-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 4px;
    }
    .provider-card .prov-icon {
        font-size: 28px;
        width: 48px;
        height: 48px;
        border-radius: 10px;
        background: linear-gradient(135deg, #5b21b6, #7c3aed);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .provider-card h3 {
        margin: 0;
        color: #fff;
        font-size: 1.05rem;
    }
    .provider-card p {
        margin: 0;
        color: #ccc;
        font-size: 0.85rem;
        flex: 1;
    }
    .btn {
        display: inline-block;
        padding: 8px 14px;
        border-radius: 8px;
        background: #6b4cff;
        color: white;
        text-decoration: none;
        font-size: 0.88rem;
        text-align: center;
        margin-top: 4px;
        transition: background .15s;
    }
    .btn:hover { background: #5538e0; }

    .section-title {
        padding: 10px 20px 0 20px;
        color: #fff;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        opacity: 0.6;
        margin-top: 10px;
    }
    </style>
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
        <a href="proveedores.php">🏢 Proveedores</a>
        <a href="logout.php" class="logout-link">🚪 Cerrar Sesión</a>
    </div>

    <div class="main-content">
        <div class="header">
            <h2>Proveedores</h2>
            <p class="user-info">👤 <?php echo htmlspecialchars($_SESSION['usuario']); ?> | <?php echo ucfirst($_SESSION['rol']); ?></p>
        </div>

        <!-- TECNOLOGÍA -->
        <p class="section-title">💻 Tecnología</p>
        <div class="providers">

            <div class="provider-card">
                <div class="prov-header">
                    <div class="prov-icon">💙</div>
                    <h3>HP</h3>
                </div>
                <p>Laptops, equipos de escritorio, monitores e impresoras.</p>
                <a class="btn" href="proveedor.php?prov=HP">Ver productos</a>
            </div>

            <div class="provider-card">
                <div class="prov-header">
                    <div class="prov-icon">📱</div>
                    <h3>Samsung</h3>
                </div>
                <p>Smartphones, tablets, monitores y almacenamiento SSD.</p>
                <a class="btn" href="proveedor.php?prov=Samsung">Ver productos</a>
            </div>

            <div class="provider-card">
                <div class="prov-header">
                    <div class="prov-icon">🍎</div>
                    <h3>Apple</h3>
                </div>
                <p>iPhone, iPad, MacBook y equipos premium.</p>
                <a class="btn" href="proveedor.php?prov=Apple">Ver productos</a>
            </div>

            <div class="provider-card">
                <div class="prov-header">
                    <div class="prov-icon">🖥️</div>
                    <h3>Lenovo</h3>
                </div>
                <p>ThinkPad, IdeaPad, equipos empresariales y tablets.</p>
                <a class="btn" href="proveedor.php?prov=Lenovo">Ver productos</a>
            </div>

            <div class="provider-card">
                <div class="prov-header">
                    <div class="prov-icon">📦</div>
                    <h3>Amazon</h3>
                </div>
                <p>Periféricos, accesorios y equipamiento de oficina.</p>
                <a class="btn" href="proveedor.php?prov=Amazon">Ver productos</a>
            </div>

        </div>

        <!-- ÚTILES Y MOBILIARIO -->
        <p class="section-title">🏬 Útiles y Mobiliario</p>
        <div class="providers">

            <div class="provider-card">
                <div class="prov-header">
                    <div class="prov-icon">🛒</div>
                    <h3>Walmart</h3>
                </div>
                <p>Útiles de oficina, papelería y artículos de escritorio.</p>
                <a class="btn" href="proveedor.php?prov=Walmart">Ver productos</a>
            </div>

            <div class="provider-card">
                <div class="prov-header">
                    <div class="prov-icon">🪑</div>
                    <h3>Siman</h3>
                </div>
                <p>Mobiliario de oficina, sillas, escritorios y estantes.</p>
                <a class="btn" href="proveedor.php?prov=Siman">Ver productos</a>
            </div>

        </div>

    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>
