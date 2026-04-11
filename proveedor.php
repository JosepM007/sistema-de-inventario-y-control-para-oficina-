<?php
session_start();
if (!isset($_SESSION['usuario'])) { header("Location: login.php"); exit; }
require 'db.php';

$proveedores_validos = ['Amazon','Walmart','Siman','HP','Samsung','Apple','Lenovo'];
$prov = isset($_GET['prov']) ? trim($_GET['prov']) : '';
if (!in_array($prov, $proveedores_validos)) { header("Location: proveedores.php"); exit; }

$info = [
    'HP'=>['icon'=>'💙','cat'=>'Tecnología'],'Samsung'=>['icon'=>'📱','cat'=>'Tecnología'],
    'Apple'=>['icon'=>'🍎','cat'=>'Tecnología'],'Lenovo'=>['icon'=>'🖥️','cat'=>'Tecnología'],
    'Amazon'=>['icon'=>'📦','cat'=>'Tecnología'],'Walmart'=>['icon'=>'🛒','cat'=>'Útiles y Mobiliario'],
    'Siman'=>['icon'=>'🪑','cat'=>'Útiles y Mobiliario'],
];
$meta   = $info[$prov] ?? ['icon'=>'🏢','cat'=>'General'];
$p_safe = $conn->real_escape_string($prov);

