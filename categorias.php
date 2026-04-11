<?php
session_start();
if (!isset($_SESSION['usuario'])) { header("Location: login.php"); exit; }
require 'db.php';

function slug($s) {
    $map = ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u'];
    $s = strtolower(trim(strtr($s, $map)));
    $s = preg_replace('/[^a-z0-9\-]/','-', $s);
    return preg_replace('/-+/', '-', $s);
}

$categorias = [
    ['title'=>'Tecnología', 'slug'=>slug('tecnologia'), 'icon'=>'💻'],
    ['title'=>'Mobiliario', 'slug'=>slug('mobiliario'), 'icon'=>'🪑'],
    ['title'=>'Útiles',     'slug'=>slug('utiles'),     'icon'=>'📎']
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Categorías - OfficeStock Pro</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Nunito', sans-serif;
            background: linear-gradient(135deg, #0f4c8a 0%, #0a7abf 45%, #00b4d8 100%);
            min-height: 100vh; color: #fff;
        }
        .layout { display: flex; min-height: 100vh; }

        .sidebar { width: 220px; flex-shrink: 0; background: rgba(0,0,0,0.28); backdrop-filter: blur(16px); border-right: 1px solid rgba(255,255,255,0.14); display: flex; flex-direction: column; padding: 28px 0 24px; position: sticky; top: 0; height: 100vh; }
        .logo { font-size: 19px; font-weight: 800; color: #fff; padding: 0 24px 24px; border-bottom: 1px solid rgba(255,255,255,0.14); margin-bottom: 12px; }
        .sidebar a { display: flex; align-items: center; gap: 10px; padding: 11px 24px; color: rgba(255,255,255,0.58); text-decoration: none; font-size: 13.5px; font-weight: 600; border-left: 3px solid transparent; transition: all 0.18s; }
        .sidebar a:hover  { color: #fff; background: rgba(255,255,255,0.08); }
        .sidebar a.active { color: #fff; border-left-color: #00c8e8; background: rgba(0,200,232,0.15); }
        .sidebar .logout-link { margin-top: auto; color: #fca5a5; border-top: 1px solid rgba(255,255,255,0.14); padding-top: 14px; }
        .sidebar .logout-link:hover { background: rgba(239,68,68,0.14); color: #fff; }

        .main-content { flex: 1; padding: 32px 36px; }

        .header { margin-bottom: 8px; }
        .header h2 { font-size: 26px; font-weight: 800; color: #fff; }
        .user-info { color: rgba(255,255,255,0.62); font-size: 13px; font-weight: 600; margin-top: 4px; display: inline-flex; align-items: center; gap: 6px; background: rgba(255,255,255,0.12); padding: 5px 14px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.16); }

        .breadcrumb { color: rgba(255,255,255,0.55); margin: 16px 0 10px; font-size: 13px; }

        .section-title { font-size: 16px; font-weight: 700; color: #fff; margin-bottom: 18px; }

        .categories-grid { display: flex; gap: 18px; flex-wrap: wrap; }

        .cat-card {
            flex: 1 1 260px;
            min-height: 120px;
            border-radius: 16px;
            padding: 20px 22px;
            box-shadow: 0 10px 32px rgba(0,0,0,0.20);
            display: flex; align-items: center; gap: 18px;
            cursor: pointer;
            transition: transform .2s ease, box-shadow .2s ease;
            background: rgba(255,255,255,0.10);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,0.16);
            text-decoration: none;
        }
        .cat-card:hover { transform: translateY(-6px); box-shadow: 0 20px 44px rgba(0,0,0,0.28); border-color: rgba(0,200,232,0.45); }

        .cat-card.nuevo-inv {
            border-color: rgba(163,255,176,0.28);
            background: rgba(16,185,129,0.12);
        }
        .cat-card.nuevo-inv:hover { box-shadow: 0 20px 44px rgba(16,185,129,0.18); }
        .cat-card.nuevo-inv .cat-icon { background: linear-gradient(135deg,#065f46,#10b981); box-shadow: 0 6px 18px rgba(16,185,129,0.38); }
        .cat-card.nuevo-inv .cat-title { color: #a3ffb0; }

        .cat-icon { font-size: 34px; width: 62px; height: 62px; display: flex; align-items: center; justify-content: center; border-radius: 14px; background: linear-gradient(135deg,#0077b6,#00b4d8); color: white; box-shadow: 0 6px 18px rgba(0,0,0,0.22); flex-shrink: 0; }
        .cat-body { color: white; }
        .cat-title { font-size: 17px; font-weight: 800; margin-bottom: 5px; }
        .cat-desc  { font-size: 13px; color: rgba(255,255,255,0.65); }

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
            <h2>📂 Categorías</h2>
            <span class="user-info">👤 <?php echo htmlentities($_SESSION['usuario']); ?> | <?php echo ucfirst(htmlentities($_SESSION['rol'])); ?></span>
        </div>

        <div class="breadcrumb">Inicio / Categorías</div>
        <div class="section-title">Selecciona una categoría</div>

        <div class="categories-grid">
            <?php foreach($categorias as $c): ?>
                <a class="cat-card" href="categoria.php?cat=<?php echo urlencode($c['slug']); ?>">
                    <div class="cat-icon"><?php echo $c['icon']; ?></div>
                    <div class="cat-body">
                        <div class="cat-title"><?php echo htmlentities($c['title']); ?></div>
                        <div class="cat-desc">Ver todos los productos de <?php echo htmlentities($c['title']); ?>.</div>
                    </div>
                </a>
            <?php endforeach; ?>
            <a class="cat-card nuevo-inv" href="nuevo_inventario.php">
                <div class="cat-icon">📋</div>
                <div class="cat-body">
                    <div class="cat-title">Nuevo Inventario</div>
                    <div class="cat-desc">Ver todos los productos ingresados con fecha, proveedor y cantidad.</div>
                </div>
            </a>
        </div>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>
