<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}
require 'db.php';

if (!isset($_GET['cat'])) {
    header("Location: categorias.php");
    exit;
}

$cat_slug = trim($_GET['cat']);

// Mapeo slug => nombre visible y proveedores relacionados
$categorias_config = [
    'tecnologia' => [
        'nombre'      => 'Tecnología',
        'icon'        => '💻',
        'proveedores' => ['HP', 'Samsung', 'Apple', 'Lenovo', 'Amazon']
    ],
    'mobiliario' => [
        'nombre'      => 'Mobiliario',
        'icon'        => '🪑',
        'proveedores' => ['Siman']
    ],
    'utiles' => [
        'nombre'      => 'Útiles',
        'icon'        => '✏️',
        'proveedores' => ['Walmart']
    ]
];

if (!isset($categorias_config[$cat_slug])) {
    header("Location: categorias.php");
    exit;
}

$config    = $categorias_config[$cat_slug];
$cat_name  = $config['nombre'];
$provs     = $config['proveedores'];

// Construir placeholders dinámicos: ?,?,?
$placeholders = implode(',', array_fill(0, count($provs), '?'));
$types        = str_repeat('s', count($provs));

$sql = "SELECT id, nombre, descripcion, cantidad, precio, proveedores
        FROM productos
        WHERE proveedores IN ($placeholders)
        ORDER BY proveedores, nombre ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$provs);
$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($r = $result->fetch_assoc()) {
    $products[] = $r;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($cat_name); ?> - OfficeStock Pro</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
    .page-wrap { padding: 28px; }
    .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 16px;
    }
    .product-card {
        background: rgba(255,255,255,0.07);
        border-radius: 12px;
        padding: 16px;
        display: flex;
        flex-direction: column;
        gap: 8px;
        box-shadow: 0 6px 20px rgba(0,0,0,0.2);
        transition: transform .18s, box-shadow .18s;
    }
    .product-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 14px 32px rgba(0,0,0,0.3);
    }
    .product-badge {
        font-size: 0.72rem;
        background: rgba(107,76,255,0.4);
        color: #d4c9ff;
        border-radius: 6px;
        padding: 2px 8px;
        display: inline-block;
        width: fit-content;
    }
    .product-title {
        font-weight: 700;
        color: #fff;
        font-size: 0.95rem;
    }
    .product-desc {
        color: #bbb;
        font-size: 0.82rem;
        flex: 1;
    }
    .product-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top: 1px solid rgba(255,255,255,0.1);
        padding-top: 8px;
        margin-top: 4px;
    }
    .product-price { color: #a3ffb0; font-weight: 700; font-size: 0.95rem; }
    .product-qty   { color: #aaa; font-size: 0.82rem; }
    .no-products   { color: #fff8; padding: 18px; border-radius: 12px; background: rgba(0,0,0,0.15); }
    .back-link     { color: #c4b5fd; text-decoration: none; margin-bottom: 16px; display: inline-block; font-size: 0.9rem; }
    .back-link:hover { text-decoration: underline; }
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
        <a href="categorias.php" class="active">📂 Categorías</a>
        <a href="proveedores.php">🏢 Proveedores</a>
        <a href="logout.php" class="logout-link">🚪 Cerrar Sesión</a>
    </div>

    <div class="main-content">
        <div class="header">
            <div>
                <h2><?php echo $config['icon'] . ' ' . htmlspecialchars($cat_name); ?></h2>
                <p class="user-info">
                    👤 <?php echo htmlspecialchars($_SESSION['usuario']); ?> |
                    <?php echo ucfirst(htmlspecialchars($_SESSION['rol'])); ?>
                </p>
            </div>
        </div>

        <div class="page-wrap">
            <a class="back-link" href="categorias.php">← Volver a categorías</a>

            <?php if (count($products) === 0): ?>
                <div class="no-products">
                    No se encontraron productos en <strong><?php echo htmlspecialchars($cat_name); ?></strong>.
                </div>
            <?php else: ?>
                <p style="color:#bbb; margin-bottom:16px; font-size:0.88rem;">
                    <?php echo count($products); ?> productos encontrados
                </p>
                <div class="products-grid">
                    <?php foreach($products as $p): ?>
                        <div class="product-card">
                            <span class="product-badge"><?php echo htmlspecialchars($p['proveedores']); ?></span>
                            <div class="product-title"><?php echo htmlspecialchars($p['nombre']); ?></div>
                            <div class="product-desc"><?php echo htmlspecialchars($p['descripcion']); ?></div>
                            <div class="product-footer">
                                <span class="product-price">$<?php echo number_format(floatval($p['precio']), 2); ?></span>
                                <span class="product-qty"><?php echo intval($p['cantidad']); ?> uds</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>
