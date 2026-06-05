<?php
session_start();
if (!isset($_SESSION['usuario'])) { header("Location: login.php"); exit; }
require 'db.php';
 
$rol = $_SESSION['rol'];
$usuario = $_SESSION['usuario'];
 
// ── Filtros ──────────────────────────────────────────────
$f_estado = trim($_GET['estado'] ?? '');
$f_tipo   = trim($_GET['tipo']   ?? '');
$f_fecha  = trim($_GET['fecha']  ?? '');
$f_buscar = trim($_GET['buscar'] ?? '');
 
$where = ["1=1"];
if ($f_estado) $where[] = "d.estado = '" . $conn->real_escape_string($f_estado) . "'";
if ($f_tipo)   $where[] = "d.tipo_devolucion = '" . $conn->real_escape_string($f_tipo) . "'";
if ($f_fecha)  $where[] = "DATE(d.fecha_solicitud) = '" . $conn->real_escape_string($f_fecha) . "'";
if ($f_buscar) $where[] = "(d.codigo LIKE '%" . $conn->real_escape_string($f_buscar) . "%' OR d.producto_nombre LIKE '%" . $conn->real_escape_string($f_buscar) . "%' OR d.solicitado_por LIKE '%" . $conn->real_escape_string($f_buscar) . "%')";
$where_str = implode(" AND ", $where);
 
$devoluciones_res = $conn->query("SELECT d.*, p.cantidad AS stock_actual FROM devoluciones d LEFT JOIN productos p ON d.producto_id = p.id WHERE $where_str ORDER BY d.fecha_solicitud DESC LIMIT 100");
$devoluciones = [];
while ($r = $devoluciones_res->fetch_assoc()) $devoluciones[] = $r;
 
// ── KPIs ─────────────────────────────────────────────────
$total        = $conn->query("SELECT COUNT(*) as t FROM devoluciones")->fetch_assoc()['t'];
$pendientes   = $conn->query("SELECT COUNT(*) as t FROM devoluciones WHERE estado='pendiente'")->fetch_assoc()['t'];
$aprobadas    = $conn->query("SELECT COUNT(*) as t FROM devoluciones WHERE estado='aprobado'")->fetch_assoc()['t'];
$rechazadas   = $conn->query("SELECT COUNT(*) as t FROM devoluciones WHERE estado='rechazado'")->fetch_assoc()['t'];
$finalizadas  = $conn->query("SELECT COUNT(*) as t FROM devoluciones WHERE estado='finalizado'")->fetch_assoc()['t'];
$hoy          = $conn->query("SELECT COUNT(*) as t FROM devoluciones WHERE DATE(fecha_solicitud)=CURDATE()")->fetch_assoc()['t'];
$reingresadas = $conn->query("SELECT COUNT(*) as t FROM devoluciones WHERE reingresa_stock=1")->fetch_assoc()['t'];
 
// Tipo más frecuente
$tipo_top_res = $conn->query("SELECT tipo_devolucion, COUNT(*) as c FROM devoluciones GROUP BY tipo_devolucion ORDER BY c DESC LIMIT 1");
$tipo_top = $tipo_top_res ? $tipo_top_res->fetch_assoc() : null;
 
// Tiempo promedio resolución (días entre solicitud y finalización)
$tiempo_res = $conn->query("SELECT AVG(TIMESTAMPDIFF(HOUR, fecha_solicitud, fecha_finalizacion)) as avg_h FROM devoluciones WHERE fecha_finalizacion IS NOT NULL");
$tiempo_avg = $tiempo_res ? round(floatval($tiempo_res->fetch_assoc()['avg_h']), 1) : 0;
 
// Producto con más devoluciones
$prod_top_res = $conn->query("SELECT producto_nombre, COUNT(*) as c FROM devoluciones GROUP BY producto_nombre ORDER BY c DESC LIMIT 1");
$prod_top = $prod_top_res ? $prod_top_res->fetch_assoc() : null;
 
// Usuario con más devoluciones
$user_top_res = $conn->query("SELECT solicitado_por, COUNT(*) as c FROM devoluciones GROUP BY solicitado_por ORDER BY c DESC LIMIT 1");
$user_top = $user_top_res ? $user_top_res->fetch_assoc() : null;
 
$conn->close();
 
