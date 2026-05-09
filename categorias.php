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

        <div class="breadcrumb">
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
Inicio / Categorías</div>
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
<script>
/* ── Lectura de página: Categorías ── */

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
    
    const cards = document.querySelectorAll('.cat-card');
    const frases = [
        'Página de Categorías. Hay ' + cards.length + ' categorías disponibles.',
    ];
    cards.forEach((c,i) => {
        const title = c.querySelector('.cat-title')?.textContent.trim() || '';
        const desc  = c.querySelector('.cat-desc')?.textContent.trim() || '';
        frases.push('Categoría ' + (i+1) + ': ' + title + '. ' + desc);
    });
    frases.push('Presiona Enter sobre una categoría para ver sus productos.');
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
