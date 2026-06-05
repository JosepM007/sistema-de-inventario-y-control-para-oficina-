<?php
session_start();
if (!isset($_SESSION['usuario'])) { header("Location: login.php"); exit; }
if ($_SESSION['rol'] != 'admin')  { header("Location: dashboard.php"); exit; }
require 'db.php';

// ── Crear tabla auditoria si no existe ───────────────────────────────────────
$conn->query("
    CREATE TABLE IF NOT EXISTS `auditoria` (
        `id`              INT(11) NOT NULL AUTO_INCREMENT,
        `usuario`         VARCHAR(100) DEFAULT NULL,
        `accion`          VARCHAR(50)  DEFAULT NULL,
        `detalle`         TEXT         DEFAULT NULL,
        `tabla_afectada`  VARCHAR(100) DEFAULT NULL,
        `fecha`           DATETIME     DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$filtro_accion  = trim($_GET['accion']  ?? '');
$filtro_usuario = trim($_GET['usuario'] ?? '');
$filtro_fecha   = trim($_GET['fecha']   ?? '');

$where = [];
if ($filtro_accion)  $where[] = "accion LIKE '%" . $conn->real_escape_string($filtro_accion) . "%'";
if ($filtro_usuario) $where[] = "usuario LIKE '%" . $conn->real_escape_string($filtro_usuario) . "%'";
if ($filtro_fecha)   $where[] = "DATE(fecha) = '" . $conn->real_escape_string($filtro_fecha) . "'";
$where_str = $where ? "WHERE " . implode(" AND ", $where) : '';

$historial_res = $conn->query("SELECT * FROM auditoria $where_str ORDER BY fecha DESC LIMIT 100");
$historial = [];
if ($historial_res) while ($r = $historial_res->fetch_assoc()) $historial[] = $r;

$res = $conn->query("SELECT COUNT(*) as t FROM auditoria");
$total_movimientos = $res ? $res->fetch_assoc()['t'] : 0;
$res = $conn->query("SELECT COUNT(*) as t FROM auditoria WHERE DATE(fecha) = CURDATE()");
$movimientos_hoy   = $res ? $res->fetch_assoc()['t'] : 0;
$res = $conn->query("SELECT COUNT(*) as t FROM auditoria WHERE accion = 'ENTRADA'");
$total_entradas    = $res ? $res->fetch_assoc()['t'] : 0;
$res = $conn->query("SELECT COUNT(*) as t FROM auditoria WHERE accion = 'SALIDA'");
$total_salidas_a   = $res ? $res->fetch_assoc()['t'] : 0;

$usuarios_res = $conn->query("SELECT DISTINCT usuario FROM auditoria ORDER BY usuario ASC");
$usuarios_lista = [];
if ($usuarios_res) while ($r = $usuarios_res->fetch_assoc()) $usuarios_lista[] = $r['usuario'];
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditoría - OfficeStock Pro</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --card-bg:    rgba(255,255,255,0.10);
            --card-border:rgba(255,255,255,0.16);
            --text-muted: rgba(255,255,255,0.58);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Nunito', sans-serif;
            background: linear-gradient(135deg, #0f4c8a 0%, #0a7abf 45%, #00b4d8 100%);
            min-height: 100vh; color: #fff;
        }
        .layout { display: flex; min-height: 100vh; }

        .sidebar { width: 220px; flex-shrink: 0; background: rgba(0,0,0,0.28); backdrop-filter: blur(16px); border-right: 1px solid var(--card-border); display: flex; flex-direction: column; padding: 28px 0 24px; position: sticky; top: 0; height: 100vh; }
        .logo { font-size: 19px; font-weight: 800; color: #fff; padding: 0 24px 24px; border-bottom: 1px solid var(--card-border); margin-bottom: 12px; }
        .sidebar a { display: flex; align-items: center; gap: 10px; padding: 11px 24px; color: var(--text-muted); text-decoration: none; font-size: 13.5px; font-weight: 600; border-left: 3px solid transparent; transition: all 0.18s; }
        .sidebar a:hover  { color: #fff; background: rgba(255,255,255,0.08); }
        .sidebar a.active { color: #fff; border-left-color: #00c8e8; background: rgba(0,200,232,0.15); }
        .sidebar .logout-link { margin-top: auto; color: #fca5a5; border-top: 1px solid var(--card-border); padding-top: 12px; }
        .sidebar .logout-link:hover { background: rgba(239,68,68,0.14); color: #fff; }

        .main-content { flex: 1; display: flex; flex-direction: column; overflow-y: auto; }

        .top-bar { padding: 22px 30px 18px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--card-border); background: rgba(0,0,0,0.15); backdrop-filter: blur(6px); position: sticky; top: 0; z-index: 10; }
        .top-bar h1 { font-size: 20px; font-weight: 800; }
        .top-bar .sub { font-size: 12px; color: var(--text-muted); margin-top: 1px; }
        .user-chip { display: inline-flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.12); border: 1px solid var(--card-border); border-radius: 20px; padding: 6px 14px; font-size: 13px; font-weight: 600; }
        .dot { width:8px; height:8px; border-radius:50%; background:#00c8e8; }

        .page-body { padding: 26px 30px 40px; display: flex; flex-direction: column; gap: 22px; }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px,1fr)); gap: 14px; }
        .stat-card { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: 14px; padding: 18px 20px; display: flex; align-items: center; gap: 14px; transition: transform .18s; }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-icon { width:46px; height:46px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:22px; flex-shrink:0; }
        .ic-cyan  { background: rgba(0,200,232,0.22); }
        .ic-green { background: rgba(16,185,129,0.22); }
        .ic-red   { background: rgba(239,68,68,0.20); }
        .ic-amber { background: rgba(245,158,11,0.22); }
        .stat-num   { font-size: 22px; font-weight: 800; line-height:1; }
        .stat-label { font-size: 11.5px; color: var(--text-muted); margin-top: 3px; font-weight: 600; }

        .filtros-card { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: 14px; padding: 18px 22px; }
        .filtros-card h4 { font-size: 13px; font-weight: 700; color: #00c8e8; margin-bottom: 14px; display:flex; align-items:center; gap:6px; }
        .filtros-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px,1fr)); gap: 12px; align-items: end; }
        .filtro-group label { display:block; font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:.7px; margin-bottom:6px; }
        .filtro-group select,
        .filtro-group input { width:100%; background:rgba(255,255,255,0.10); border:1px solid rgba(255,255,255,0.16); border-radius:9px; padding:9px 12px; color:#fff; font-family:'Nunito',sans-serif; font-size:13px; outline:none; transition: border-color .2s; }
        .filtro-group select option { background:#0a4a72; }
        .filtro-group select:focus,
        .filtro-group input:focus { border-color:#00c8e8; }
        .btn-filtrar { background: linear-gradient(90deg,#0077b6,#00b4d8); color:#fff; border:none; border-radius:9px; padding:10px 20px; font-family:'Nunito',sans-serif; font-size:13px; font-weight:700; cursor:pointer; transition:opacity .2s; white-space:nowrap; }
        .btn-filtrar:hover { opacity:.85; }
        .btn-limpiar { background:rgba(255,255,255,0.10); border:1px solid rgba(255,255,255,0.16); color:rgba(255,255,255,.72); border-radius:9px; padding:10px 16px; font-family:'Nunito',sans-serif; font-size:13px; font-weight:600; cursor:pointer; text-decoration:none; white-space:nowrap; transition:background .2s; }
        .btn-limpiar:hover { background:rgba(255,255,255,0.16); color:#fff; }

        .table-card { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: 16px; overflow: hidden; box-shadow: 0 12px 36px rgba(0,0,0,0.18); }
        .table-head { padding: 16px 22px; border-bottom: 1px solid var(--card-border); background: rgba(255,255,255,0.04); display:flex; justify-content:space-between; align-items:center; }
        .table-head h3 { font-size:14px; font-weight:700; display:flex; align-items:center; gap:7px; }
        .count-badge { background:rgba(0,200,232,0.20); color:#7fecf8; border-radius:20px; padding:3px 12px; font-size:12px; font-weight:700; }

        .table-wrap { overflow-x: auto; }
        table { width:100%; border-collapse:collapse; font-size:13px; }
        thead th { padding:12px 16px; text-align:left; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.8px; color:#00c8e8; border-bottom:1px solid var(--card-border); white-space:nowrap; }
        tbody tr { border-bottom:1px solid rgba(255,255,255,0.06); transition:background .15s; }
        tbody tr:last-child { border-bottom:none; }
        tbody tr:hover { background:rgba(255,255,255,0.06); }
        tbody td { padding:12px 16px; color:#fff; vertical-align:middle; }

        .accion-badge { display:inline-flex; align-items:center; gap:5px; padding:4px 11px; border-radius:20px; font-size:11.5px; font-weight:700; white-space:nowrap; }
        .accion-ENTRADA  { background:rgba(16,185,129,0.22);  color:#6ee7b7; border:1px solid rgba(16,185,129,0.32); }
        .accion-SALIDA   { background:rgba(239,68,68,0.20);   color:#fca5a5; border:1px solid rgba(239,68,68,0.30); }
        .accion-EDICION  { background:rgba(245,158,11,0.20);  color:#fcd34d; border:1px solid rgba(245,158,11,0.30); }
        .accion-ELIMINAR { background:rgba(239,68,68,0.28);   color:#fca5a5; border:1px solid rgba(239,68,68,0.42); }
        .accion-LOGIN    { background:rgba(0,200,232,0.22);   color:#7fecf8; border:1px solid rgba(0,200,232,0.32); }
        .accion-DEFAULT  { background:rgba(255,255,255,0.12); color:#e2f4ff; border:1px solid rgba(255,255,255,0.18); }

        .id-badge { display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; border-radius:7px; background:rgba(0,200,232,0.18); color:#7fecf8; font-size:12px; font-weight:700; }
        .usuario-cell { color:#fcd34d; font-weight:700; }
        .detalle-cell { color:var(--text-muted); font-size:12.5px; max-width:280px; }
        .tabla-cell   { color:#7fecf8; font-size:12px; }
        .fecha-cell   { color:var(--text-muted); font-size:12px; white-space:nowrap; }
        .empty-msg    { text-align:center; padding:40px; color:var(--text-muted); }

        @media (max-width:768px) { .sidebar{display:none;} .page-body{padding:16px;} }
    
        /* ♿ ── BARRA DE ACCESIBILIDAD POR VOZ ── */
        .skip-link{position:absolute;top:-50px;left:10px;background:#00c8e8;color:#003;padding:8px 16px;border-radius:8px;font-weight:700;font-size:14px;z-index:9999;transition:top .2s;text-decoration:none}
        .skip-link:focus{top:10px}
        .voz-bar{background:rgba(0,0,0,0.22);border:1px solid rgba(255,255,255,0.16);border-radius:14px;padding:12px 18px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:4px}
        .voz-title{font-size:13px;font-weight:700;color:rgba(255,255,255,0.80);white-space:nowrap}
        .voz-status{font-size:12px;color:rgba(255,255,255,0.45);font-weight:600}
        .voz-status.activo{color:#6ee7b7;animation:vozBlink 1.2s infinite}
        @keyframes vozBlink{0%,100%{opacity:1}50%{opacity:.4}}
        .voz-btns{display:flex;gap:7px;flex-wrap:wrap;margin-left:auto}
        .btn-voz{display:inline-flex;align-items:center;gap:5px;border:none;border-radius:9px;padding:7px 14px;font-family:'Nunito',sans-serif;font-size:12.5px;font-weight:700;cursor:pointer;transition:opacity .15s,transform .12s}
        .btn-voz:hover{opacity:.84;transform:translateY(-1px)}
        .bv-green{background:linear-gradient(135deg,#065f46,#10b981);color:#fff;box-shadow:0 4px 12px rgba(16,185,129,0.28)}
        .bv-cyan{background:rgba(0,200,232,0.18);border:1px solid rgba(0,200,232,0.30);color:#7fecf8}
        .bv-red{background:rgba(239,68,68,0.18);border:1px solid rgba(239,68,68,0.32);color:#fca5a5}

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
        <a href="proveedores.php">🏢 Proveedores</a>
        <a href="nuevo_inventario.php">📋 Inventario</a>
        <a href="salidas.php">📤 Salidas</a>
         <a href="devoluciones.php" class=>↩️ Devoluciones</a>
        <a href="auditoria.php" class="active">🔍 Auditoría</a>
        <a href="logout.php" class="logout-link">🚪 Cerrar Sesión</a>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <div>
                <h1>🔍 Historial de Auditoría</h1>
                <div class="sub">Inicio / Auditoría — Registro de todos los movimientos del sistema</div>
            </div>
            <div class="user-chip"><span class="dot"></span><?php echo htmlspecialchars($_SESSION['usuario']); ?> · Admin</div>
        </div>

        <div class="page-body">
            <!-- ♿ ASISTENTE DE VOZ -->
            <section class="voz-bar" role="region" aria-label="Asistente de voz para personas con discapacidad visual">
                <div class="voz-title" aria-hidden="true">♿ 🔊 Asistente de Voz</div>
                <span class="voz-status" id="vozStatus" aria-live="polite" aria-atomic="true">Listo</span>
                <div class="voz-btns">
                    <button class="btn-voz bv-green" onclick="leerPagina()" aria-label="Leer toda la pagina en voz alta">🔊 Leer página</button>
                    <button class="btn-voz bv-cyan"  onclick="leerAyuda()"  aria-label="Escuchar instrucciones de ayuda">❓ Ayuda</button>
                    <button class="btn-voz bv-red"   onclick="detenerVoz()" aria-label="Detener la lectura de voz">⏹ Detener</button>
                </div>
            </section>


            <div class="stats-grid">
                <div class="stat-card"><div class="stat-icon ic-cyan">📋</div><div><div class="stat-num"><?php echo $total_movimientos; ?></div><div class="stat-label">Total Movimientos</div></div></div>
                <div class="stat-card"><div class="stat-icon ic-amber">📅</div><div><div class="stat-num"><?php echo $movimientos_hoy; ?></div><div class="stat-label">Movimientos Hoy</div></div></div>
                <div class="stat-card"><div class="stat-icon ic-green">📥</div><div><div class="stat-num"><?php echo $total_entradas; ?></div><div class="stat-label">Entradas</div></div></div>
                <div class="stat-card"><div class="stat-icon ic-red">📤</div><div><div class="stat-num"><?php echo $total_salidas_a; ?></div><div class="stat-label">Salidas</div></div></div>
            </div>

            <div class="filtros-card">
                <h4>🔎 Filtrar Historial</h4>
                <form method="GET" action="">
                    <div class="filtros-grid">
                        <div class="filtro-group">
                            <label>Tipo de Acción</label>
                            <select name="accion">
                                <option value="">— Todas —</option>
                                <option value="ENTRADA"  <?php echo $filtro_accion=='ENTRADA'  ?'selected':''; ?>>📥 Entrada</option>
                                <option value="SALIDA"   <?php echo $filtro_accion=='SALIDA'   ?'selected':''; ?>>📤 Salida</option>
                                <option value="EDICION"  <?php echo $filtro_accion=='EDICION'  ?'selected':''; ?>>✏️ Edición</option>
                                <option value="ELIMINAR" <?php echo $filtro_accion=='ELIMINAR' ?'selected':''; ?>>🗑️ Eliminar</option>
                                <option value="LOGIN"    <?php echo $filtro_accion=='LOGIN'    ?'selected':''; ?>>🔐 Login</option>
                            </select>
                        </div>
                        <div class="filtro-group">
                            <label>Usuario</label>
                            <select name="usuario">
                                <option value="">— Todos —</option>
                                <?php foreach ($usuarios_lista as $u): ?>
                                    <option value="<?php echo htmlspecialchars($u); ?>" <?php echo $filtro_usuario==$u?'selected':''; ?>><?php echo htmlspecialchars($u); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filtro-group">
                            <label>Fecha</label>
                            <input type="date" name="fecha" value="<?php echo htmlspecialchars($filtro_fecha); ?>">
                        </div>
                        <div style="display:flex;gap:8px;align-items:flex-end;">
                            <button type="submit" class="btn-filtrar">🔎 Filtrar</button>
                            <a href="auditoria.php" class="btn-limpiar">✕ Limpiar</a>
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-card">
                <div class="table-head">
                    <h3>📋 Registro de Movimientos</h3>
                    <span class="count-badge"><?php echo count($historial); ?> registros</span>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>#</th><th>Acción</th><th>Usuario</th><th>Detalle</th><th>Tabla</th><th>Fecha y Hora</th></tr>
                        </thead>
                        <tbody>
                        <?php if (empty($historial)): ?>
                            <tr><td colspan="6" class="empty-msg">📭 No hay movimientos registrados aún.</td></tr>
                        <?php else: ?>
                            <?php foreach ($historial as $h):
                                $ac = 'accion-' . strtoupper($h['accion']);
                                $ac = in_array($ac, ['accion-ENTRADA','accion-SALIDA','accion-EDICION','accion-ELIMINAR','accion-LOGIN']) ? $ac : 'accion-DEFAULT';
                                $iconos = ['ENTRADA'=>'📥','SALIDA'=>'📤','EDICION'=>'✏️','ELIMINAR'=>'🗑️','LOGIN'=>'🔐'];
                                $ico = $iconos[strtoupper($h['accion'])] ?? '📋';
                            ?>
                            <tr>
                                <td><span class="id-badge"><?php echo $h['id']; ?></span></td>
                                <td><span class="accion-badge <?php echo $ac; ?>"><?php echo $ico . ' ' . htmlspecialchars($h['accion']); ?></span></td>
                                <td><span class="usuario-cell">👤 <?php echo htmlspecialchars($h['usuario']); ?></span></td>
                                <td class="detalle-cell"><?php echo htmlspecialchars($h['detalle'] ?? '—'); ?></td>
                                <td><span class="tabla-cell"><?php echo htmlspecialchars($h['tabla_afectada'] ?? '—'); ?></span></td>
                                <td>
                                    <div class="fecha-cell"><?php echo date('d/m/Y', strtotime($h['fecha'])); ?></div>
                                    <div class="fecha-cell"><?php echo date('H:i:s', strtotime($h['fecha'])); ?></div>
                                </td>
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
<script>
/* ── Lectura de página: Auditoría ── */

/* ═══════════════════════════════════════
   ♿ ASISTENTE DE VOZ — inline, sin voz.js
   ═══════════════════════════════════════ */
const _vs = document.getElementById('vozStatus');
function hablar(texto, encolar) {
    encolar = encolar || false;
    if (!('speechSynthesis' in window)) return;
    if (!encolar) window.speechSynthesis.cancel();
    const u = new SpeechSynthesisUtterance(texto);
    u.lang = 'es-ES'; u.rate = 0.92; u.pitch = 1; u.volume = 1;
    u.onstart = function() { if (_vs) { _vs.textContent = '🔊 Hablando...'; _vs.className = 'voz-status activo'; } };
    u.onend   = function() { if (_vs) { _vs.textContent = 'Listo'; _vs.className = 'voz-status'; } };
    window.speechSynthesis.speak(u);
}
function detenerVoz() {
    window.speechSynthesis.cancel();
    if (_vs) { _vs.textContent = 'Detenido'; _vs.className = 'voz-status'; }
}
function leerAyuda() {
    window.speechSynthesis.cancel();
    var msgs = [
        'Ayuda del asistente de voz para personas con discapacidad visual.',
        'Boton Leer Pagina: lee en voz alta toda la informacion de esta seccion.',
        'Boton Ayuda: reproduce estas instrucciones.',
        'Boton Detener: para la voz inmediatamente.',
        'Usa la tecla Tab para navegar entre los controles.',
        'Usa Enter o Espacio para activar botones y enlaces.',
        'Fin de ayuda.'
    ];
    msgs.forEach(function(t) { hablar(t, true); });
}

function leerPagina() {
    window.speechSynthesis && window.speechSynthesis.cancel();
    
    const stats = [...document.querySelectorAll('.stat-card')].map(c => (c.querySelector('.stat-label')?.textContent || '') + ': ' + (c.querySelector('.stat-num')?.textContent || '')).filter(Boolean);
    const rows  = document.querySelectorAll('tbody tr');
    const frases = [
        'Historial de Auditoría. ' + stats.join('. ') + '.',
        'Se muestran ' + rows.length + ' registros en la tabla.',
        'Puedes filtrar por tipo de acción, usuario o fecha usando los campos de búsqueda.',
    ];
    const primeros = [...rows].slice(0,3);
    primeros.forEach((r,i) => {
        const accion  = r.querySelector('.accion-badge')?.textContent.trim() || '';
        const usuario = r.querySelector('.usuario-cell')?.textContent.trim() || '';
        const detalle = r.querySelector('.detalle-cell')?.textContent.trim() || '';
        const fecha   = r.querySelector('.fecha-cell')?.textContent.trim() || '';
        if (accion) frases.push('Registro ' + (i+1) + ': ' + accion + ' por ' + usuario + '. ' + detalle.substring(0,80) + '. Fecha: ' + fecha + '.');
    });
    if (rows.length > 3) frases.push('Hay ' + (rows.length - 3) + ' registros más en la tabla.');
    frases.push('Fin.');
    frases.forEach(t => hablar(t, true));

};
/* Anunciar alertas automáticamente */
document.querySelectorAll('[role="alert"]').forEach(el => {
    if (el.textContent.trim()) setTimeout(() => hablar(el.textContent.trim(), true), 600);
});
</script>
</body>
</html>