// ── Helpers ───────────────────────────────────────────────
function badge_estado($e) {
    $map = [
        'pendiente'   => ['🕐','#fcd34d','rgba(245,158,11,0.22)','rgba(245,158,11,0.35)'],
        'revisado'    => ['🔍','#7fecf8','rgba(0,200,232,0.22)','rgba(0,200,232,0.35)'],
        'aprobado'    => ['✅','#6ee7b7','rgba(16,185,129,0.22)','rgba(16,185,129,0.35)'],
        'reingresado' => ['📦','#a78bfa','rgba(139,92,246,0.22)','rgba(139,92,246,0.35)'],
        'finalizado'  => ['🏁','#86efac','rgba(34,197,94,0.22)','rgba(34,197,94,0.35)'],
        'rechazado'   => ['❌','#fca5a5','rgba(239,68,68,0.22)','rgba(239,68,68,0.35)'],
    ];
    $d = $map[$e] ?? ['📋','#fff','rgba(255,255,255,0.1)','rgba(255,255,255,0.2)'];
    return "<span style='display:inline-flex;align-items:center;gap:4px;padding:3px 11px;border-radius:20px;font-size:11.5px;font-weight:700;color:{$d[1]};background:{$d[2]};border:1px solid {$d[3]};white-space:nowrap'>{$d[0]} " . ucfirst($e) . "</span>";
}
function badge_tipo($t) {
    $map = [
        'dañado'        => ['💔','Dañado'],
        'incorrecto'    => ['❓','Incorrecto'],
        'vencido'       => ['⏰','Vencido'],
        'error_despacho'=> ['🚚','Error despacho'],
        'sobrante'      => ['➕','Sobrante'],
        'interno'       => ['🔄','Interno'],
        'cliente'       => ['👤','Cliente'],
    ];
    $d = $map[$t] ?? ['📋', ucfirst($t)];
    return "<span style='font-size:12px;font-weight:700;color:rgba(255,255,255,0.80)'>{$d[0]} {$d[1]}</span>";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Devoluciones - OfficeStock Pro</title>
<link rel="stylesheet" href="css/style.css">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
        :root{--card-bg:rgba(255,255,255,0.10);--card-border:rgba(255,255,255,0.16);--text-muted:rgba(255,255,255,0.55)}
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Nunito',sans-serif;background:linear-gradient(135deg,#0f4c8a 0%,#0a7abf 45%,#00b4d8 100%);min-height:100vh;color:#fff}
        .layout{display:flex;min-height:100vh}
 
        /* Sidebar */
        .sidebar{width:220px;flex-shrink:0;background:rgba(0,0,0,0.28);backdrop-filter:blur(16px);border-right:1px solid var(--card-border);display:flex;flex-direction:column;padding:28px 0 24px;position:sticky;top:0;height:100vh}
        .logo{font-size:19px;font-weight:800;color:#fff;padding:0 24px 24px;border-bottom:1px solid var(--card-border);margin-bottom:12px}
        .sidebar a{display:flex;align-items:center;gap:10px;padding:11px 24px;color:var(--text-muted);text-decoration:none;font-size:13.5px;font-weight:600;border-left:3px solid transparent;transition:all .18s}
        .sidebar a:hover{color:#fff;background:rgba(255,255,255,0.08)}
        .sidebar a.active{color:#fff;border-left-color:#00c8e8;background:rgba(0,200,232,0.15)}
        .sidebar .logout-link{margin-top:auto;color:#fca5a5;border-top:1px solid var(--card-border);padding-top:14px}
 
        /* Main */
        .main-content{flex:1;display:flex;flex-direction:column;overflow-y:auto}
        .top-bar{padding:22px 30px 18px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid var(--card-border);background:rgba(0,0,0,0.15);backdrop-filter:blur(6px);position:sticky;top:0;z-index:10}
        .top-bar h1{font-size:20px;font-weight:800}
        .top-bar .sub{font-size:12px;color:var(--text-muted);margin-top:1px}
        .user-chip{display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,0.12);border:1px solid var(--card-border);border-radius:20px;padding:6px 14px;font-size:13px;font-weight:600}
        .dot{width:8px;height:8px;border-radius:50%;background:#00c8e8}
        .page-body{padding:26px 30px 40px;display:flex;flex-direction:column;gap:22px}
 
        /* KPI grid */
        .kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px}
        .kpi-card{background:var(--card-bg);border:1px solid var(--card-border);border-radius:14px;padding:18px 16px;display:flex;align-items:center;gap:14px;transition:transform .18s}
        .kpi-card:hover{transform:translateY(-3px)}
        .kpi-icon{width:46px;height:46px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0}
        .ic-amber{background:rgba(245,158,11,0.22)} .ic-cyan{background:rgba(0,200,232,0.22)}
        .ic-green{background:rgba(16,185,129,0.22)} .ic-red{background:rgba(239,68,68,0.22)}
        .ic-purple{background:rgba(139,92,246,0.22)} .ic-blue{background:rgba(59,130,246,0.22)}
        .kpi-num{font-size:22px;font-weight:800;line-height:1}
        .kpi-label{font-size:11.5px;color:var(--text-muted);margin-top:3px;font-weight:600}
 
        /* Info cards row */
        .info-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px}
        .info-box{background:var(--card-bg);border:1px solid var(--card-border);border-radius:12px;padding:14px 18px;display:flex;align-items:center;gap:12px}
        .info-box .ib-ico{font-size:26px}
        .info-box .ib-label{font-size:11px;color:var(--text-muted);font-weight:700;text-transform:uppercase;letter-spacing:.5px}
        .info-box .ib-val{font-size:14px;font-weight:800;margin-top:2px}
 
        /* Filtros */
        .filtros-card{background:var(--card-bg);border:1px solid var(--card-border);border-radius:14px;padding:18px 22px}
        .filtros-card h4{font-size:13px;font-weight:700;color:#7fecf8;margin-bottom:14px}
        .filtros-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;align-items:end}
        .fg label{display:block;font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.7px;margin-bottom:6px}
        .fg select,.fg input{width:100%;background:rgba(255,255,255,0.10);border:1px solid rgba(255,255,255,0.18);border-radius:9px;padding:9px 12px;color:#fff;font-family:'Nunito',sans-serif;font-size:13px;outline:none;transition:border-color .2s}
        .fg select option{background:#0a4a72}
        .fg select:focus,.fg input:focus{border-color:#00c8e8}
        .fg input::placeholder{color:rgba(255,255,255,0.35)}
        .btn-filtrar{background:linear-gradient(90deg,#0077b6,#00b4d8);color:#fff;border:none;border-radius:9px;padding:10px 20px;font-family:'Nunito',sans-serif;font-size:13px;font-weight:700;cursor:pointer;transition:opacity .2s;white-space:nowrap}
        .btn-filtrar:hover{opacity:.85}
        .btn-limpiar{background:rgba(255,255,255,0.10);border:1px solid rgba(255,255,255,0.18);color:rgba(255,255,255,.75);border-radius:9px;padding:10px 16px;font-family:'Nunito',sans-serif;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;white-space:nowrap;transition:background .2s}
        .btn-limpiar:hover{background:rgba(255,255,255,0.18);color:#fff}
 
        /* Tabla */
        .table-card{background:var(--card-bg);border:1px solid var(--card-border);border-radius:16px;overflow:hidden;box-shadow:0 12px 36px rgba(0,0,0,0.18)}
        .table-head{padding:16px 22px;border-bottom:1px solid var(--card-border);background:rgba(255,255,255,0.04);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px}
        .table-head h3{font-size:14px;font-weight:700;display:flex;align-items:center;gap:7px}
        .btn-nueva{display:inline-flex;align-items:center;gap:7px;background:linear-gradient(135deg,#065f46,#10b981);color:#fff;text-decoration:none;padding:9px 20px;border-radius:9px;font-size:13px;font-weight:700;transition:opacity .2s,transform .15s;box-shadow:0 4px 14px rgba(16,185,129,0.30)}
        .btn-nueva:hover{opacity:.88;transform:translateY(-1px)}
        .count-badge{background:rgba(0,200,232,0.20);color:#7fecf8;border-radius:20px;padding:3px 12px;font-size:12px;font-weight:700}
        .table-wrap{overflow-x:auto}
        table{width:100%;border-collapse:collapse;font-size:13px}
        thead th{padding:12px 16px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#7fecf8;border-bottom:1px solid var(--card-border);white-space:nowrap}
        tbody tr{border-bottom:1px solid rgba(255,255,255,0.06);transition:background .15s}
        tbody tr:hover{background:rgba(255,255,255,0.07)}
        tbody tr:last-child{border-bottom:none}
        tbody td{padding:12px 16px;vertical-align:middle}
        .codigo-cell{font-family:'Courier New',monospace;font-size:12px;font-weight:700;color:#7fecf8}
        .prod-name{font-weight:700;color:#fff;font-size:13px}
        .user-cell{color:#fcd34d;font-size:12px;font-weight:600}
        .fecha-cell{color:var(--text-muted);font-size:12px}
        .qty-cell{font-weight:800;color:#fff;font-size:14px}
        .empty-msg{text-align:center;padding:50px;color:var(--text-muted)}
        .btn-ver{display:inline-flex;align-items:center;gap:5px;background:rgba(0,200,232,0.18);color:#7fecf8;border:1px solid rgba(0,200,232,0.30);border-radius:8px;padding:6px 14px;font-size:12px;font-weight:700;text-decoration:none;transition:all .2s;white-space:nowrap}
        .btn-ver:hover{background:rgba(0,200,232,0.30);transform:translateY(-1px)}
 
        @media(max-width:768px){.sidebar{display:none}.page-body{padding:16px}}
</style>
</head>
<body>
<div class="layout">
<div class="sidebar">
<div class="logo">🗂️ OfficeStock</div>
<a href="dashboard.php">🏠 Dashboard</a>
<?php if ($rol=='admin'): ?>
<a href="productos.php">📦 Productos</a>
<a href="usuarios.php">👥 Usuarios</a>
<?php endif; ?>
<a href="categorias.php">📂 Categorías</a>
<a href="proveedores.php">🏢 Proveedores</a>
<a href="nuevo_inventario.php">📋 Inventario</a>
<a href="salidas.php">📤 Salidas</a>
<a href="devoluciones.php" class="active">↩️ Devoluciones</a>
<?php if ($rol=='admin'): ?>
<a href="auditoria.php">🔍 Auditoría</a>
<?php endif; ?>
<a href="logout.php" class="logout-link">🚪 Cerrar Sesión</a>
</div>
 
    <div class="main-content">
<div class="top-bar">
<div>
<h1>↩️ Gestión de Devoluciones</h1>
<div class="sub">Inicio / Devoluciones — Control y trazabilidad de retornos</div>
</div>
<div class="user-chip"><span class="dot"></span><?php echo htmlspecialchars($usuario); ?> · <?php echo ucfirst($rol); ?></div>
</div>
 
        <div class="page-body">
 
            <!-- KPIs -->
<div class="kpi-grid">
<div class="kpi-card"><div class="kpi-icon ic-cyan">↩️</div><div><div class="kpi-num"><?php echo $total; ?></div><div class="kpi-label">Total Devoluciones</div></div></div>
<div class="kpi-card"><div class="kpi-icon ic-amber">🕐</div><div><div class="kpi-num"><?php echo $pendientes; ?></div><div class="kpi-label">Pendientes</div></div></div>
<div class="kpi-card"><div class="kpi-icon ic-green">✅</div><div><div class="kpi-num"><?php echo $aprobadas; ?></div><div class="kpi-label">Aprobadas</div></div></div>
<div class="kpi-card"><div class="kpi-icon ic-red">❌</div><div><div class="kpi-num"><?php echo $rechazadas; ?></div><div class="kpi-label">Rechazadas</div></div></div>
<div class="kpi-card"><div class="kpi-icon ic-blue">🏁</div><div><div class="kpi-num"><?php echo $finalizadas; ?></div><div class="kpi-label">Finalizadas</div></div></div>
<div class="kpi-card"><div class="kpi-icon ic-purple">📦</div><div><div class="kpi-num"><?php echo $reingresadas; ?></div><div class="kpi-label">Reingresadas</div></div></div>
<div class="kpi-card"><div class="kpi-icon ic-cyan">📅</div><div><div class="kpi-num"><?php echo $hoy; ?></div><div class="kpi-label">Hoy</div></div></div>
<div class="kpi-card"><div class="kpi-icon ic-amber">⏱️</div><div><div class="kpi-num"><?php echo $tiempo_avg; ?>h</div><div class="kpi-label">Tiempo prom. resolución</div></div></div>
</div>
 
            <!-- Info rápida -->
<div class="info-row">
<div class="info-box">
<div class="ib-ico">🏆</div>
<div>
<div class="ib-label">Tipo más frecuente</div>
<div class="ib-val"><?php echo $tipo_top ? ucfirst(str_replace('_',' ',$tipo_top['tipo_devolucion'])) . ' (' . $tipo_top['c'] . ')' : 'Sin datos'; ?></div>
</div>
</div>
<div class="info-box">
<div class="ib-ico">📦</div>
<div>
<div class="ib-label">Producto más devuelto</div>
<div class="ib-val"><?php echo $prod_top ? htmlspecialchars($prod_top['producto_nombre']) . ' (' . $prod_top['c'] . 'x)' : 'Sin datos'; ?></div>
</div>
</div>
<div class="info-box">
<div class="ib-ico">👤</div>
<div>
<div class="ib-label">Mayor solicitante</div>
<div class="ib-val"><?php echo $user_top ? htmlspecialchars($user_top['solicitado_por']) . ' (' . $user_top['c'] . 'x)' : 'Sin datos'; ?></div>
</div>
</div>
<?php if ($pendientes > 0): ?>
<div class="info-box" style="border-color:rgba(245,158,11,0.40);background:rgba(245,158,11,0.10)">
<div class="ib-ico">⚠️</div>
<div>
<div class="ib-label" style="color:#fcd34d">Atención requerida</div>
<div class="ib-val" style="color:#fcd34d"><?php echo $pendientes; ?> devolucion<?php echo $pendientes!=1?'es':''; ?> sin revisar</div>
</div>
</div>
<?php endif; ?>
</div>
 
            <!-- Filtros -->
<div class="filtros-card">
<h4>🔎 Filtrar Devoluciones</h4>
<form method="GET" action="">
<div class="filtros-grid">
<div class="fg">
<label>Buscar</label>
<input type="text" name="buscar" placeholder="Código, producto, usuario..." value="<?php echo htmlspecialchars($f_buscar); ?>">
</div>
<div class="fg">
<label>Estado</label>
<select name="estado">
<option value="">— Todos —</option>
<?php foreach (['pendiente','revisado','aprobado','reingresado','finalizado','rechazado'] as $e): ?>
<option value="<?php echo $e; ?>" <?php echo $f_estado==$e?'selected':''; ?>><?php echo ucfirst($e); ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="fg">
<label>Tipo</label>
<select name="tipo">
<option value="">— Todos —</option>
<?php foreach (['dañado','incorrecto','vencido','error_despacho','sobrante','interno','cliente'] as $t): ?>
<option value="<?php echo $t; ?>" <?php echo $f_tipo==$t?'selected':''; ?>><?php echo ucfirst(str_replace('_',' ',$t)); ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="fg">
<label>Fecha</label>
<input type="date" name="fecha" value="<?php echo htmlspecialchars($f_fecha); ?>">
</div>
<div style="display:flex;gap:8px;align-items:flex-end">
<button type="submit" class="btn-filtrar">🔎 Filtrar</button>
<a href="devoluciones.php" class="btn-limpiar">✕ Limpiar</a>
</div>
</div>
</form>
</div>
 
            <!-- Tabla -->
<div class="table-card">
<div class="table-head">
<h3>📋 Registro de Devoluciones <span class="count-badge"><?php echo count($devoluciones); ?> registros</span></h3>
<a href="nueva_devolucion.php" class="btn-nueva">➕ Nueva Devolución</a>
</div>
<div class="table-wrap">
<table>
<thead>
<tr>
<th>Código</th>
<th>Producto</th>
<th>Tipo</th>
<th>Cant.</th>
<th>Estado</th>
<th>Solicitado por</th>
<th>Área origen</th>
<th>Fecha</th>
<th>Acciones</th>
</tr>
</thead>
<tbody>
<?php if (empty($devoluciones)): ?>
<tr><td colspan="9" class="empty-msg">📭 No hay devoluciones registradas<?php echo ($f_estado||$f_tipo||$f_fecha||$f_buscar)?' con esos filtros':''; ?>.</td></tr>
<?php else: ?>
<?php foreach ($devoluciones as $d): ?>
<tr>
<td class="codigo-cell"><?php echo htmlspecialchars($d['codigo']); ?></td>
<td><div class="prod-name"><?php echo htmlspecialchars($d['producto_nombre']); ?></div></td>
<td><?php echo badge_tipo($d['tipo_devolucion']); ?></td>
<td class="qty-cell"><?php echo intval($d['cantidad']); ?></td>
<td><?php echo badge_estado($d['estado']); ?></td>
<td class="user-cell">👤 <?php echo htmlspecialchars($d['solicitado_por']); ?></td>
<td style="color:var(--text-muted);font-size:12px"><?php echo htmlspecialchars($d['area_origen'] ?? '—'); ?></td>
<td>
<div class="fecha-cell"><?php echo date('d/m/Y', strtotime($d['fecha_solicitud'])); ?></div>
<div class="fecha-cell"><?php echo date('H:i', strtotime($d['fecha_solicitud'])); ?></div>
</td>
<td><a href="ver_devolucion.php?id=<?php echo $d['id']; ?>" class="btn-ver">👁️ Ver</a></td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</tbody>
</table>
</div>
</div>
 
        </div>
</div>
</div>
</body>
</html>