<?php
session_start();
if (!isset($_SESSION['usuario'])) { header("Location: login.php"); exit; }
require 'db.php';
 
/* ══ ELIMINAR PROVEEDOR (por ID desde BD) ══ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'eliminar') {
    $id = intval($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmt = $conn->prepare("DELETE FROM proveedores WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        header("Location: proveedores.php?msg=eliminado");
        exit;
    }
}
 
/* ══ ELIMINAR PROVEEDOR FIJO (por nombre) ══ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'eliminar_fijo') {
    $nombre_fijo = trim($_POST['nombre_fijo'] ?? '');
    // Los proveedores fijos no están en BD, solo mostramos confirmación visual
    // Si en el futuro se quiere borrar sus productos: DELETE FROM productos WHERE proveedores = ?
    // Por ahora redirigimos con mensaje informativo
    header("Location: proveedores.php?msg=fijo_info");
    exit;
}
 
$msg = $_GET['msg'] ?? '';
 
/* ══ CARGAR PROVEEDORES DESDE BD ══ */
$proveedores_db = [];
$res = $conn->query("SELECT * FROM proveedores ORDER BY categoria, nombre ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $proveedores_db[] = $row;
    }
}
 
/* ══ PROVEEDORES FIJOS (los hardcodeados originalmente) ══ */
$fijos = [
    ['prov'=>'HP',      'categoria'=>'Tecnología',         'icon'=>'💙', 'desc'=>'Laptops, equipos de escritorio, monitores e impresoras.'],
    ['prov'=>'Samsung', 'categoria'=>'Tecnología',         'icon'=>'📱', 'desc'=>'Smartphones, tablets, monitores y almacenamiento SSD.'],
    ['prov'=>'Apple',   'categoria'=>'Tecnología',         'icon'=>'🍎', 'desc'=>'iPhone, iPad, MacBook y equipos premium.'],
    ['prov'=>'Lenovo',  'categoria'=>'Tecnología',         'icon'=>'🖥️', 'desc'=>'ThinkPad, IdeaPad, equipos empresariales y tablets.'],
    ['prov'=>'Amazon',  'categoria'=>'Tecnología',         'icon'=>'📦', 'desc'=>'Periféricos, accesorios y equipamiento de oficina.'],
    ['prov'=>'Walmart', 'categoria'=>'Útiles y Mobiliario','icon'=>'🛒', 'desc'=>'Útiles de oficina, papelería y artículos de escritorio.'],
    ['prov'=>'Siman',   'categoria'=>'Útiles y Mobiliario','icon'=>'🪑', 'desc'=>'Mobiliario de oficina, sillas, escritorios y estantes.'],
];
 
/* ══ ÍCONOS POR CATEGORÍA ══ */
$iconos_cat = [
    'Tecnología'          => '💻',
    'Útiles y Mobiliario' => '🏬',
    'Papelería'           => '📝',
    'Electrodomésticos'   => '🔌',
    'Otro'                => '🏢',
];
 
