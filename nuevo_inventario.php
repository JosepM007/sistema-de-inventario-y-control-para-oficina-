<?php
session_start();
if (!isset($_SESSION['usuario'])) { header("Location: login.php"); exit; }
require 'db.php';

$sql = "SELECT id, nombre, descripcion, cantidad, precio, proveedores FROM productos ORDER BY id DESC";
$result = $conn->query($sql);
$productos = [];
if ($result) while ($row = $result->fetch_assoc()) $productos[] = $row;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo Inventario - OfficeStock Pro</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Nunito', sans-serif; background: linear-gradient(135deg, #0f4c8a 0%, #0a7abf 45%, #00b4d8 100%); min-height: 100vh; color: #fff; }
        .layout { display: flex; min-height: 100vh; }

        .sidebar { width: 220px; flex-shrink: 0; background: rgba(0,0,0,0.28); backdrop-filter: blur(16px); border-right: 1px solid rgba(255,255,255,0.14); display: flex; flex-direction: column; padding: 28px 0 24px; position: sticky; top: 0; height: 100vh; }
        .logo { font-size: 19px; font-weight: 800; color: #fff; padding: 0 24px 24px; border-bottom: 1px solid rgba(255,255,255,0.14); margin-bottom: 12px; }
        .sidebar a { display: flex; align-items: center; gap: 10px; padding: 11px 24px; color: rgba(255,255,255,0.58); text-decoration: none; font-size: 13.5px; font-weight: 600; border-left: 3px solid transparent; transition: all 0.18s; }
        .sidebar a:hover  { color: #fff; background: rgba(255,255,255,0.08); }
        .sidebar a.active { color: #fff; border-left-color: #00c8e8; background: rgba(0,200,232,0.15); }
        .sidebar .logout-link { margin-top: auto; color: #fca5a5; border-top: 1px solid rgba(255,255,255,0.14); padding-top: 14px; }

        .main-content { flex: 1; padding: 32px 36px; overflow-y: auto; }
        .header { margin-bottom: 20px; }
        .header h2 { font-size: 22px; font-weight: 800; }
        .user-info { color: rgba(255,255,255,0.62); font-size: 13px; font-weight: 600; margin-top: 6px; display: inline-flex; align-items: center; gap: 6px; background: rgba(255,255,255,0.12); padding: 5px 14px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.16); }

        .breadcrumb { color: rgba(255,255,255,0.52); font-size: 13px; margin-bottom: 8px; }
        .back-link { color: #7fecf8; text-decoration: none; font-size: 13.5px; display: inline-flex; align-items: center; gap: 5px; margin-bottom: 18px; font-weight: 700; }
        .back-link:hover { text-decoration: underline; }

        .inv-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:10px; }
        .page-title { color:#fff; font-size:18px; font-weight:800; }
        .inv-count { color:rgba(255,255,255,0.70); font-size:13px; background:rgba(255,255,255,0.10); padding:4px 14px; border-radius:20px; border:1px solid rgba(255,255,255,0.16); font-weight:700; }

        /* ── Tabla ── */
        .inv-table-wrap { background: rgba(255,255,255,0.08); border-radius: 16px; overflow: hidden; box-shadow: 0 12px 40px rgba(0,0,0,0.22); border: 1px solid rgba(255,255,255,0.14); }
        table { width:100%; border-collapse:collapse; font-size:13.5px; }
        thead { background: linear-gradient(90deg, rgba(0,119,182,0.80), rgba(0,180,216,0.60)); }
        thead th { color:#fff; font-weight:800; padding:14px 16px; text-align:left; border-bottom:2px solid rgba(255,255,255,0.20); white-space:nowrap; }

        tbody tr { border-bottom:1px solid rgba(255,255,255,0.08); transition:background 0.15s; }
        tbody tr:last-child { border-bottom: none; }

        /* ✅ FILAS CON FONDO OSCURO para que el texto blanco se vea */
        tbody tr:nth-child(odd)  { background: rgba(0,0,0,0.20); }
        tbody tr:nth-child(even) { background: rgba(0,0,0,0.10); }
        tbody tr:hover { background: rgba(0,200,232,0.15) !important; }

        /* ✅ Texto de celdas con colores fuertes y visibles */
        tbody td { padding:13px 16px; vertical-align:middle; }

        .prod-nombre { font-weight: 800; color: #ffffff; font-size: 14px; text-shadow: 0 1px 3px rgba(0,0,0,0.5); }
        .prod-desc   { font-size: 12px; color: rgba(255,255,255,0.70); margin-top: 2px; }
        .num-col     { color: rgba(255,255,255,0.60); font-size: 12px; font-weight: 700; }

        .badge-prov { background: rgba(0,200,232,0.30); color: #7fecf8; border-radius:6px; padding:3px 10px; font-size:12px; font-weight:800; border: 1px solid rgba(0,200,232,0.40); }
        .precio { color: #a3ffb0; font-weight: 800; font-size: 14px; }
        .qty    { color: #fcd34d; font-weight: 800; font-size: 14px; }
        .qty-cero { color: #fca5a5; font-weight: 800; font-size: 14px; }

        .btn-recibo { display:inline-flex; align-items:center; gap:5px; background: linear-gradient(135deg,#065f46,#10b981); color:#fff; border:none; border-radius:8px; padding:6px 12px; font-size:12px; font-weight:800; cursor:pointer; text-decoration:none; transition: opacity 0.2s, transform 0.15s; white-space: nowrap; }
        .btn-recibo:hover { opacity:.85; transform:scale(1.04); }
        .btn-recibo svg { width:14px; height:14px; flex-shrink:0; }

        .empty-msg { text-align:center; padding:40px; color:rgba(255,255,255,0.55); }

        @media (max-width:768px) { .sidebar{display:none;} .main-content{padding:18px;} }
    
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
        <a href="salidas.php">📤 Salidas</a>
        <a href="auditoria.php">🔍 Auditoría</a>
        <a href="logout.php" class="logout-link">🚪 Cerrar Sesión</a>
    </div>

    <div class="main-content">
        <div class="header">
            <h2>📋 Nuevo Inventario</h2>
            <span class="user-info">👤 <?php echo htmlspecialchars($_SESSION['usuario']); ?> | <?php echo ucfirst(htmlspecialchars($_SESSION['rol'])); ?></span>
        </div>

        <div class="breadcrumb">Inicio / Categorías / Nuevo Inventario</div>
        <a class="back-link"
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
 href="categorias.php">← Volver a categorías</a>

        <div class="inv-header">
            <div class="page-title">Registro de Productos Ingresados</div>
            <div class="inv-count"><?php echo count($productos); ?> producto<?php echo count($productos) !== 1 ? 's' : ''; ?> registrado<?php echo count($productos) !== 1 ? 's' : ''; ?></div>
        </div>

        <div class="inv-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>📦 Producto</th>
                        <th>📝 Descripción</th>
                        <th>🏢 Proveedor</th>
                        <th>🔢 Cantidad</th>
                        <th>💲 Precio Unit.</th>
                        <th>⬇️ Recibo</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($productos)): ?>
                    <tr><td colspan="7" class="empty-msg">No hay productos registrados en el inventario.</td></tr>
                <?php else: ?>
                    <?php foreach ($productos as $i => $p): ?>
                    <tr>
                        <td class="num-col"><?php echo $i + 1; ?></td>
                        <td>
                            <div class="prod-nombre"><?php echo htmlspecialchars($p['nombre']); ?></div>
                        </td>
                        <td>
                            <div class="prod-desc"><?php echo htmlspecialchars($p['descripcion'] ?? '—'); ?></div>
                        </td>
                        <td><span class="badge-prov"><?php echo htmlspecialchars($p['proveedores'] ?? '—'); ?></span></td>
                        <td class="<?php echo intval($p['cantidad']) === 0 ? 'qty-cero' : 'qty'; ?>">
                            <?php echo intval($p['cantidad']) === 0 ? '🚫 0' : intval($p['cantidad']); ?> uds
                        </td>
                        <td class="precio">$<?php echo number_format(floatval($p['precio']), 2); ?></td>
                        <td>
                            <a class="btn-recibo" href="generar_recibo.php?id=<?php echo intval($p['id']); ?>" target="_blank">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/>
                                </svg>
                                Recibo
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
/* ── Lectura de página: Inventario ── */

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
    
    const rows = document.querySelectorAll('tbody tr');
    const frases = [
        'Página de inventario completo. Se muestran ' + rows.length + ' productos registrados.',
    ];
    [...rows].slice(0,5).forEach((r,i) => {
        const nombre = r.querySelector('.prod-nombre')?.textContent.trim() || '';
        const prov   = r.querySelector('.badge-prov')?.textContent.trim() || '';
        const qty    = r.querySelector('.qty, .qty-cero')?.textContent.trim() || '';
        const precio = r.querySelector('.precio')?.textContent.trim() || '';
        if (nombre) frases.push('Producto ' + (i+1) + ': ' + nombre + '. Proveedor: ' + prov + '. Cantidad: ' + qty + '. Precio: ' + precio + '.');
    });
    if (rows.length > 5) frases.push('Hay ' + (rows.length - 5) + ' productos más en la lista.');
    frases.push('Puedes descargar el recibo de cada producto usando el botón Recibo en la última columna.');
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
<?php $conn->close(); ?>
