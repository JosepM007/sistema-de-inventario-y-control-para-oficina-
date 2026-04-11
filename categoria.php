<?php
session_start();
if (!isset($_SESSION['usuario'])) { header("Location: login.php"); exit; }
require 'db.php';

if (!isset($_GET['cat'])) { header("Location: categorias.php"); exit; }

$cat_slug = trim($_GET['cat']);
$categorias_config = [
    'tecnologia' => ['nombre'=>'Tecnología','icon'=>'💻','proveedores'=>['HP','Samsung','Apple','Lenovo','Amazon']],
    'mobiliario' => ['nombre'=>'Mobiliario','icon'=>'🪑','proveedores'=>['Siman']],
    'utiles'     => ['nombre'=>'Útiles',    'icon'=>'📎','proveedores'=>['Walmart']]
];

if (!isset($categorias_config[$cat_slug])) { header("Location: categorias.php"); exit; }

$config   = $categorias_config[$cat_slug];
$cat_name = $config['nombre'];
$provs    = $config['proveedores'];

$placeholders = implode(',', array_fill(0, count($provs), '?'));
$types        = str_repeat('s', count($provs));

$stmt = $conn->prepare("SELECT id, nombre, descripcion, cantidad, precio, proveedores FROM productos WHERE proveedores IN ($placeholders) ORDER BY proveedores, nombre ASC");
$stmt->bind_param($types, ...$provs);
$stmt->execute();
$result = $stmt->get_result();
$products = [];
while ($r = $result->fetch_assoc()) $products[] = $r;
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($cat_name); ?> - OfficeStock Pro</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Nunito', sans-serif; background: linear-gradient(135deg, #0f4c8a 0%, #0a7abf 45%, #00b4d8 100%); min-height: 100vh; color: #fff; }
        .layout { display: flex; min-height: 100vh; }
        .sidebar { width: 220px; flex-shrink: 0; background: rgba(0,0,0,0.28); backdrop-filter: blur(16px); border-right: 1px solid rgba(255,255,255,0.14); display: flex; flex-direction: column; padding: 28px 0 24px; position: sticky; top: 0; height: 100vh; }
        .logo { font-size: 19px; font-weight: 800; color: #fff; padding: 0 24px 24px; border-bottom: 1px solid rgba(255,255,255,0.14); margin-bottom: 12px; }
        .sidebar a { display: flex; align-items: center; gap: 10px; padding: 11px 24px; color: rgba(255,255,255,0.58); text-decoration: none; font-size: 13.5px; font-weight: 600; border-left: 3px solid transparent; transition: all 0.18s; }
        .sidebar a:hover  { color: #fff; background: rgba(255,255,255,0.08); }
        .sidebar a.active { color: #fff; border-left-color: #00c8e8; background: rgba(0,200,232,0.15); }
        .sidebar .logout-link { margin-top: auto; color: #fca5a5; border-top: 1px solid rgba(255,255,255,0.14); padding-top: 14px; }
        .sidebar .logout-link:hover { background: rgba(239,68,68,0.14); color: #fff; }

        .main-content { flex: 1; padding: 32px 36px; }
        .header { margin-bottom: 20px; }
        .header h2 { font-size: 24px; font-weight: 800; }
        .user-info { color: rgba(255,255,255,0.62); font-size: 13px; font-weight: 600; margin-top: 6px; display: inline-flex; align-items: center; gap: 6px; background: rgba(255,255,255,0.12); padding: 5px 14px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.16); }

        .back-link { color: #7fecf8; text-decoration: none; margin-bottom: 20px; display: inline-flex; align-items: center; gap: 6px; font-size: 13.5px; font-weight: 700; transition: color .2s; }
        .back-link:hover { color: #fff; text-decoration: underline; }

        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; margin-top: 16px; }

        .product-card { background: rgba(255,255,255,0.10); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.16); border-radius: 14px; padding: 18px; display: flex; flex-direction: column; gap: 8px; box-shadow: 0 6px 22px rgba(0,0,0,0.18); transition: transform .2s, box-shadow .2s; }
        .product-card:hover { transform: translateY(-4px); box-shadow: 0 16px 36px rgba(0,0,0,0.26); border-color: rgba(0,200,232,0.40); }

        .product-badge { font-size: 0.72rem; background: rgba(0,200,232,0.22); color: #7fecf8; border-radius: 6px; padding: 2px 8px; display: inline-block; width: fit-content; font-weight: 700; }
        .product-title { font-weight: 800; color: #fff; font-size: 0.95rem; }
        .product-desc  { color: rgba(255,255,255,0.60); font-size: 0.82rem; flex: 1; }

        .product-footer { display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255,255,255,0.12); padding-top: 8px; margin-top: 4px; }
        .product-price { color: #a3ffb0; font-weight: 800; font-size: 0.95rem; }
        .product-qty   { color: rgba(255,255,255,0.55); font-size: 0.82rem; font-weight: 600; }

        .no-products { color: rgba(255,255,255,0.70); padding: 20px; border-radius: 12px; background: rgba(0,0,0,0.15); font-size: 14px; }
        .count-label { color: rgba(255,255,255,0.60); margin-bottom: 14px; font-size: 13px; }

        @media (max-width:768px) { .sidebar{display:none;} .main-content{padding:18px;} }
    </style>
</head>
<body>
<div class="layout">
    <div class="sidebar">
        <div class="logo">🗂️ OfficeStock</div>
        <a href="dashboard.php">🏠 Dashboard</a>
        <?php if ($_SESSION['rol'] == 'admin'): ?>
            <a href="productos.php">📦 Productos</a>
            <a href="usuarios.php">👥 Usuarios</a>
        <?php endif; ?>
        <a href="categorias.php" class="active">📂 Categorías</a>
        <a href="proveedores.php">🏢 Proveedores</a>
        <a href="salidas.php">📤 Salidas</a>
        <a href="auditoria.php">🔍 Auditoría</a>
        <a href="logout.php" class="logout-link">🚪 Cerrar Sesión</a>
    </div>

    <div class="main-content">
        <div class="header">
            <h2><?php echo $config['icon'] . ' ' . htmlspecialchars($cat_name); ?></h2>
            <span class="user-info">👤 <?php echo htmlspecialchars($_SESSION['usuario']); ?> | <?php echo ucfirst(htmlspecialchars($_SESSION['rol'])); ?></span>
        </div>

        <a class="back-link" href="categorias.php">← Volver a categorías</a>

        <?php if (count($products) === 0): ?>
            <div class="no-products">No se encontraron productos en <strong><?php echo htmlspecialchars($cat_name); ?></strong>.</div>
        <?php else: ?>
            <div class="count-label"><?php echo count($products); ?> productos encontrados</div>
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
</body>
</html>
<?php $conn->close(); ?>