/* ══ AGRUPAR PROVEEDORES DE BD POR CATEGORÍA ══ */
$por_categoria = [];
foreach ($proveedores_db as $p) {
    $cat = $p['categoria'] ?: 'Otro';
    $por_categoria[$cat][] = $p;
}
 
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
 
        /* ── Sidebar ── */
        .sidebar { width: 220px; flex-shrink: 0; background: rgba(0,0,0,0.28); backdrop-filter: blur(16px); border-right: 1px solid var(--card-border); display: flex; flex-direction: column; padding: 28px 0 24px; position: sticky; top: 0; height: 100vh; }
        .logo { font-size: 19px; font-weight: 800; color: #fff; padding: 0 24px 24px; border-bottom: 1px solid var(--card-border); margin-bottom: 12px; }
        .sidebar a { display: flex; align-items: center; gap: 10px; padding: 11px 24px; color: var(--text-muted); text-decoration: none; font-size: 13.5px; font-weight: 600; border-left: 3px solid transparent; transition: all 0.18s; }
        .sidebar a:hover  { color: #fff; background: rgba(255,255,255,0.08); }
        .sidebar a.active { color: #fff; border-left-color: #00c8e8; background: rgba(0,200,232,0.15); }
        .sidebar .logout-link { margin-top: auto; color: #fca5a5; border-top: 1px solid var(--card-border); padding-top: 14px; }
 
        /* ── Main ── */
        .main-content { flex: 1; display: flex; flex-direction: column; }
        .top-bar { padding: 22px 30px 18px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--card-border); background: rgba(0,0,0,0.15); backdrop-filter: blur(6px); }
        .top-bar h1 { font-size: 20px; font-weight: 800; }
        .top-bar .sub { font-size: 12px; color: var(--text-muted); margin-top: 1px; }
        .user-chip { display: inline-flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.12); border: 1px solid var(--card-border); border-radius: 20px; padding: 6px 14px; font-size: 13px; font-weight: 600; }
        .dot { width:8px; height:8px; border-radius:50%; background:#00c8e8; }
        .page-body { padding: 28px 30px 40px; }
 
        /* ── Section label ── */
        .section-label { display: flex; align-items: center; gap: 10px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.4px; color: #7fecf8; margin-bottom: 16px; margin-top: 30px; }
        .section-label:first-child { margin-top: 0; }
        .section-label::after { content: ''; flex: 1; height: 1px; background: rgba(0,200,232,0.22); }
 
        /* ── Grid de cards ── */
        .prov-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 16px; margin-bottom: 8px; }
 
        /* ── Card base ── */
        .prov-card { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: 16px; padding: 20px; display: flex; flex-direction: column; gap: 12px; text-decoration: none; color: #fff; transition: transform 0.18s, box-shadow 0.18s, background 0.18s; position: relative; overflow: hidden; }
        .prov-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg,#0077b6,#00c8e8); opacity: 0; transition: opacity 0.2s; }
        .prov-card:hover { transform: translateY(-5px); box-shadow: 0 18px 42px rgba(0,0,0,0.26); background: rgba(255,255,255,0.14); }
        .prov-card:hover::before { opacity: 1; }
 
        .prov-top { display: flex; align-items: center; gap: 14px; }
        .prov-icon { width: 52px; height: 52px; border-radius: 13px; background: linear-gradient(135deg,#0077b6,#00b4d8); display: flex; align-items: center; justify-content: center; font-size: 26px; flex-shrink: 0; box-shadow: 0 6px 16px rgba(0,0,0,0.22); }
        .prov-name  { font-size: 17px; font-weight: 800; color: #fff; }
        .prov-count { font-size: 11.5px; color: #7fecf8; font-weight: 600; margin-top: 2px; }
        .prov-desc  { font-size: 13px; color: var(--text-muted); line-height: 1.5; flex: 1; }
 
        .prov-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.10); gap: 8px; }
        .prov-btn { display: inline-flex; align-items: center; gap: 6px; background: linear-gradient(135deg,#0077b6,#00c8e8); color: #fff; border-radius: 9px; padding: 7px 14px; font-size: 12.5px; font-weight: 700; transition: opacity 0.2s; box-shadow: 0 4px 12px rgba(0,119,182,0.32); white-space: nowrap; }
        .prov-card:hover .prov-btn { opacity: 0.88; }
 
        /* ── Card Nuevo Proveedor (verde) ── */
        .prov-card--new::before { background: linear-gradient(90deg, #065f46, #10b981); }
        .prov-icon--new { background: linear-gradient(135deg, #065f46, #10b981); }
        .prov-card--new .prov-btn { background: linear-gradient(135deg, #065f46, #10b981); box-shadow: 0 4px 12px rgba(16,185,129,0.32); }
 
        /* ── Card de BD (acento naranja) ── */
        .prov-card--db::before { background: linear-gradient(90deg, #b45309, #f59e0b); }
        .prov-icon--db { background: linear-gradient(135deg, #b45309, #f59e0b); }
        .prov-card--db .prov-btn { background: linear-gradient(135deg, #b45309, #f59e0b); box-shadow: 0 4px 12px rgba(245,158,11,0.32); }
 
        /* ── Botón eliminar ── */
        .btn-del { display: inline-flex; align-items: center; gap: 5px; background: rgba(239,68,68,0.18); border: 1px solid rgba(239,68,68,0.36); color: #fca5a5; border-radius: 9px; padding: 7px 13px; font-size: 12px; font-weight: 700; cursor: pointer; font-family: 'Nunito', sans-serif; transition: background 0.18s, transform 0.15s; white-space: nowrap; }
        .btn-del:hover { background: rgba(239,68,68,0.35); transform: translateY(-1px); }
 
        /* ── Info extra (solo BD) ── */
        .prov-meta { display: flex; flex-direction: column; gap: 3px; font-size: 12px; color: var(--text-muted); }
        .prov-meta span { display: flex; align-items: center; gap: 5px; }
 
        /* ── Badge ── */
        .badge { display: inline-block; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; padding: 2px 8px; border-radius: 20px; background: rgba(245,158,11,0.20); border: 1px solid rgba(245,158,11,0.35); color: #fde68a; margin-top: 2px; }
 
        /* ── Alertas ── */
        .alert { border-radius: 12px; padding: 14px 18px; font-size: 14px; font-weight: 700; margin-bottom: 22px; display: flex; align-items: center; gap: 10px; }
        .alert-ok    { background: rgba(16,185,129,0.18); border: 1px solid rgba(16,185,129,0.40); color: #6ee7b7; }
        .alert-error { background: rgba(239,68,68,0.18);  border: 1px solid rgba(239,68,68,0.38);  color: #fca5a5; }
        .alert-info  { background: rgba(0,200,232,0.14);  border: 1px solid rgba(0,200,232,0.30);  color: #7fecf8; }
 
        /* ── Modal confirmación ── */
        .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.55); z-index:1000; align-items:center; justify-content:center; }
        .modal-overlay.open { display:flex; }
        .modal-box { background: linear-gradient(135deg,#0f4c8a,#0a7abf); border:1px solid var(--card-border); border-radius:20px; padding:32px; max-width:380px; width:90%; text-align:center; box-shadow:0 30px 80px rgba(0,0,0,0.50); }
        .modal-box h3 { font-size:18px; font-weight:800; margin-bottom:10px; }
        .modal-box p  { font-size:13.5px; color:var(--text-muted); margin-bottom:24px; line-height:1.5; }
        .modal-btns { display:flex; gap:12px; justify-content:center; }
        .modal-btn-cancel { background:rgba(255,255,255,0.10); border:1px solid rgba(255,255,255,0.22); color:#fff; border-radius:10px; padding:10px 22px; font-family:'Nunito',sans-serif; font-size:14px; font-weight:700; cursor:pointer; transition:background .18s; }
        .modal-btn-cancel:hover { background:rgba(255,255,255,0.18); }
        .modal-btn-confirm { background:linear-gradient(135deg,#7f1d1d,#ef4444); border:none; color:#fff; border-radius:10px; padding:10px 22px; font-family:'Nunito',sans-serif; font-size:14px; font-weight:700; cursor:pointer; box-shadow:0 4px 14px rgba(239,68,68,0.35); transition:opacity .18s; }
        .modal-btn-confirm:hover { opacity:.85; }
 
        /* ── Voz ── */
        .voz-bar { background:rgba(0,0,0,0.22); border:1px solid rgba(255,255,255,0.16); border-radius:14px; padding:12px 18px; display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:4px; }
        .voz-title { font-size:13px; font-weight:700; color:rgba(255,255,255,0.80); white-space:nowrap; }
        .voz-status { font-size:12px; color:rgba(255,255,255,0.45); font-weight:600; }
        .voz-status.activo { color:#6ee7b7; animation:vozBlink 1.2s infinite; }
        @keyframes vozBlink{0%,100%{opacity:1}50%{opacity:.4}}
        .voz-btns { display:flex; gap:7px; flex-wrap:wrap; margin-left:auto; }
        .btn-voz { display:inline-flex; align-items:center; gap:5px; border:none; border-radius:9px; padding:7px 14px; font-family:'Nunito',sans-serif; font-size:12.5px; font-weight:700; cursor:pointer; transition:opacity .15s,transform .12s; }
        .btn-voz:hover { opacity:.84; transform:translateY(-1px); }
        .bv-green { background:linear-gradient(135deg,#065f46,#10b981); color:#fff; box-shadow:0 4px 12px rgba(16,185,129,0.28); }
        .bv-cyan  { background:rgba(0,200,232,0.18); border:1px solid rgba(0,200,232,0.30); color:#7fecf8; }
        .bv-red   { background:rgba(239,68,68,0.18); border:1px solid rgba(239,68,68,0.32); color:#fca5a5; }
 
        @media (max-width: 768px) { .sidebar{display:none;} .page-body{padding:16px;} }
</style>
</head>
<body>
<div class="layout">
 
    <!-- ── Sidebar ── -->
<div class="sidebar">
<div class="logo">🗂️ OfficeStock</div>
<a href="dashboard.php">🏠 Dashboard</a>
<?php if ($_SESSION['rol'] == 'admin'): ?>
<a href="productos.php">📦 Productos</a>
<a href="usuarios.php">👥 Usuarios</a>
<?php endif; ?>
<a href="categorias.php">📂 Categorías</a>
<a href="proveedores.php" class="active">🏢 Proveedores</a>
<a href="nuevo_inventario.php">📋 Inventario</a>
<a href="salidas.php">📤 Salidas</a>
<a href="devoluciones.php">↩️ Devoluciones</a>
<a href="auditoria.php">🔍 Auditoría</a>
<a href="logout.php" class="logout-link">🚪 Cerrar Sesión</a>
</div>
 
    <!-- ── Contenido principal ── -->
<div class="main-content">
<div class="top-bar">
<div>
<h1>🏢 Proveedores</h1>
<div class="sub">Inicio / Proveedores</div>
</div>
<div class="user-chip">
<span class="dot"></span>
<?php echo htmlspecialchars($_SESSION['usuario']); ?> · <?php echo ucfirst($_SESSION['rol']); ?>
</div>
</div>
 
        <div class="page-body">
 
            <!-- ♿ Asistente de voz -->
<section class="voz-bar" role="region" aria-label="Asistente de voz">
<div class="voz-title" aria-hidden="true">♿ 🔊 Asistente de Voz</div>
<span class="voz-status" id="vozStatus" aria-live="polite" aria-atomic="true">Listo</span>
<div class="voz-btns">
<button class="btn-voz bv-green" onclick="leerPagina()"  aria-label="Leer toda la página">🔊 Leer página</button>
<button class="btn-voz bv-cyan"  onclick="leerAyuda()"   aria-label="Escuchar ayuda">❓ Ayuda</button>
<button class="btn-voz bv-red"   onclick="detenerVoz()"  aria-label="Detener voz">⏹ Detener</button>
</div>
</section>
 
            <!-- Alertas -->
<?php if ($msg === 'eliminado'): ?>
<div class="alert alert-ok" role="alert">✅ Proveedor eliminado correctamente.</div>
<?php elseif ($msg === 'fijo_info'): ?>
<div class="alert alert-info" role="alert">ℹ️ Los proveedores del sistema (HP, Samsung, Apple, etc.) son fijos y no pueden eliminarse del listado base.</div>
<?php endif; ?>
 
            <!-- ══ SECCIÓN: TECNOLOGÍA (fijos) ══ -->
<div class="section-label">💻 Tecnología</div>
<div class="prov-grid">
<?php foreach ($fijos as $p):
                    if ($p['categoria'] !== 'Tecnología') continue;
                    $cnt = contarProductos($conn, $p['prov']); ?>
<div class="prov-card">
<div class="prov-top">
<div class="prov-icon"><?php echo $p['icon']; ?></div>
<div>
<div class="prov-name"><?php echo htmlspecialchars($p['prov']); ?></div>
<div class="prov-count"><?php echo $cnt; ?> producto<?php echo $cnt !== 1 ? 's' : ''; ?> registrado<?php echo $cnt !== 1 ? 's' : ''; ?></div>
</div>
</div>
<div class="prov-desc"><?php echo htmlspecialchars($p['desc']); ?></div>
<div class="prov-footer">
<a class="prov-btn" href="proveedor.php?prov=<?php echo urlencode($p['prov']); ?>">Ver productos →</a>
<?php if ($_SESSION['rol'] == 'admin'): ?>
<button class="btn-del"
                                onclick="abrirModalFijo('<?php echo htmlspecialchars(addslashes($p['prov'])); ?>')"
                                aria-label="Eliminar proveedor <?php echo htmlspecialchars($p['prov']); ?>">
                            🗑 Eliminar
</button>
<?php endif; ?>
</div>
</div>
<?php endforeach; ?>
</div>
 
            <!-- ══ SECCIÓN: ÚTILES Y MOBILIARIO (fijos) ══ -->
<div class="section-label">🏬 Útiles y Mobiliario</div>
<div class="prov-grid">
<?php foreach ($fijos as $p):
                    if ($p['categoria'] !== 'Útiles y Mobiliario') continue;
                    $cnt = contarProductos($conn, $p['prov']); ?>
<div class="prov-card">
<div class="prov-top">
<div class="prov-icon"><?php echo $p['icon']; ?></div>
<div>
<div class="prov-name"><?php echo htmlspecialchars($p['prov']); ?></div>
<div class="prov-count"><?php echo $cnt; ?> producto<?php echo $cnt !== 1 ? 's' : ''; ?> registrado<?php echo $cnt !== 1 ? 's' : ''; ?></div>
</div>
</div>
<div class="prov-desc"><?php echo htmlspecialchars($p['desc']); ?></div>
<div class="prov-footer">
<a class="prov-btn" href="proveedor.php?prov=<?php echo urlencode($p['prov']); ?>">Ver productos →</a>
<?php if ($_SESSION['rol'] == 'admin'): ?>
<button class="btn-del"
                                onclick="abrirModalFijo('<?php echo htmlspecialchars(addslashes($p['prov'])); ?>')"
                                aria-label="Eliminar proveedor <?php echo htmlspecialchars($p['prov']); ?>">
                            🗑 Eliminar
</button>
<?php endif; ?>
</div>
</div>
<?php endforeach; ?>
</div>
 
            <!-- ══ PROVEEDORES REGISTRADOS EN BD (dinámicos por categoría) ══ -->
<?php if (!empty($por_categoria)): ?>
<?php foreach ($por_categoria as $cat => $lista): ?>
<div class="section-label">
<?php echo ($iconos_cat[$cat] ?? '🏢') . ' ' . htmlspecialchars($cat); ?>
<span style="font-size:10px;color:rgba(255,255,255,0.45);font-weight:600;text-transform:none;letter-spacing:0;margin-left:4px;">(registrados)</span>
</div>
<div class="prov-grid">
<?php foreach ($lista as $p):
                            $cnt = contarProductos($conn, $p['nombre']); ?>
<div class="prov-card prov-card--db">
<div class="prov-top">
<div class="prov-icon prov-icon--db">🏢</div>
<div>
<div class="prov-name"><?php echo htmlspecialchars($p['nombre']); ?></div>
<div class="prov-count"><?php echo $cnt; ?> producto<?php echo $cnt !== 1 ? 's' : ''; ?> asociado<?php echo $cnt !== 1 ? 's' : ''; ?></div>
<span class="badge">BD</span>
</div>
</div>
<?php if (!empty($p['descripcion'])): ?>
<div class="prov-desc"><?php echo htmlspecialchars($p['descripcion']); ?></div>
<?php endif; ?>
<div class="prov-meta">
<?php if (!empty($p['contacto'])): ?>
<span>👤 <?php echo htmlspecialchars($p['contacto']); ?></span>
<?php endif; ?>
<?php if (!empty($p['correo'])): ?>
<span>📧 <?php echo htmlspecialchars($p['correo']); ?></span>
<?php endif; ?>
<?php if (!empty($p['telefono'])): ?>
<span>📞 <?php echo htmlspecialchars($p['telefono']); ?></span>
<?php endif; ?>
<?php if (!empty($p['pais'])): ?>
<span>🌐 <?php echo htmlspecialchars($p['pais']); ?></span>
<?php endif; ?>
</div>
<div class="prov-footer">
<a class="prov-btn" href="proveedor.php?prov=<?php echo urlencode($p['nombre']); ?>">Ver productos →</a>
<?php if ($_SESSION['rol'] == 'admin'): ?>
<button class="btn-del"
                                        onclick="abrirModal(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars(addslashes($p['nombre'])); ?>')"
                                        aria-label="Eliminar proveedor <?php echo htmlspecialchars($p['nombre']); ?>">
                                    🗑 Eliminar
</button>
<?php endif; ?>
</div>
</div>
<?php endforeach; ?>
</div>
<?php endforeach; ?>
<?php else: ?>
<div class="section-label">📋 Proveedores Registrados</div>
<p style="color:var(--text-muted);font-size:14px;margin-bottom:20px;">
                    Aún no hay proveedores registrados en la base de datos.
</p>
<?php endif; ?>
 
            <!-- ══ SECCIÓN: AGREGAR NUEVO ══ -->
<div class="section-label">➕ Registro de Proveedores</div>
<div class="prov-grid">
<a class="prov-card prov-card--new" href="nuevo_proveedor.php">
<div class="prov-top">
<div class="prov-icon prov-icon--new">➕</div>
<div>
<div class="prov-name">Nuevo Proveedor</div>
<div class="prov-count">Registrar proveedor</div>
</div>
</div>
<div class="prov-desc">
                        Completa el formulario para agregar un nuevo proveedor al sistema con toda su información de contacto y categoría.
</div>
<div class="prov-footer">
<span class="prov-btn">Agregar proveedor →</span>
</div>
</a>
</div>
 
        </div><!-- /page-body -->
</div><!-- /main-content -->
</div><!-- /layout -->
 
<!-- ══ MODAL: Confirmar eliminación BD ══ -->
<div class="modal-overlay" id="modalEliminar" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
<div class="modal-box">
<h3 id="modalTitle">🗑 Eliminar Proveedor</h3>
<p id="modalMsg">¿Estás seguro de que deseas eliminar este proveedor? Esta acción no se puede deshacer.</p>
<div class="modal-btns">
<button class="modal-btn-cancel" onclick="cerrarModal()">Cancelar</button>
<form method="POST" action="proveedores.php" id="formEliminar" style="display:inline;">
<input type="hidden" name="action" value="eliminar">
<input type="hidden" name="id" id="modalProvId" value="">
<button type="submit" class="modal-btn-confirm">Sí, eliminar</button>
</form>
</div>
</div>
</div>
 
<!-- ══ MODAL: Proveedor fijo (informativo) ══ -->
<div class="modal-overlay" id="modalFijo" role="dialog" aria-modal="true" aria-labelledby="modalFijoTitle">
<div class="modal-box">
<h3 id="modalFijoTitle">⚠️ Proveedor del Sistema</h3>
<p id="modalFijoMsg">Este es un proveedor base del sistema. ¿Deseas eliminar todos sus productos asociados de la base de datos?</p>
<div class="modal-btns">
<button class="modal-btn-cancel" onclick="cerrarModalFijo()">Cancelar</button>
<form method="POST" action="proveedores.php" id="formEliminarFijo" style="display:inline;">
<input type="hidden" name="action" value="eliminar_fijo">
<input type="hidden" name="nombre_fijo" id="modalFijoNombre" value="">
<button type="submit" class="modal-btn-confirm">Entendido</button>
</form>
</div>
</div>
</div>
 
<script>
/* ── MODAL BD ── */
function abrirModal(id, nombre) {
    document.getElementById('modalProvId').value = id;
    document.getElementById('modalMsg').textContent =
        '¿Estás seguro de que deseas eliminar al proveedor "' + nombre + '"? Esta acción no se puede deshacer.';
    document.getElementById('modalEliminar').classList.add('open');
}
function cerrarModal() {
    document.getElementById('modalEliminar').classList.remove('open');
}
document.getElementById('modalEliminar').addEventListener('click', function(e) {
    if (e.target === this) cerrarModal();
});
 
/* ── MODAL FIJO ── */
function abrirModalFijo(nombre) {
    document.getElementById('modalFijoNombre').value = nombre;
    document.getElementById('modalFijoMsg').textContent =
        '"' + nombre + '" es un proveedor base del sistema. No puede eliminarse del listado, pero sus productos seguirán siendo administrables desde la sección de productos.';
    document.getElementById('modalFijo').classList.add('open');
}
function cerrarModalFijo() {
    document.getElementById('modalFijo').classList.remove('open');
}
document.getElementById('modalFijo').addEventListener('click', function(e) {
    if (e.target === this) cerrarModalFijo();
});
 
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') { cerrarModal(); cerrarModalFijo(); }
});
 
/* ── ASISTENTE DE VOZ ── */
const _vs = document.getElementById('vozStatus');
function hablar(texto, encolar) {
    encolar = encolar || false;
    if (!('speechSynthesis' in window)) return;
    if (!encolar) window.speechSynthesis.cancel();
    const u = new SpeechSynthesisUtterance(texto);
    u.lang = 'es-ES'; u.rate = 0.92; u.pitch = 1; u.volume = 1;
    u.onstart = () => { if(_vs){ _vs.textContent='🔊 Hablando...'; _vs.className='voz-status activo'; } };
    u.onend   = () => { if(_vs){ _vs.textContent='Listo';          _vs.className='voz-status'; } };
    window.speechSynthesis.speak(u);
}
function detenerVoz() { window.speechSynthesis.cancel(); if(_vs){_vs.textContent='Detenido';_vs.className='voz-status';} }
function leerAyuda() {
    window.speechSynthesis.cancel();
    ['Ayuda del asistente de voz.',
     'Botón Leer Página: lee en voz alta todos los proveedores disponibles.',
     'Los proveedores con etiqueta B D son los que registraste en el sistema.',
     'El botón Eliminar solo aparece para administradores y abre una ventana de confirmación.',
     'Fin de ayuda.'
    ].forEach(t => hablar(t, true));
}
function leerPagina() {
    window.speechSynthesis && window.speechSynthesis.cancel();
    const cards = document.querySelectorAll('.prov-card');
    const frases = ['Página de Proveedores. Hay ' + cards.length + ' tarjetas disponibles.'];
    cards.forEach((c, i) => {
        const name  = c.querySelector('.prov-name')?.textContent.trim()  || '';
        const count = c.querySelector('.prov-count')?.textContent.trim() || '';
        const desc  = c.querySelector('.prov-desc')?.textContent.trim()  || '';
        frases.push('Proveedor ' + (i+1) + ': ' + name + '. ' + count + '. ' + desc);
    });
    frases.push('Fin.');
    frases.forEach(t => hablar(t, true));
}
document.querySelectorAll('[role="alert"]').forEach(el => {
    if (el.textContent.trim()) setTimeout(() => hablar(el.textContent.trim(), true), 600);
});
</script>
</body>
</html>
<?php $conn->close(); ?>