$result  = $conn->query("SELECT id, nombre, descripcion, cantidad, precio FROM productos WHERE proveedores = '$p_safe' ORDER BY nombre ASC");
$total   = $result ? $result->num_rows : 0;
$valor   = floatval($conn->query("SELECT SUM(cantidad*precio) as t FROM productos WHERE proveedores = '$p_safe'")->fetch_assoc()['t']);
$stock   = $conn->query("SELECT SUM(cantidad) as t FROM productos WHERE proveedores = '$p_safe'")->fetch_assoc()['t'] ?? 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($prov); ?> - OfficeStock Pro</title>
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

        .main-content { flex: 1; display: flex; flex-direction: column; overflow-y: auto; }
        .top-bar { padding: 22px 30px 18px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--card-border); background: rgba(0,0,0,0.15); backdrop-filter: blur(6px); }
        .top-bar h1 { font-size: 20px; font-weight: 800; display:flex; align-items:center; gap:10px; }
        .top-bar .sub { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
        .user-chip { display: inline-flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.12); border: 1px solid var(--card-border); border-radius: 20px; padding: 6px 14px; font-size: 13px; font-weight: 600; }
        .dot { width:8px; height:8px; border-radius:50%; background:#00c8e8; }

        .page-body { padding: 26px 30px 40px; display: flex; flex-direction: column; gap: 22px; }

        .prov-hero { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: 16px; padding: 22px 26px; display: flex; align-items: center; gap: 20px; }
        .hero-icon { width: 64px; height: 64px; border-radius: 16px; background: linear-gradient(135deg,#0077b6,#00c8e8); display: flex; align-items: center; justify-content: center; font-size: 32px; box-shadow: 0 8px 24px rgba(0,0,0,0.28); flex-shrink: 0; }
        .hero-info h2 { font-size: 22px; font-weight: 800; }
        .hero-info .cat { font-size: 12px; color: #7fecf8; font-weight: 700; text-transform:uppercase; letter-spacing:0.7px; margin-top:3px; }
        .hero-stats { margin-left: auto; display: flex; gap: 14px; }
        .hstat { text-align: center; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.12); border-radius: 12px; padding: 12px 18px; min-width: 90px; }
        .hstat-num   { font-size: 20px; font-weight: 800; color: #fff; }
        .hstat-label { font-size: 11px; color: var(--text-muted); margin-top: 2px; }

        .btn-back { display: inline-flex; align-items: center; gap: 7px; background: rgba(255,255,255,0.10); border: 1px solid rgba(255,255,255,0.16); color: rgba(255,255,255,0.78); border-radius: 9px; padding: 8px 16px; font-size: 13px; font-weight: 700; text-decoration: none; align-self: flex-start; transition: background 0.2s, color 0.2s; }
        .btn-back:hover { background: rgba(255,255,255,0.18); color: #fff; }

        .table-card { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: 16px; overflow: hidden; box-shadow: 0 12px 36px rgba(0,0,0,0.18); }
        .table-head { padding: 16px 22px; border-bottom: 1px solid var(--card-border); background: rgba(255,255,255,0.04); display: flex; justify-content: space-between; align-items: center; }
        .table-head h3 { font-size: 14px; font-weight: 700; display:flex; align-items:center; gap:7px; }
        .count-badge { background: rgba(0,200,232,0.20); color: #7fecf8; border-radius: 20px; padding: 3px 12px; font-size: 12px; font-weight: 700; }

        table { width:100%; border-collapse:collapse; font-size:13.5px; }
        thead th { padding:12px 20px; text-align:left; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.8px; color:#7fecf8; border-bottom:1px solid var(--card-border); }
        tbody tr { border-bottom:1px solid rgba(255,255,255,0.06); transition:background .15s; }
        tbody tr:last-child { border-bottom:none; }
        tbody tr:hover { background: rgba(255,255,255,0.07); }
        tbody td { padding:13px 20px; vertical-align:middle; color:#fff; }

        .id-badge { display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; border-radius:7px; background:rgba(0,200,232,0.20); color:#7fecf8; font-size:12px; font-weight:700; }
        .prod-name { font-weight:700; color:#fff; }
        .prod-desc { font-size:12px; color:var(--text-muted); margin-top:2px; }
        .qty-cell { color:#fcd34d; font-weight:700; }
        .qty-low  { color:#fca5a5; }
        .qty-wrap { display:flex; align-items:center; gap:8px; }
        .qty-bar-track { width:50px; height:5px; background:rgba(255,255,255,0.10); border-radius:3px; overflow:hidden; }
        .qty-bar-fill  { height:100%; border-radius:3px; background:linear-gradient(90deg,#0077b6,#00c8e8); }
        .precio-cell { color:#a3ffb0; font-weight:700; }
        .empty-state { text-align:center; padding:50px 20px; color:var(--text-muted); }
        .empty-state .empty-ico { font-size:40px; margin-bottom:12px; }

        @media (max-width:768px) { .sidebar{display:none;} .page-body{padding:16px;} .hero-stats{display:none;} }
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
            <div><h1><?php echo $meta['icon']; ?> <?php echo htmlspecialchars($prov); ?></h1><div class="sub">Inicio / Proveedores / <?php echo htmlspecialchars($prov); ?></div></div>
            <div class="user-chip"><span class="dot"></span><?php echo htmlspecialchars($_SESSION['usuario']); ?> · <?php echo ucfirst($_SESSION['rol']); ?></div>
        </div>
        <div class="page-body">
            <a class="btn-back" href="proveedores.php">← Volver a Proveedores</a>
            <div class="prov-hero">
                <div class="hero-icon"><?php echo $meta['icon']; ?></div>
                <div class="hero-info"><h2><?php echo htmlspecialchars($prov); ?></h2><div class="cat">📂 <?php echo $meta['cat']; ?></div></div>
                <div class="hero-stats">
                    <div class="hstat"><div class="hstat-num"><?php echo $total; ?></div><div class="hstat-label">Productos</div></div>
                    <div class="hstat"><div class="hstat-num"><?php echo number_format($stock); ?></div><div class="hstat-label">Unidades</div></div>
                    <div class="hstat"><div class="hstat-num">$<?php echo number_format($valor, 0); ?></div><div class="hstat-label">Valor total</div></div>
                </div>
            </div>
            <div class="table-card">
                <div class="table-head">
                    <h3>📋 Catálogo de Productos</h3>
                    <span class="count-badge"><?php echo $total; ?> producto<?php echo $total !== 1 ? 's' : ''; ?></span>
                </div>
                <?php if ($total === 0): ?>
                    <div class="empty-state"><div class="empty-ico">📭</div><p>No hay productos registrados para <strong><?php echo htmlspecialchars($prov); ?></strong>.</p></div>
                <?php else:
                    $res_max = $conn->query("SELECT MAX(cantidad) as m FROM productos WHERE proveedores = '$p_safe'");
                    $max_qty = max(1, intval($res_max->fetch_assoc()['m']));
                    $result  = $conn->query("SELECT id, nombre, descripcion, cantidad, precio FROM productos WHERE proveedores = '$p_safe' ORDER BY nombre ASC");
                ?>
                <table>
                    <thead><tr><th>ID</th><th>Producto</th><th>Cantidad</th><th>Precio Unit.</th><th>Subtotal</th></tr></thead>
                    <tbody>
                    <?php while ($row = $result->fetch_assoc()):
                        $subtotal = floatval($row['precio']) * intval($row['cantidad']);
                        $pct = round((intval($row['cantidad']) / $max_qty) * 100);
                        $low = intval($row['cantidad']) < 10;
                    ?>
                    <tr>
                        <td><span class="id-badge"><?php echo $row['id']; ?></span></td>
                        <td><div class="prod-name"><?php echo htmlspecialchars($row['nombre']); ?></div><?php if (!empty($row['descripcion'])): ?><div class="prod-desc"><?php echo htmlspecialchars($row['descripcion']); ?></div><?php endif; ?></td>
                        <td><div class="qty-wrap"><span class="qty-cell <?php echo $low?'qty-low':''; ?>"><?php echo intval($row['cantidad']); ?> uds <?php echo $low?'⚠️':''; ?></span><div class="qty-bar-track"><div class="qty-bar-fill" style="width:<?php echo $pct; ?>%"></div></div></div></td>
                        <td class="precio-cell">$<?php echo number_format(floatval($row['precio']), 2); ?></td>
                        <td style="color:#7fecf8;font-weight:700;">$<?php echo number_format($subtotal, 2); ?></td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>
