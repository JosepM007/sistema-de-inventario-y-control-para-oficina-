<?php
session_start();
if (!isset($_SESSION['usuario'])) { header("Location: login.php"); exit; }
require 'db.php';

function contarProductos($conn, $prov) {
    $p = $conn->real_escape_string($prov);
    $res = $conn->query("SELECT COUNT(*) as t FROM productos WHERE proveedores = '$p'");
    return $res ? $res->fetch_assoc()['t'] : 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proveedores - OfficeStock Pro</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --card-bg: rgba(255,255,255,0.10); --card-border: rgba(255,255,255,0.16); --text-muted: rgba(255,255,255,0.55); }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Nunito', sans-serif; background: linear-gradient(135deg, #0f4c8a 0%, #0a7abf 45%, #00b4d8 100%); min-height: 100vh; color: #fff; }
        .layout { display: flex; min-height: 100vh; }

        .sidebar { width: 220px; flex-shrink: 0; background: rgba(0,0,0,0.28); backdrop-filter: blur(16px); border-right: 1px solid var(--card-border); display: flex; flex-direction: column; padding: 28px 0 24px; position: sticky; top: 0; height: 100vh; }
        .logo { font-size: 19px; font-weight: 800; color: #fff; padding: 0 24px 24px; border-bottom: 1px solid var(--card-border); margin-bottom: 12px; }
        .sidebar a { display: flex; align-items: center; gap: 10px; padding: 11px 24px; color: var(--text-muted); text-decoration: none; font-size: 13.5px; font-weight: 600; border-left: 3px solid transparent; transition: all 0.18s; }
        .sidebar a:hover  { color: #fff; background: rgba(255,255,255,0.08); }
        .sidebar a.active { color: #fff; border-left-color: #00c8e8; background: rgba(0,200,232,0.15); }
        .sidebar .logout-link { margin-top: auto; color: #fca5a5; border-top: 1px solid var(--card-border); padding-top: 14px; }

        .main-content { flex: 1; display: flex; flex-direction: column; }
        .top-bar { padding: 22px 30px 18px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--card-border); background: rgba(0,0,0,0.15); backdrop-filter: blur(6px); }
        .top-bar h1 { font-size: 20px; font-weight: 800; }
        .top-bar .sub { font-size: 12px; color: var(--text-muted); margin-top: 1px; }
        .user-chip { display: inline-flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.12); border: 1px solid var(--card-border); border-radius: 20px; padding: 6px 14px; font-size: 13px; font-weight: 600; }
        .dot { width:8px; height:8px; border-radius:50%; background:#00c8e8; }
        .page-body { padding: 28px 30px 40px; }

        .section-label { display: flex; align-items: center; gap: 10px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.4px; color: #7fecf8; margin-bottom: 16px; margin-top: 30px; }
        .section-label:first-child { margin-top: 0; }
        .section-label::after { content: ''; flex: 1; height: 1px; background: rgba(0,200,232,0.22); }

        .prov-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 16px; margin-bottom: 8px; }

        .prov-card { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: 16px; padding: 20px; display: flex; flex-direction: column; gap: 12px; text-decoration: none; transition: transform 0.18s, box-shadow 0.18s, background 0.18s; position: relative; overflow: hidden; }
        .prov-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg,#0077b6,#00c8e8); opacity: 0; transition: opacity 0.2s; }
        .prov-card:hover { transform: translateY(-5px); box-shadow: 0 18px 42px rgba(0,0,0,0.26); background: rgba(255,255,255,0.14); }
        .prov-card:hover::before { opacity: 1; }

        .prov-top { display: flex; align-items: center; gap: 14px; }
        .prov-icon { width: 52px; height: 52px; border-radius: 13px; background: linear-gradient(135deg,#0077b6,#00b4d8); display: flex; align-items: center; justify-content: center; font-size: 26px; flex-shrink: 0; box-shadow: 0 6px 16px rgba(0,0,0,0.22); }
        .prov-name  { font-size: 17px; font-weight: 800; color: #fff; }
        .prov-count { font-size: 11.5px; color: #7fecf8; font-weight: 600; margin-top: 2px; }
        .prov-desc  { font-size: 13px; color: var(--text-muted); line-height: 1.5; flex: 1; }

        .prov-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.10); }
        .prov-btn { display: inline-flex; align-items: center; gap: 6px; background: linear-gradient(135deg,#0077b6,#00c8e8); color: #fff; border-radius: 9px; padding: 7px 16px; font-size: 12.5px; font-weight: 700; transition: opacity 0.2s; box-shadow: 0 4px 12px rgba(0,119,182,0.32); }
        .prov-card:hover .prov-btn { opacity: 0.88; }
        .arrow { font-size: 14px; color: rgba(255,255,255,0.22); }

        @media (max-width: 768px) { .sidebar{display:none;} .page-body{padding:16px;} }
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
        <a href="categorias.php">📂 Categorías</a>
        <a href="proveedores.php" class="active">🏢 Proveedores</a>
        <a href="salidas.php">📤 Salidas</a>
        <a href="auditoria.php">🔍 Auditoría</a>
        <a href="logout.php" class="logout-link">🚪 Cerrar Sesión</a>
    </div>
    <div class="main-content">
        <div class="top-bar">
            <div><h1>🏢 Proveedores</h1><div class="sub">Inicio / Proveedores</div></div>
            <div class="user-chip"><span class="dot"></span><?php echo htmlspecialchars($_SESSION['usuario']); ?> · <?php echo ucfirst($_SESSION['rol']); ?></div>
        </div>
        <div class="page-body">
            <div class="section-label">💻 Tecnología</div>
            <div class="prov-grid">
                <?php foreach ([
                    ['prov'=>'HP',     'icon'=>'💙','desc'=>'Laptops, equipos de escritorio, monitores e impresoras.'],
                    ['prov'=>'Samsung','icon'=>'📱','desc'=>'Smartphones, tablets, monitores y almacenamiento SSD.'],
                    ['prov'=>'Apple',  'icon'=>'🍎','desc'=>'iPhone, iPad, MacBook y equipos premium.'],
                    ['prov'=>'Lenovo', 'icon'=>'🖥️','desc'=>'ThinkPad, IdeaPad, equipos empresariales y tablets.'],
                    ['prov'=>'Amazon', 'icon'=>'📦','desc'=>'Periféricos, accesorios y equipamiento de oficina.'],
                ] as $p):
                    $cnt = contarProductos($conn, $p['prov']); ?>
                <a class="prov-card" href="proveedor.php?prov=<?php echo urlencode($p['prov']); ?>">
                    <div class="prov-top"><div class="prov-icon"><?php echo $p['icon']; ?></div><div><div class="prov-name"><?php echo htmlspecialchars($p['prov']); ?></div><div class="prov-count"><?php echo $cnt; ?> producto<?php echo $cnt !== 1 ? 's' : ''; ?> registrado<?php echo $cnt !== 1 ? 's' : ''; ?></div></div></div>
                    <div class="prov-desc"><?php echo $p['desc']; ?></div>
                    <div class="prov-footer"><span class="prov-btn">Ver productos →</span><span class="arrow">›</span></div>
                </a>
                <?php endforeach; ?>
            </div>
            <div class="section-label">🏬 Útiles y Mobiliario</div>
            <div class="prov-grid">
                <?php foreach ([
                    ['prov'=>'Walmart','icon'=>'🛒','desc'=>'Útiles de oficina, papelería y artículos de escritorio.'],
                    ['prov'=>'Siman',  'icon'=>'🪑','desc'=>'Mobiliario de oficina, sillas, escritorios y estantes.'],
                ] as $p):
                    $cnt = contarProductos($conn, $p['prov']); ?>
                <a class="prov-card" href="proveedor.php?prov=<?php echo urlencode($p['prov']); ?>">
                    <div class="prov-top"><div class="prov-icon"><?php echo $p['icon']; ?></div><div><div class="prov-name"><?php echo htmlspecialchars($p['prov']); ?></div><div class="prov-count"><?php echo $cnt; ?> producto<?php echo $cnt !== 1 ? 's' : ''; ?> registrado<?php echo $cnt !== 1 ? 's' : ''; ?></div></div></div>
                    <div class="prov-desc"><?php echo $p['desc']; ?></div>
                    <div class="prov-footer"><span class="prov-btn">Ver productos →</span><span class="arrow">›</span></div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>
