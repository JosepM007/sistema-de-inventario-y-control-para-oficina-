<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}
require 'db.php';

// Opcional: detectar si tienes tabla 'categorias' y usarla. Aquí mostramos las 3 que pediste.
// Normalizamos la etiqueta para urls:
function slug($s) {
    $s = strtolower(trim($s));
    $s = str_replace('á','a',$s);
    $s = str_replace('é','e',$s);
    $s = str_replace('í','i',$s);
    $s = str_replace('ó','o',$s);
    $s = str_replace('ú','u',$s);
    $s = preg_replace('/[^a-z0-9\-]/','-', $s);
    $s = preg_replace('/-+/', '-', $s);
    return $s;
}

$categorias = [
    ['title'=>'Tecnología', 'slug'=>slug('tecnologia'), 'icon'=>'💻'],
    ['title'=>'Mobiliario', 'slug'=>slug('mobiliario'), 'icon'=>'🪑'],
    ['title'=>'Útiles', 'slug'=>slug('utiles'), 'icon'=>'📎']
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Categorías - OfficeStock Pro</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
    /* Estilos específicos para categorías (puedes moverlos a style.css) */
    .page-wrap { padding: 30px; }
    .breadcrumb { color: #fff8; margin-bottom: 16px; font-size:14px; }
    .categories-grid { display:flex; gap:20px; flex-wrap:wrap; }
    .cat-card {
        flex:1 1 280px;
        min-height:140px;
        border-radius:14px;
        padding:18px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.25);
        display:flex; align-items:center; gap:18px;
        cursor:pointer; transition: transform .18s ease, box-shadow .18s ease;
        background: linear-gradient(135deg, rgba(255,255,255,0.06), rgba(255,255,255,0.02));
        backdrop-filter: blur(6px);
        border: 1px solid rgba(255,255,255,0.06);
    }
    .cat-card:hover { transform: translateY(-6px); box-shadow: 0 18px 40px rgba(0,0,0,0.35); }
    .cat-icon { font-size:36px; width:64px; height:64px; display:flex; align-items:center; justify-content:center; border-radius:12px; background: linear-gradient(135deg,#5b21b6,#7c3aed); color:white; box-shadow: 0 6px 18px rgba(0,0,0,0.25); }
    .cat-body { color: white; }
    .cat-title { font-size:18px; font-weight:700; margin-bottom:6px; }
    .cat-desc { font-size:13px; color: #eae8f6b8; }
    .center { display:flex; align-items:center; justify-content:center; flex-direction:column; gap:12px; }
    .btn-secondary { background:transparent; border: 1px solid rgba(255,255,255,0.12); padding:8px 14px; border-radius:10px; color:white; text-decoration:none; }
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
        <a href="categorias.php">📂 Categorías</a>
        <a href="proveedores.php">🏢 Proveedores</a>
        <a href="logout.php" class="logout-link">🚪 Cerrar Sesión</a>
    </div>

    <div class="main-content">
        <div class="header">
            <div>
                <h2 style="color:#fff;">Categorías</h2>
                <p class="user-info"> 👤 <?php echo htmlentities($_SESSION['usuario']); ?> | <?php echo ucfirst(htmlentities($_SESSION['rol'])); ?> </p>
            </div>
        </div>

        <div class="page-wrap">
            <div class="breadcrumb">Inicio / Categorías</div>
            <h3 style="color:white; margin-bottom:14px;">Selecciona una categoría</h3>

            <div class="categories-grid">
                <?php foreach($categorias as $c): ?>
                    <a class="cat-card" href="categoria.php?cat=<?php echo urlencode($c['slug']); ?>" title="<?php echo htmlentities($c['title']); ?>">
                        <div class="cat-icon"><?php echo $c['icon']; ?></div>
                        <div class="cat-body">
                            <div class="cat-title"><?php echo htmlentities($c['title']); ?></div>
                            <div class="cat-desc">Ver todos los productos de <?php echo htmlentities($c['title']); ?>.</div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>

            <div style="margin-top:26px; color:#fff8;">

            </div>
        </div>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>