<?php
session_start();
if (!isset($_SESSION['usuario'])) { header("Location: login.php"); exit; }
require 'db.php';

if (!isset($_GET['cat'])) { header("Location: categorias.php"); exit; }

$cat_slug = trim($_GET['cat']);
$categorias_config = [
    'tecnologia' => ['nombre'=>'Tecnología','icon'=>'💻','proveedores'=>['HP','Samsung','Apple','Lenovo','Amazon']],
    'mobiliario' => ['nombre'=>'Mobiliario','icon'=>'🪑','proveedores'=>['Siman']],
    'utiles'     => ['nombre'=>'Útiles',    'icon'=>'📎','proveedores'=>['Walmart']]
];

if (!isset($categorias_config[$cat_slug])) { header("Location: categorias.php"); exit; }

$config   = $categorias_config[$cat_slug];
$cat_name = $config['nombre'];
$provs    = $config['proveedores'];

$placeholders = implode(',', array_fill(0, count($provs), '?'));
$types        = str_repeat('s', count($provs));

$stmt = $conn->prepare("SELECT id, nombre, descripcion, cantidad, precio, proveedores FROM productos WHERE proveedores IN ($placeholders) ORDER BY proveedores, nombre ASC");
$stmt->bind_param($types, ...$provs);
$stmt->execute();
$result = $stmt->get_result();
$products = [];
while ($r = $result->fetch_assoc()) $products[] = $r;
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($cat_name); ?> - OfficeStock Pro</title>
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
        .sidebar .logout-link:hover { background: rgba(239,68,68,0.14); color: #fff; }

        .main-content { flex: 1; padding: 32px 36px; }
        .header { margin-bottom: 20px; }
        .header h2 { font-size: 24px; font-weight: 800; }
        .user-info { color: rgba(255,255,255,0.62); font-size: 13px; font-weight: 600; margin-top: 6px; display: inline-flex; align-items: center; gap: 6px; background: rgba(255,255,255,0.12); padding: 5px 14px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.16); }

        .back-link { color: #7fecf8; text-decoration: none; margin-bottom: 20px; display: inline-flex; align-items: center; gap: 6px; font-size: 13.5px; font-weight: 700; transition: color .2s; }
        .back-link:hover { color: #fff; text-decoration: underline; }

        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; margin-top: 16px; }

        .product-card { background: rgba(255,255,255,0.10); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.16); border-radius: 14px; padding: 18px; display: flex; flex-direction: column; gap: 8px; box-shadow: 0 6px 22px rgba(0,0,0,0.18); transition: transform .2s, box-shadow .2s; }
        .product-card:hover { transform: translateY(-4px); box-shadow: 0 16px 36px rgba(0,0,0,0.26); border-color: rgba(0,200,232,0.40); }

        .product-badge { font-size: 0.72rem; background: rgba(0,200,232,0.22); color: #7fecf8; border-radius: 6px; padding: 2px 8px; display: inline-block; width: fit-content; font-weight: 700; }
        .product-title { font-weight: 800; color: #fff; font-size: 0.95rem; }
        .product-desc  { color: rgba(255,255,255,0.60); font-size: 0.82rem; flex: 1; }

        .product-footer { display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255,255,255,0.12); padding-top: 8px; margin-top: 4px; }
        .product-price { color: #a3ffb0; font-weight: 800; font-size: 0.95rem; }
        .product-qty   { color: rgba(255,255,255,0.55); font-size: 0.82rem; font-weight: 600; }

        .no-products { color: rgba(255,255,255,0.70); padding: 20px; border-radius: 12px; background: rgba(0,0,0,0.15); font-size: 14px; }
        .count-label { color: rgba(255,255,255,0.60); margin-bottom: 14px; font-size: 13px; }

        @media (max-width:768px) { .sidebar{display:none;} .main-content{padding:18px;} }
        /* ♿ VOZ */
        .skip-link{position:absolute;top:-50px;left:10px;background:#00c8e8;color:#003;padding:8px 16px;border-radius:8px;font-weight:700;font-size:14px;z-index:9999;transition:top .2s;text-decoration:none}
        .skip-link:focus{top:10px}
        .voz-bar{background:rgba(0,0,0,0.22);border:1px solid rgba(255,255,255,0.16);border-radius:14px;padding:12px 18px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:18px}
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
<a class="skip-link" href="#contenido-categoria">Saltar al contenido principal</a>
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
            <h2><?php echo $config['icon'] . ' ' . htmlspecialchars($cat_name); ?></h2>
            <span class="user-info">👤 <?php echo htmlspecialchars($_SESSION['usuario']); ?> | <?php echo ucfirst(htmlspecialchars($_SESSION['rol'])); ?></span>
        </div>

        <a class="back-link" href="categorias.php">← Volver a categorías</a>
        <!-- ♿ BARRA DE VOZ -->
        <div class="voz-bar" id="contenido-categoria" role="region" aria-label="Asistente de voz">
            <span class="voz-title">♿ Asistente de Voz</span>
            <span class="voz-status" id="vozStatus" aria-live="polite">Listo</span>
            <div class="voz-btns">
                <button class="btn-voz bv-green" onclick="leerPagina()" aria-label="Leer productos de esta categoría">🔊 Leer página</button>
                <button class="btn-voz bv-cyan"  onclick="leerAyuda()" aria-label="Ayuda">❓ Ayuda</button>
                <button class="btn-voz bv-red"   onclick="detenerVoz()" aria-label="Detener voz">⏹ Detener</button>
            </div>
        </div>


        <?php if (count($products) === 0): ?>
            <div class="no-products">No se encontraron productos en <strong><?php echo htmlspecialchars($cat_name); ?></strong>.</div>
        <?php else: ?>
            <div class="count-label"><?php echo count($products); ?> productos encontrados</div>
            <div class="products-grid">
                <?php foreach($products as $p): ?>
                    <div class="product-card">
                        <span class="product-badge"><?php echo htmlspecialchars($p['proveedores']); ?></span>
                        <div class="product-title"><?php echo htmlspecialchars($p['nombre']); ?></div>
                        <div class="product-desc"><?php echo htmlspecialchars($p['descripcion']); ?></div>
                        <div class="product-footer">
                            <span class="product-price">$<?php echo number_format(floatval($p['precio']), 2); ?></span>
                            <span class="product-qty"><?php echo intval($p['cantidad']); ?> uds</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<script>

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
    const catName = <?php echo json_encode($cat_name); ?>;
    const total   = <?php echo count($products); ?>;
    const prods   = <?php
        $arr = array_map(fn($p) => ['nombre'=>$p['nombre'],'precio'=>$p['precio'],'cantidad'=>$p['cantidad'],'prov'=>$p['proveedores']], array_slice($products,0,5));
        echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    ?>;
    const frases = [
        'Categoría ' + catName + '. Se muestran ' + total + ' productos.',
    ];
    prods.forEach((p,i) => {
        frases.push('Producto ' + (i+1) + ': ' + p.nombre + '. Proveedor: ' + p.prov + '. Precio: ' + p.precio + ' dólares. Cantidad: ' + p.cantidad + ' unidades.');
    });
    if (total > 5) frases.push('Hay ' + (total - 5) + ' productos más.');
    frases.push('Para volver a categorías usa el enlace Volver a categorías. Fin.');
    frases.forEach(t => hablar(t, true));
};
</script>
</body>
</html>
<?php $conn->close(); ?>
