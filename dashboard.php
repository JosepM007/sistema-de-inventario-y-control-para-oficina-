<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

require 'db.php';

/* ── Consultas principales ── */
$total_productos = $conn->query("SELECT COUNT(*) as t FROM productos")->fetch_assoc()['t'];
$total_stock     = $conn->query("SELECT SUM(cantidad) as t FROM productos")->fetch_assoc()['t'] ?? 0;
$valor_total     = $conn->query("SELECT SUM(cantidad * precio) as t FROM productos")->fetch_assoc()['t'] ?? 0;

if ($_SESSION['rol'] == 'admin') {
    $total_usuarios = $conn->query("SELECT COUNT(*) as t FROM usuarios")->fetch_assoc()['t'];
}

$bajos_res = $conn->query("SELECT nombre, cantidad, proveedores FROM productos WHERE cantidad < 10 AND cantidad > 0 ORDER BY cantidad ASC LIMIT 5");
$bajos = [];
while ($r = $bajos_res->fetch_assoc()) $bajos[] = $r;

$cero_res = $conn->query("SELECT id, nombre, proveedores FROM productos WHERE cantidad = 0 ORDER BY nombre ASC");
$en_cero  = [];
while ($r = $cero_res->fetch_assoc()) $en_cero[] = $r;
$total_en_cero = count($en_cero);

