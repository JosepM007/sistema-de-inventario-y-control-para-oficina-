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

$slugToName = [
    'tecnologia' => 'Tecnología',
    'mobiliario' => 'Mobiliario',
    'utiles'     => 'Útiles'
];

$cat_name = isset($slugToName[$cat_slug]) ? $slugToName[$cat_slug] : ucfirst($cat_slug);

$products = [];

$colExists = false;
$res = $conn->query("SHOW COLUMNS FROM productos LIKE 'categoria'");
if ($res && $res->num_rows > 0) { 
    $colExists = true; 
}

if ($colExists) {

    $sql = "SELECT id, nombre, descripcion, cantidad, precio 
            FROM productos 
            WHERE LOWER(categoria) = LOWER(?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $cat_name);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($r = $result->fetch_assoc()) { 
        $products[] = $r; 
    }

    $stmt->close();

} else {

    $like = "%$cat_slug%";

    $sql = "SELECT id, nombre, descripcion, cantidad, precio 
            FROM productos 
            WHERE LOWER(nombre) LIKE LOWER(?) 
            OR LOWER(descripcion) LIKE LOWER(?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($r = $result->fetch_assoc()) { 
        $products[] = $r; 
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Productos - <?php echo htmlentities($cat_name); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <style>
    .page-wrap { padding: 28px; }
    .products-grid { display:grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap:18px; }
    .product-card {
        background: linear-gradient(180deg, rgba(255,255,255,0.03), rgba(255,255,255,0.01));
        border-radius:12px; padding:12px; box-shadow: 0 8px 30px rgba(0,0,0,0.2);
        display:flex; flex-direction:column; gap:10px; min-height:160px;
    }
    .product-thumb { 
        height:120px; 
        border-radius:8px; 
        background:#f2f2f2; 
        display:flex;
        align-items:center;
        justify-content:center;
        color:#777;
        font-weight:bold;
    }
    .product-title { font-weight:700; color:#fff; }
    .product-meta { color:#eae8f6b8; font-size:13px; display:flex; justify-content:space-between; align-items:center; gap:6px; }
    .btn-primary { background: linear-gradient(90deg,#7c3aed,#a78bfa); color:white; padding:8px 10px; border-radius:8px; text-decoration:none; display:inline-block;}
    .no-products { color:#fff8; padding:18px; border-radius:12px; background: rgba(0,0,0,0.12); }
    .back-link { color:#fff; text-decoration:none; margin-bottom:12px; display:inline-block; }
    body { background: linear-gradient(180deg, #6d28d9 0%, #8b5cf6 100%); }
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
                <h2 style="color:#fff;"><?php echo htmlentities($cat_name); ?></h2>
                <p class="user-info">
                    👤 <?php echo htmlentities($_SESSION['usuario']); ?> |
                    <?php echo ucfirst(htmlentities($_SESSION['rol'])); ?>
                </p>
            </div>
        </div>

        <div class="page-wrap">
            <a class="back-link" href="categorias.php">← Volver a categorías</a>

            <?php if (count($products) === 0): ?>
                <div class="no-products">
                    No se encontraron productos en <strong><?php echo htmlentities($cat_name); ?></strong>.
                </div>
            <?php else: ?>
                <div class="products-grid">
                    <?php foreach($products as $p): ?>
                        <div class="product-card">
                            <div class="product-thumb">Producto</div>

                            <div>
                                <div class="product-title">
                                    <?php echo htmlentities($p['nombre']); ?>
                                </div>
                                <div class="product-meta">
                                    <div><?php echo intval($p['cantidad']); ?> uds</div>
                                    <div>$<?php echo number_format(floatval($p['precio']), 2); ?></div>
                                </div>
                            </div>

                            <div style="margin-top:auto;">
                                <a class="btn-primary"
                                   href="editar_producto.php?id=<?php echo intval($p['id']); ?>">
                                   Ver / Editar
                                </a>
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