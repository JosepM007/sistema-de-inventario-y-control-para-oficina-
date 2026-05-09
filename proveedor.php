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

// Admin jose puede editar/borrar desde esta vista
$es_admin_jose = (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin' && strtolower($_SESSION['usuario']) === 'jose');

$success = $_SESSION['success'] ?? ''; $error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
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
        .page-body { padding: 26px 30px 40px; display: flex; flex-direction: column; gap: 20px; }
        /* Alertas */
        .alert{display:flex;align-items:center;gap:10px;padding:13px 16px;border-radius:11px;font-size:13.5px;font-weight:600}
        .alert-ok{background:rgba(16,185,129,0.16);border:1px solid rgba(16,185,129,0.32);color:#6ee7b7}
        .alert-err{background:rgba(239,68,68,0.16);border:1px solid rgba(239,68,68,0.32);color:#fca5a5}
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
        .table-head { padding: 16px 22px; border-bottom: 1px solid var(--card-border); background: rgba(255,255,255,0.04); display: flex; justify-content: space-between; align-items: center; flex-wrap:wrap; gap:10px; }
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
        /* Botones admin jose */
        .admin-actions{display:flex;gap:5px;flex-wrap:wrap}
        .btn-edit{display:inline-flex;align-items:center;gap:3px;background:rgba(245,158,11,0.18);border:1px solid rgba(245,158,11,0.35);color:#fcd34d;border-radius:7px;padding:5px 11px;font-size:12px;font-weight:700;text-decoration:none;transition:background .15s}
        .btn-edit:hover{background:rgba(245,158,11,0.35)}
        .btn-del{display:inline-flex;align-items:center;gap:3px;background:rgba(239,68,68,0.18);border:1px solid rgba(239,68,68,0.35);color:#fca5a5;border-radius:7px;padding:5px 11px;font-size:12px;font-weight:700;cursor:pointer;text-decoration:none;transition:background .15s}
        .btn-del:hover{background:rgba(239,68,68,0.35)}
        /* ♿ Barra de voz */
        .voz-bar{background:rgba(0,0,0,0.22);border:1px solid var(--card-border);border-radius:14px;padding:14px 20px;display:flex;align-items:center;gap:12px;flex-wrap:wrap}
        .voz-title{font-size:13px;font-weight:700;color:rgba(255,255,255,0.82);display:flex;align-items:center;gap:7px;white-space:nowrap}
        .voz-status{font-size:12px;color:rgba(255,255,255,0.48);font-weight:600}
        .voz-status.activo{color:#6ee7b7;animation:blink 1.2s infinite}
        @keyframes blink{0%,100%{opacity:1}50%{opacity:.4}}
        .voz-btns{display:flex;gap:8px;flex-wrap:wrap;margin-left:auto}
        .btn-voz{display:inline-flex;align-items:center;gap:6px;border:none;border-radius:9px;padding:8px 15px;font-family:'Nunito',sans-serif;font-size:13px;font-weight:700;cursor:pointer;transition:opacity .15s,transform .12s}
        .btn-voz:hover{opacity:.84;transform:translateY(-1px)}
        .bv-green{background:linear-gradient(135deg,#065f46,#10b981);color:#fff;box-shadow:0 4px 12px rgba(16,185,129,0.28)}
        .bv-cyan{background:rgba(0,200,232,0.18);border:1px solid rgba(0,200,232,0.30);color:#7fecf8}
        .bv-red{background:rgba(239,68,68,0.18);border:1px solid rgba(239,68,68,0.32);color:#fca5a5}
        /* Skip link */
        .skip-link{position:absolute;top:-50px;left:10px;background:#00c8e8;color:#003;padding:8px 16px;border-radius:8px;font-weight:700;font-size:14px;z-index:9999;transition:top .2s;text-decoration:none}
        .skip-link:focus{top:10px}
        @media (max-width:768px) { .sidebar{display:none;} .page-body{padding:16px;} .hero-stats{display:none;} }
    </style>
</head>
<body>
<a class="skip-link" href="#contenido-proveedor">Saltar al contenido principal</a>

<div class="layout">
    <nav class="sidebar" role="navigation" aria-label="Menú principal">
        <div class="logo" role="banner">🗂️ OfficeStock</div>
        <a href="dashboard.php">🏠 Dashboard</a>
        <?php if ($_SESSION['rol'] == 'admin'): ?>
            <a href="productos.php">📦 Productos</a>
            <a href="usuarios.php">👥 Usuarios</a>
        <?php endif; ?>
        <a href="categorias.php">📂 Categorías</a>
        <a href="proveedores.php" class="active" aria-current="page">🏢 Proveedores</a>
        <a href="salidas.php">📤 Salidas</a>
        <a href="auditoria.php">🔍 Auditoría</a>
        <a href="logout.php" class="logout-link">🚪 Cerrar Sesión</a>
    </nav>
    <div class="main-content" id="contenido-proveedor" role="main">
        <header class="top-bar">
            <div><h1><?php echo $meta['icon']; ?> <?php echo htmlspecialchars($prov); ?></h1><div class="sub">Inicio / Proveedores / <?php echo htmlspecialchars($prov); ?></div></div>
            <div class="user-chip" aria-label="Usuario activo"><span class="dot" aria-hidden="true"></span><?php echo htmlspecialchars($_SESSION['usuario']); ?> · <?php echo ucfirst($_SESSION['rol']); ?></div>
        </header>
        <div class="page-body">

            <?php if ($success): ?><div class="alert alert-ok" role="alert" aria-live="polite">✅ <?php echo htmlspecialchars($success); ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-err" role="alert" aria-live="polite">⚠️ <?php echo htmlspecialchars($error); ?></div><?php endif; ?>

            <a class="btn-back" href="proveedores.php" aria-label="Volver a la lista de proveedores">← Volver a Proveedores</a>

            <!-- ♿ BARRA DE ACCESIBILIDAD -->
            <section class="voz-bar" role="region" aria-label="Asistente de voz para personas con discapacidad visual">
                <div class="voz-title" aria-hidden="true">♿ 🔊 Asistente de Voz</div>
                <span class="voz-status" id="vozStatus" aria-live="polite" aria-atomic="true">Listo</span>
                <div class="voz-btns">
                    <button class="btn-voz bv-green" onclick="leerPagina()" aria-label="Leer el catálogo de productos en voz alta">🔊 Leer página</button>
                    <button class="btn-voz bv-cyan" onclick="leerAyuda()" aria-label="Instrucciones de accesibilidad">❓ Ayuda</button>
                    <button class="btn-voz bv-red" onclick="detenerVoz()" aria-label="Detener la voz">⏹ Detener</button>
                </div>
            </section>

            <div class="prov-hero" role="region" aria-label="Información del proveedor <?php echo htmlspecialchars($prov); ?>">
                <div class="hero-icon" aria-hidden="true"><?php echo $meta['icon']; ?></div>
                <div class="hero-info">
                    <h2><?php echo htmlspecialchars($prov); ?></h2>
                    <div class="cat">📂 <?php echo $meta['cat']; ?></div>
                </div>
                <div class="hero-stats" aria-label="Estadísticas del proveedor">
                    <div class="hstat" tabindex="0" aria-label="<?php echo $total; ?> productos"><div class="hstat-num"><?php echo $total; ?></div><div class="hstat-label">Productos</div></div>
                    <div class="hstat" tabindex="0" aria-label="<?php echo number_format($stock); ?> unidades en inventario"><div class="hstat-num"><?php echo number_format($stock); ?></div><div class="hstat-label">Unidades</div></div>
                    <div class="hstat" tabindex="0" aria-label="Valor total: $<?php echo number_format($valor, 0); ?>"><div class="hstat-num">$<?php echo number_format($valor, 0); ?></div><div class="hstat-label">Valor total</div></div>
                </div>
            </div>

            <div class="table-card">
                <div class="table-head">
                    <h3>📋 Catálogo de Productos <?php echo $es_admin_jose ? '· <small style="font-weight:600;color:#fcd34d;font-size:11px;">✏️ Modo admin José</small>' : ''; ?></h3>
                    <span class="count-badge" aria-label="<?php echo $total; ?> productos"><?php echo $total; ?> producto<?php echo $total !== 1 ? 's' : ''; ?></span>
                </div>
                <?php if ($total === 0): ?>
                    <div class="empty-state" role="status"><div class="empty-ico" aria-hidden="true">📭</div><p>No hay productos registrados para <strong><?php echo htmlspecialchars($prov); ?></strong>.</p></div>
                <?php else:
                    $res_max = $conn->query("SELECT MAX(cantidad) as m FROM productos WHERE proveedores = '$p_safe'");
                    $max_qty = max(1, intval($res_max->fetch_assoc()['m']));
                    $result  = $conn->query("SELECT id, nombre, descripcion, cantidad, precio FROM productos WHERE proveedores = '$p_safe' ORDER BY nombre ASC");
                ?>
                <table aria-label="Catálogo de productos del proveedor <?php echo htmlspecialchars($prov); ?>">
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Producto</th>
                            <th scope="col">Cantidad</th>
                            <th scope="col">Precio Unit.</th>
                            <th scope="col">Subtotal</th>
                            <?php if ($es_admin_jose): ?><th scope="col">Acciones</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($row = $result->fetch_assoc()):
                        $subtotal = floatval($row['precio']) * intval($row['cantidad']);
                        $pct = round((intval($row['cantidad']) / $max_qty) * 100);
                        $low = intval($row['cantidad']) < 10;
                    ?>
                    <tr>
                        <td><span class="id-badge" aria-label="ID <?php echo $row['id']; ?>"><?php echo $row['id']; ?></span></td>
                        <td>
                            <div class="prod-name"><?php echo htmlspecialchars($row['nombre']); ?></div>
                            <?php if (!empty($row['descripcion'])): ?><div class="prod-desc"><?php echo htmlspecialchars($row['descripcion']); ?></div><?php endif; ?>
                        </td>
                        <td>
                            <div class="qty-wrap">
                                <span class="qty-cell <?php echo $low?'qty-low':''; ?>" aria-label="<?php echo intval($row['cantidad']); ?> unidades<?php echo $low?' - stock bajo':''; ?>">
                                    <?php echo intval($row['cantidad']); ?> uds <?php echo $low?'⚠️':''; ?>
                                </span>
                                <div class="qty-bar-track" aria-hidden="true"><div class="qty-bar-fill" style="width:<?php echo $pct; ?>%"></div></div>
                            </div>
                        </td>
                        <td class="precio-cell" aria-label="Precio: $<?php echo number_format(floatval($row['precio']), 2); ?>">$<?php echo number_format(floatval($row['precio']), 2); ?></td>
                        <td style="color:#7fecf8;font-weight:700;" aria-label="Subtotal: $<?php echo number_format($subtotal, 2); ?>">$<?php echo number_format($subtotal, 2); ?></td>
                        <?php if ($es_admin_jose): ?>
                        <td>
                            <div class="admin-actions" role="group" aria-label="Acciones para <?php echo htmlspecialchars($row['nombre']); ?>">
                                <a href="editar_producto.php?id=<?php echo $row['id']; ?>&from_prov=<?php echo urlencode($prov); ?>"
                                   class="btn-edit"
                                   aria-label="Editar producto <?php echo htmlspecialchars($row['nombre']); ?>">
                                    ✏️ Editar
                                </a>
                                <a href="eliminar_producto.php?id=<?php echo $row['id']; ?>&from_prov=<?php echo urlencode($prov); ?>"
                                   class="btn-del"
                                   aria-label="Eliminar producto <?php echo htmlspecialchars($row['nombre']); ?>"
                                   onclick="return confirm('¿Eliminar el producto \"<?php echo addslashes($row['nombre']); ?>\"?\nEsta acción no se puede deshacer.')">
                                    🗑️ Eliminar
                                </a>
                            </div>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
const statusEl = document.getElementById('vozStatus');

function hablar(texto, encolar = false) {
    if (!('speechSynthesis' in window)) return;
    if (!encolar) window.speechSynthesis.cancel();
    const utt = new SpeechSynthesisUtterance(texto);
    utt.lang = 'es-ES'; utt.rate = 0.92; utt.pitch = 1; utt.volume = 1;
    utt.onstart = () => { statusEl.textContent = '🔊 Hablando...'; statusEl.className = 'voz-status activo'; };
    utt.onend   = () => { statusEl.textContent = 'Listo'; statusEl.className = 'voz-status'; };
    window.speechSynthesis.speak(utt);
}
function detenerVoz() {
    window.speechSynthesis.cancel();
    statusEl.textContent = 'Detenido'; statusEl.className = 'voz-status';
}
function leerPagina() {
    window.speechSynthesis.cancel();
    const prov = <?php echo json_encode($prov); ?>;
    const total = <?php echo intval($total); ?>;
    const stockTotal = <?php echo intval($stock); ?>;
    const valor = <?php echo floatval($valor); ?>;
    const esAdmin = <?php echo $es_admin_jose ? 'true' : 'false'; ?>;

    const textos = [
        'Estás en la vista del proveedor ' + prov + '.',
        'Este proveedor tiene ' + total + ' productos registrados. ' + stockTotal + ' unidades en inventario. Valor total: ' + Math.round(valor) + ' dólares.',
    ];
    if (esAdmin) {
        textos.push('Eres administrador José. Puedes editar o eliminar cada producto usando los botones de la columna Acciones.');
    }
    textos.push('Para volver a la lista de proveedores, usa el botón Volver a Proveedores.');
    textos.push('Fin de la lectura.');
    textos.forEach(t => hablar(t, true));
}
function leerAyuda() {
    window.speechSynthesis.cancel();
    [
        'Ayuda del asistente de voz.',
        'Botón Leer Página: lee la información y el catálogo del proveedor.',
        'Botón Detener: para la voz inmediatamente.',
        'Usa la tecla Tab para navegar entre los elementos de la tabla.',
        'Fin de ayuda.'
    ].forEach(t => hablar(t, true));
}
<?php if ($success): ?>
window.addEventListener('load', () => setTimeout(() => hablar(<?php echo json_encode('Operación exitosa. ' . strip_tags($success)); ?>), 700));
<?php elseif ($error): ?>
window.addEventListener('load', () => setTimeout(() => hablar(<?php echo json_encode('Error: ' . strip_tags($error)); ?>), 700));
<?php endif; ?>
</script>
</body>
</html>
<?php $conn->close(); ?>