$prov_top = $conn->query("
    SELECT proveedores, COUNT(*) as total, SUM(cantidad) as unidades
    FROM productos GROUP BY proveedores ORDER BY total DESC LIMIT 1
")->fetch_assoc();

$prov_dist_res = $conn->query("
    SELECT proveedores, COUNT(*) as total FROM productos
    GROUP BY proveedores ORDER BY total DESC LIMIT 5
");
$prov_dist = [];
while ($r = $prov_dist_res->fetch_assoc()) $prov_dist[] = $r;
$max_prov = $prov_dist ? max(array_column($prov_dist, 'total')) : 1;

$recientes_res = $conn->query("SELECT id, nombre, cantidad, precio, proveedores FROM productos ORDER BY id DESC LIMIT 5");
$recientes = [];
while ($r = $recientes_res->fetch_assoc()) $recientes[] = $r;

$mas_caro = $conn->query("SELECT nombre, precio FROM productos ORDER BY precio DESC LIMIT 1")->fetch_assoc();

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - OfficeStock Pro</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --card-bg:     rgba(255,255,255,0.10);
            --card-border: rgba(255,255,255,0.16);
            --text-muted:  rgba(255,255,255,0.60);
            --blue-accent: #00c8e8;
            --blue-mid:    #0077b6;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Nunito', sans-serif;
            background: linear-gradient(135deg, #0f4c8a 0%, #0a7abf 45%, #00b4d8 100%);
            min-height: 100vh;
            color: #fff;
        }

        .layout { display: flex; min-height: 100vh; }

        /* ── Sidebar ── */
        .sidebar {
            width: 220px; flex-shrink: 0;
            background: rgba(0,0,0,0.28); backdrop-filter: blur(16px);
            border-right: 1px solid var(--card-border);
            display: flex; flex-direction: column;
            padding: 28px 0 24px;
            position: sticky; top: 0; height: 100vh;
        }
        .logo { font-size: 19px; font-weight: 800; color: #fff; padding: 0 24px 24px; letter-spacing: -0.4px; border-bottom: 1px solid var(--card-border); margin-bottom: 12px; }
        .sidebar a { display: flex; align-items: center; gap: 10px; padding: 11px 24px; color: var(--text-muted); text-decoration: none; font-size: 13.5px; font-weight: 600; border-left: 3px solid transparent; transition: all 0.18s; }
        .sidebar a:hover  { color: #fff; background: rgba(255,255,255,0.08); }
        .sidebar a.active { color: #fff; border-left-color: #00c8e8; background: rgba(0,200,232,0.15); }
        .sidebar .logout-link { margin-top: auto; color: #fca5a5; border-top: 1px solid var(--card-border); padding-top: 14px; }
        .sidebar .logout-link:hover { background: rgba(239,68,68,0.14); color: #fff; }

        .main-content { flex: 1; display: flex; flex-direction: column; overflow-y: auto; }

        /* ── Top bar ── */
        .top-bar {
            padding: 22px 30px 18px;
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid var(--card-border);
            background: rgba(0,0,0,0.15); backdrop-filter: blur(6px);
            position: sticky; top: 0; z-index: 10;
        }
        .top-bar h1 { font-size: 20px; font-weight: 800; letter-spacing: -0.3px; }
        .top-bar .sub { font-size: 12px; color: var(--text-muted); margin-top: 1px; }
        .user-chip { display: inline-flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.12); border: 1px solid var(--card-border); border-radius: 20px; padding: 6px 14px; font-size: 13px; font-weight: 600; }
        .dot { width:8px; height:8px; border-radius:50%; background:#00c8e8; }

        .page-body { padding: 26px 30px 40px; display: flex; flex-direction: column; gap: 24px; }

        /* ── Stats ── */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 14px; }
        .stat-card {
            background: var(--card-bg); border: 1px solid var(--card-border);
            border-radius: 14px; padding: 18px 20px;
            display: flex; align-items: center; gap: 14px;
            transition: transform 0.18s, box-shadow 0.18s;
        }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 12px 32px rgba(0,0,0,0.20); }
        .stat-card.danger { border-color: rgba(239,68,68,0.45); background: rgba(239,68,68,0.10); }
        .stat-icon { width: 46px; height: 46px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; }
        .ic-cyan   { background: rgba(0,200,232,0.22); }
        .ic-green  { background: rgba(16,185,129,0.22); }
        .ic-amber  { background: rgba(245,158,11,0.22); }
        .ic-blue   { background: rgba(59,130,246,0.22); }
        .ic-red    { background: rgba(239,68,68,0.20); }
        .stat-num   { font-size: 22px; font-weight: 800; line-height: 1; }
        .stat-label { font-size: 11.5px; color: var(--text-muted); margin-top: 3px; font-weight: 600; }
        .stat-num.danger { color: #fca5a5; }

        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }

        /* ── Card genérica ── */
        .card { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: 16px; overflow: hidden; }
        .card-head { padding: 16px 20px; border-bottom: 1px solid var(--card-border); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.04); }
        .card-head h3 { font-size: 14px; font-weight: 700; display:flex; align-items:center; gap:7px; }
        .card-body { padding: 18px 20px; }
        .ver-todo { font-size: 12px; color: #00c8e8; text-decoration: none; font-weight: 600; }
        .ver-todo:hover { text-decoration: underline; }

        /* ── Accesos rápidos ── */
        .accesos { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px,1fr)); gap: 12px; }
        .acceso-btn { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; padding: 18px 12px; border-radius: 12px; text-decoration: none; font-size: 12.5px; font-weight: 700; color: #fff; text-align: center; transition: transform 0.18s, opacity 0.18s; border: 1px solid rgba(255,255,255,0.10); }
        .acceso-btn:hover { transform: translateY(-3px); opacity: 0.88; }
        .acceso-btn .ico { font-size: 26px; }
        .ab-green  { background: linear-gradient(135deg,#065f46,#10b981); box-shadow: 0 4px 16px rgba(16,185,129,0.28); }
        .ab-cyan   { background: linear-gradient(135deg,#0077b6,#00b4d8); box-shadow: 0 4px 16px rgba(0,180,216,0.28); }
        .ab-amber  { background: linear-gradient(135deg,#92400e,#f59e0b); box-shadow: 0 4px 16px rgba(245,158,11,0.22); }
        .ab-blue   { background: linear-gradient(135deg,#1e3a5f,#3b82f6); box-shadow: 0 4px 16px rgba(59,130,246,0.22); }
        .ab-red    { background: linear-gradient(135deg,#7f1d1d,#ef4444); box-shadow: 0 4px 16px rgba(239,68,68,0.28); }

        /* ── Alertas ── */
        .alert-strip { display: flex; align-items: flex-start; gap: 14px; background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.30); border-radius: 14px; padding: 16px 20px; }
        .alert-strip .alert-ico { font-size: 26px; flex-shrink:0; margin-top:2px; }
        .alert-strip h4 { font-size:13.5px; font-weight:700; color:#fca5a5; margin-bottom:8px; }
        .bajo-item { display: flex; justify-content: space-between; align-items: center; padding: 6px 10px; border-radius: 8px; background: rgba(239,68,68,0.10); margin-bottom: 6px; font-size: 13px; }
        .bajo-item:last-child { margin-bottom: 0; }
        .bajo-item .nombre { font-weight: 600; }
        .bajo-badge { font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 10px; background: rgba(239,68,68,0.32); color: #fca5a5; }
        .no-bajo { font-size: 13px; color: #6ee7b7; display:flex; align-items:center; gap:6px; }

        .alert-cero {
            display: flex; align-items: flex-start; gap: 14px;
            background: rgba(239,68,68,0.18);
            border: 2px solid rgba(239,68,68,0.52);
            border-radius: 14px; padding: 16px 20px;
            animation: pulseRed 2s infinite;
        }
        @keyframes pulseRed {
            0%,100% { border-color: rgba(239,68,68,0.52); box-shadow: 0 0 0 0 rgba(239,68,68,0); }
            50%      { border-color: rgba(239,68,68,0.90); box-shadow: 0 0 0 6px rgba(239,68,68,0.10); }
        }
        .alert-cero .alert-ico { font-size: 28px; flex-shrink:0; margin-top:2px; }
        .alert-cero h4 { font-size:14px; font-weight:700; color:#fca5a5; margin-bottom:10px; display:flex; align-items:center; gap:8px; }
        .cero-badge-count { background:#ef4444; color:#fff; border-radius:20px; padding:2px 10px; font-size:12px; font-weight:700; }
        .cero-item { display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; border-radius: 9px; background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.25); margin-bottom: 8px; font-size: 13px; }
        .cero-item:last-child { margin-bottom: 0; }
        .cero-item .nombre { font-weight: 600; color: #fff; }
        .cero-item .prov   { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
        .sin-stock-badge { font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 10px; background: #ef4444; color: #fff; white-space: nowrap; }
        .btn-reponer { display: inline-flex; align-items: center; gap: 6px; background: linear-gradient(135deg,#7f1d1d,#ef4444); color: #fff; border-radius: 9px; padding: 7px 16px; font-size: 12.5px; font-weight: 700; text-decoration: none; margin-top: 12px; transition: opacity .2s; box-shadow: 0 4px 14px rgba(239,68,68,0.32); }
        .btn-reponer:hover { opacity: .85; }

        /* ── Barras proveedor ── */
        .prov-row { margin-bottom: 12px; }
        .prov-row:last-child { margin-bottom: 0; }
        .prov-info { display:flex; justify-content:space-between; font-size:12.5px; margin-bottom:5px; }
        .prov-name { font-weight:600; }
        .prov-num  { color: var(--text-muted); }
        .bar-track { background: rgba(255,255,255,0.10); border-radius: 6px; height: 7px; overflow:hidden; }
        .bar-fill  { height: 100%; border-radius: 6px; background: linear-gradient(90deg, #0077b6, #00c8e8); transition: width 0.8s ease; }

        /* ── Tabla recientes ── */
        .mini-table { width:100%; border-collapse:collapse; font-size:13px; }
        .mini-table thead th { font-size: 10.5px; text-transform:uppercase; letter-spacing:0.7px; color: #00c8e8; padding: 0 10px 10px; text-align:left; font-weight:700; }
        .mini-table tbody tr { border-top: 1px solid rgba(255,255,255,0.07); transition: background 0.15s; }
        .mini-table tbody tr:hover { background: rgba(255,255,255,0.06); }
        .mini-table tbody td { padding: 10px; color: #fff; vertical-align:middle; }
        .badge-prov { background: rgba(0,200,232,0.20); color: #7fecf8; border-radius: 6px; padding: 2px 8px; font-size: 11px; font-weight: 700; }
        .precio-cell { color: #a3ffb0; font-weight: 700; }
        .qty-cell    { color: #fcd34d; font-weight: 700; }
        .qty-cero    { color: #fca5a5; font-weight: 700; }

        /* ── Destacados ── */
        .highlight-box { background: linear-gradient(135deg, rgba(0,200,232,0.18), rgba(0,119,182,0.12)); border: 1px solid rgba(0,200,232,0.28); border-radius: 12px; padding: 16px 18px; display: flex; align-items: center; gap: 14px; }
        .hl-icon  { font-size: 32px; }
        .hl-label { font-size: 11px; color: var(--text-muted); text-transform:uppercase; letter-spacing:0.7px; font-weight:600; }
        .hl-val   { font-size: 16px; font-weight: 700; margin-top: 2px; }

        /* ── Modal ── */
        .modal-overlay { display: none; position: fixed; inset: 0; z-index: 1000; background: rgba(0,0,0,0.72); backdrop-filter: blur(6px); align-items: center; justify-content: center; animation: fadeIn .25s ease; }
        .modal-overlay.show { display: flex; }
        @keyframes fadeIn { from{opacity:0} to{opacity:1} }
        .modal { background: linear-gradient(145deg, #071e38, #0a4a72); border: 2px solid rgba(239,68,68,0.45); border-radius: 20px; padding: 32px; max-width: 480px; width: 90%; box-shadow: 0 32px 80px rgba(0,0,0,0.55); animation: slideUp .3s ease; position: relative; }
        @keyframes slideUp { from{opacity:0;transform:translateY(24px)} to{opacity:1;transform:translateY(0)} }
        .modal-icon { font-size: 48px; text-align: center; margin-bottom: 14px; animation: shake 0.5s ease 0.3s; }
        @keyframes shake { 0%,100%{transform:rotate(0)} 20%{transform:rotate(-8deg)} 40%{transform:rotate(8deg)} 60%{transform:rotate(-5deg)} 80%{transform:rotate(5deg)} }
        .modal h2 { font-size: 18px; font-weight: 800; color: #fca5a5; text-align: center; margin-bottom: 6px; }
        .modal .modal-sub { font-size: 13px; color: var(--text-muted); text-align: center; margin-bottom: 20px; }
        .modal-list { display: flex; flex-direction: column; gap: 8px; margin-bottom: 22px; max-height: 220px; overflow-y: auto; }
        .modal-item { display: flex; justify-content: space-between; align-items: center; background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.25); border-radius: 10px; padding: 10px 14px; }
        .modal-item .m-nombre { font-size: 13.5px; font-weight: 700; color: #fff; }
        .modal-item .m-prov   { font-size: 11.5px; color: var(--text-muted); margin-top: 2px; }
        .modal-item .m-badge  { background: #ef4444; color: #fff; border-radius: 8px; padding: 3px 10px; font-size: 11px; font-weight: 700; white-space:nowrap; }
        .modal-actions { display: flex; gap: 10px; }
        .modal-btn-primary { flex: 1; padding: 11px; background: linear-gradient(135deg,#7f1d1d,#ef4444); color: #fff; border: none; border-radius: 10px; font-family: 'Nunito', sans-serif; font-size: 13.5px; font-weight: 700; cursor: pointer; text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 6px; transition: opacity .2s; }
        .modal-btn-primary:hover { opacity: .85; }
        .modal-btn-secondary { flex: 1; padding: 11px; background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.72); border: 1px solid rgba(255,255,255,0.15); border-radius: 10px; font-family: 'Nunito', sans-serif; font-size: 13.5px; font-weight: 600; cursor: pointer; transition: background .2s, color .2s; }
        .modal-btn-secondary:hover { background: rgba(255,255,255,0.14); color: #fff; }
        .modal-close { position: absolute; top: 16px; right: 18px; background: none; border: none; color: rgba(255,255,255,0.42); font-size: 20px; cursor: pointer; transition: color .2s; }
        .modal-close:hover { color: #fff; }

        @media (max-width: 1024px) { .two-col { grid-template-columns: 1fr; } }
        @media (max-width: 768px)  { .sidebar { display:none; } .page-body { padding: 16px; } }
    </style>
</head>
<body>

<?php if ($total_en_cero > 0): ?>
<div class="modal-overlay show" id="modalCero">
    <div class="modal">
        <button class="modal-close" onclick="cerrarModal()">✕</button>
        <div class="modal-icon">🚫</div>
        <h2>¡Alerta de Stock Agotado!</h2>
        <div class="modal-sub">
            <?php echo $total_en_cero; ?> producto<?php echo $total_en_cero > 1 ? 's' : ''; ?>
            <?php echo $total_en_cero > 1 ? 'están' : 'está'; ?> completamente agotado<?php echo $total_en_cero > 1 ? 's' : ''; ?>.
            Se requiere reposición inmediata.
        </div>
        <div class="modal-list">
            <?php foreach ($en_cero as $z): ?>
            <div class="modal-item">
                <div>
                    <div class="m-nombre">📦 <?php echo htmlspecialchars($z['nombre']); ?></div>
                    <div class="m-prov">🏢 <?php echo htmlspecialchars($z['proveedores']); ?></div>
                </div>
                <span class="m-badge">SIN STOCK</span>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="modal-actions">
            <a href="productos.php" class="modal-btn-primary">➕ Reponer Ahora</a>
            <button class="modal-btn-secondary" onclick="cerrarModal()">Ver después</button>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="layout">
    <div class="sidebar">
        <div class="logo">🗂️ OfficeStock</div>
        <a href="dashboard.php" class="active">🏠 Dashboard</a>
        <?php if ($_SESSION['rol'] == 'admin'): ?>
            <a href="productos.php">📦 Productos</a>
            <a href="usuarios.php">👥 Usuarios</a>
        <?php endif; ?>
        <a href="categorias.php">📂 Categorías</a>
        <a href="proveedores.php">🏢 Proveedores</a>
        <a href="salidas.php">📤 Salidas</a>
        <a href="auditoria.php">🔍 Auditoría</a>
        <a href="logout.php" class="logout-link">🚪 Cerrar Sesión</a>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <div>
                <h1>🏠 Dashboard</h1>
                <div class="sub">Bienvenido de vuelta, <?php echo htmlspecialchars($_SESSION['usuario']); ?> — <?php echo date('d/m/Y'); ?></div>
            </div>
            <div style="display:flex; align-items:center; gap:12px;">
                <?php if ($total_en_cero > 0): ?>
                <button onclick="document.getElementById('modalCero').classList.add('show')"
                    style="background:rgba(239,68,68,0.22);border:1px solid rgba(239,68,68,0.42);color:#fca5a5;border-radius:20px;padding:6px 14px;font-size:12px;font-weight:700;cursor:pointer;font-family:'Nunito',sans-serif;display:flex;align-items:center;gap:6px;animation:pulseRed 2s infinite;">
                    🚫 <?php echo $total_en_cero; ?> sin stock
                </button>
                <?php endif; ?>
                <div class="user-chip">
                    <span class="dot"></span>
                    <?php echo htmlspecialchars($_SESSION['usuario']); ?> · <?php echo ucfirst($_SESSION['rol']); ?>
                </div>
            </div>
        </div>

        <div class="page-body">

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon ic-cyan">📦</div>
                    <div><div class="stat-num"><?php echo $total_productos; ?></div><div class="stat-label">Total Productos</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon ic-green">🔢</div>
                    <div><div class="stat-num"><?php echo number_format($total_stock); ?></div><div class="stat-label">Unidades en Stock</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon ic-amber">💲</div>
                    <div><div class="stat-num">$<?php echo number_format($valor_total, 0); ?></div><div class="stat-label">Valor del Inventario</div></div>
                </div>
                <?php if ($_SESSION['rol'] == 'admin'): ?>
                <div class="stat-card">
                    <div class="stat-icon ic-blue">👥</div>
                    <div><div class="stat-num"><?php echo $total_usuarios; ?></div><div class="stat-label">Usuarios</div></div>
                </div>
                <?php endif; ?>
                <div class="stat-card">
                    <div class="stat-icon ic-red">⚠️</div>
                    <div><div class="stat-num"><?php echo count($bajos); ?></div><div class="stat-label">Stock Bajo</div></div>
                </div>
                <div class="stat-card <?php echo $total_en_cero > 0 ? 'danger' : ''; ?>">
                    <div class="stat-icon ic-red">🚫</div>
                    <div>
                        <div class="stat-num <?php echo $total_en_cero > 0 ? 'danger' : ''; ?>"><?php echo $total_en_cero; ?></div>
                        <div class="stat-label">Sin Stock</div>
                    </div>
                </div>
            </div>

            <?php if ($_SESSION['rol'] == 'admin'): ?>
            <div class="card">
                <div class="card-head"><h3>⚡ Accesos Rápidos</h3></div>
                <div class="card-body">
                    <div class="accesos">
                        <a href="productos.php"       class="acceso-btn ab-green"><span class="ico">➕</span>Agregar Producto</a>
                        <a href="nuevo_inventario.php" class="acceso-btn ab-cyan"><span class="ico">📋</span>Ver Inventario</a>
                        <a href="usuarios.php"         class="acceso-btn ab-blue"><span class="ico">👥</span>Gestionar Usuarios</a>
                        <a href="categorias.php"       class="acceso-btn ab-amber"><span class="ico">📂</span>Ver Categorías</a>
                        <a href="salidas.php"          class="acceso-btn ab-red"><span class="ico">📤</span>Registrar Salida</a>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($total_en_cero > 0): ?>
            <div class="alert-cero">
                <div class="alert-ico">🚫</div>
                <div style="flex:1;">
                    <h4>Productos Sin Stock — Requieren Reposición Inmediata <span class="cero-badge-count"><?php echo $total_en_cero; ?></span></h4>
                    <?php foreach ($en_cero as $z): ?>
                        <div class="cero-item">
                            <div>
                                <div class="nombre">📦 <?php echo htmlspecialchars($z['nombre']); ?></div>
                                <div class="prov">🏢 <?php echo htmlspecialchars($z['proveedores']); ?></div>
                            </div>
                            <span class="sin-stock-badge">0 uds — SIN STOCK</span>
                        </div>
                    <?php endforeach; ?>
                    <a href="productos.php" class="btn-reponer">➕ Reponer productos ahora</a>
                </div>
            </div>
            <?php endif; ?>

            <div class="alert-strip">
                <div class="alert-ico">🚨</div>
                <div style="flex:1;">
                    <h4>Productos con Stock Bajo (menos de 10 unidades)</h4>
                    <?php if (empty($bajos)): ?>
                        <div class="no-bajo">✅ Todos los productos tienen stock suficiente.</div>
                    <?php else: ?>
                        <?php foreach ($bajos as $b): ?>
                            <div class="bajo-item">
                                <span class="nombre">📦 <?php echo htmlspecialchars($b['nombre']); ?></span>
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <span style="font-size:12px;color:rgba(255,255,255,0.5);"><?php echo htmlspecialchars($b['proveedores']); ?></span>
                                    <span class="bajo-badge"><?php echo intval($b['cantidad']); ?> uds</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="two-col">
                <div class="card">
                    <div class="card-head">
                        <h3>🕒 Últimos Productos Agregados</h3>
                        <a class="ver-todo" href="nuevo_inventario.php">Ver todo →</a>
                    </div>
                    <div class="card-body" style="padding:0 0 6px;">
                        <table class="mini-table">
                            <thead>
                                <tr>
                                    <th style="padding-left:20px;">Producto</th>
                                    <th>Proveedor</th>
                                    <th>Cant.</th>
                                    <th>Precio</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($recientes as $r): ?>
                                <tr>
                                    <td style="padding-left:20px;font-weight:700;"><?php echo htmlspecialchars($r['nombre']); ?></td>
                                    <td><span class="badge-prov"><?php echo htmlspecialchars($r['proveedores']); ?></span></td>
                                    <td class="<?php echo intval($r['cantidad']) === 0 ? 'qty-cero' : 'qty-cell'; ?>">
                                        <?php echo intval($r['cantidad']) === 0 ? '🚫 0' : intval($r['cantidad']); ?>
                                    </td>
                                    <td class="precio-cell">$<?php echo number_format(floatval($r['precio']),2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-head"><h3>🏢 Productos por Proveedor</h3></div>
                    <div class="card-body">
                        <?php foreach ($prov_dist as $pv): ?>
                            <div class="prov-row">
                                <div class="prov-info">
                                    <span class="prov-name"><?php echo htmlspecialchars($pv['proveedores']); ?></span>
                                    <span class="prov-num"><?php echo $pv['total']; ?> productos</span>
                                </div>
                                <div class="bar-track">
                                    <div class="bar-fill" style="width:<?php echo round(($pv['total']/$max_prov)*100); ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="two-col">
                <div class="highlight-box">
                    <div class="hl-icon">🏆</div>
                    <div>
                        <div class="hl-label">Proveedor principal</div>
                        <div class="hl-val"><?php echo $prov_top ? htmlspecialchars($prov_top['proveedores']) . ' — ' . $prov_top['total'] . ' productos' : 'Sin datos'; ?></div>
                    </div>
                </div>
                <div class="highlight-box">
                    <div class="hl-icon">💎</div>
                    <div>
                        <div class="hl-label">Producto más caro</div>
                        <div class="hl-val"><?php echo $mas_caro ? htmlspecialchars($mas_caro['nombre']) . ' — $' . number_format(floatval($mas_caro['precio']),2) : 'Sin datos'; ?></div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
function cerrarModal() { document.getElementById('modalCero').classList.remove('show'); }
document.getElementById('modalCero')?.addEventListener('click', function(e) { if (e.target === this) cerrarModal(); });
</script>
</body>
</html>
