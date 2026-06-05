<?php
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'admin') {
    $_SESSION['error'] = "Sin permisos";
    header("Location: dashboard.php");
    exit;
}

require 'db.php';
if (file_exists('auditoria_fn.php')) require 'auditoria_fn.php';

if (!isset($_GET['id'])) {
    $_SESSION['error'] = "ID no especificado";
    header("Location: dashboard.php");
    exit;
}

$id = intval($_GET['id']);
if ($id <= 0) {
    $_SESSION['error'] = "ID inválido";
    header("Location: dashboard.php");
    exit;
}

// Detectar si viene desde proveedor.php o nuevo_inventario.php para redirigir de vuelta correctamente
$from_prov = isset($_GET['from_prov']) ? trim($_GET['from_prov']) : '';
$back_get  = isset($_GET['back_url'])  ? trim($_GET['back_url'])  : '';

// Whitelist de URLs permitidas para redirigir
$allowed_backs = ['nuevo_inventario.php', 'dashboard.php'];
if (!empty($from_prov)) {
    $back_url   = "proveedor.php?prov=" . urlencode($from_prov);
    $back_label = "← Volver a " . htmlspecialchars($from_prov);
} elseif (!empty($back_get) && in_array($back_get, $allowed_backs)) {
    $back_url   = $back_get;
    $back_label = "← Volver al Inventario";
} else {
    $back_url   = "dashboard.php";
    $back_label = "← Volver al Dashboard";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre      = trim($_POST['nombre']      ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $cantidad    = intval($_POST['cantidad']   ?? 0);
    $precio      = floatval($_POST['precio']   ?? 0);
    $proveedores = trim($_POST['proveedores']  ?? '');
    $redir_back  = trim($_POST['back_url']     ?? 'dashboard.php');

    if (!empty($nombre) && $cantidad >= 0 && $precio >= 0) {
        $stmt = $conn->prepare("UPDATE productos SET nombre=?, descripcion=?, cantidad=?, precio=?, proveedores=? WHERE id=?");
        $stmt->bind_param("ssidsi", $nombre, $descripcion, $cantidad, $precio, $proveedores, $id);

        if ($stmt->execute()) {
            if (function_exists('registrar_auditoria')) {
                $detalle = "Producto ID {$id} editado: \"{$nombre}\" — Cantidad: {$cantidad}, Precio: \${$precio}, Proveedor: {$proveedores}.";
                registrar_auditoria($conn, $_SESSION['usuario'], 'EDICION', $detalle, 'productos');
            }
            $_SESSION['success'] = "Producto \"{$nombre}\" actualizado correctamente.";
            $stmt->close();
            $conn->close();
            header("Location: " . $redir_back);
            exit;
        } else {
            $error = "Error al actualizar el producto.";
        }
        $stmt->close();
    } else {
        $error = "Por favor completa todos los campos obligatorios.";
    }
}

// Cargar datos del producto
$stmt = $conn->prepare("SELECT * FROM productos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$producto = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if (!$producto) {
    $_SESSION['error'] = "Producto no encontrado";
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Producto - OfficeStock Pro</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Nunito', sans-serif; min-height: 100vh; background: linear-gradient(135deg, #0f4c8a 0%, #0a7abf 45%, #00b4d8 100%); display: flex; align-items: center; justify-content: center; padding: 40px 16px; }
        .form-wrapper { width: 100%; max-width: 620px; }
        .form-topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .form-logo { font-size: 18px; font-weight: 800; color: #fff; }
        .btn-back { display: inline-flex; align-items: center; gap: 6px; color: rgba(255,255,255,0.68); text-decoration: none; font-size: 13px; font-weight: 600; transition: color 0.2s; }
        .btn-back:hover { color: #fff; }
        /* ♿ Barra de voz compacta */
        .voz-bar{background:rgba(0,0,0,0.22);border:1px solid rgba(255,255,255,0.16);border-radius:12px;padding:12px 18px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:20px}
        .voz-title{font-size:12px;font-weight:700;color:rgba(255,255,255,0.75)}
        .voz-status{font-size:11px;color:rgba(255,255,255,0.45);font-weight:600}
        .voz-status.activo{color:#6ee7b7;animation:blink 1.2s infinite}
        @keyframes blink{0%,100%{opacity:1}50%{opacity:.4}}
        .voz-btns{display:flex;gap:6px;flex-wrap:wrap;margin-left:auto}
        .btn-voz{display:inline-flex;align-items:center;gap:4px;border:none;border-radius:8px;padding:6px 12px;font-family:'Nunito',sans-serif;font-size:12px;font-weight:700;cursor:pointer;transition:opacity .15s}
        .btn-voz:hover{opacity:.84}
        .bv-green{background:linear-gradient(135deg,#065f46,#10b981);color:#fff}
        .bv-red{background:rgba(239,68,68,0.18);border:1px solid rgba(239,68,68,0.32);color:#fca5a5}
        /* Form */
        .form-card { background: rgba(255,255,255,0.10); border: 1px solid rgba(255,255,255,0.18); border-radius: 20px; backdrop-filter: blur(16px); overflow: hidden; box-shadow: 0 24px 64px rgba(0,0,0,0.28); }
        .form-card-header { background: linear-gradient(135deg, rgba(245,158,11,0.45), rgba(234,88,12,0.35)); border-bottom: 1px solid rgba(255,255,255,0.14); padding: 26px 32px 22px; display: flex; align-items: center; gap: 16px; }
        .header-icon { width: 50px; height: 50px; border-radius: 14px; background: linear-gradient(135deg,#b45309,#f59e0b); display: flex; align-items: center; justify-content: center; font-size: 24px; box-shadow: 0 6px 20px rgba(0,0,0,0.22); flex-shrink: 0; }
        .form-card-header h2 { font-size: 20px; font-weight: 800; color: #fff; }
        .form-card-header p { font-size: 12.5px; color: rgba(255,255,255,0.60); margin-top: 2px; }
        .form-body { padding: 30px 32px 28px; }
        .alert-err { display: flex; align-items: center; gap: 10px; background: rgba(239,68,68,0.16); border: 1px solid rgba(239,68,68,0.32); color: #fca5a5; padding: 12px 16px; border-radius: 10px; font-size: 13.5px; font-weight: 600; margin-bottom: 24px; }
        .info-box { display:flex; align-items:flex-start; gap:10px; background:rgba(245,158,11,0.12); border:1px solid rgba(245,158,11,0.28); border-radius:10px; padding:12px 14px; margin-bottom:22px; font-size:13px; color:rgba(255,255,255,0.85); line-height:1.5; }
        .info-box strong { color:#fcd34d; }
        .fields-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px 20px; }
        .field-full { grid-column: 1 / -1; }
        .field-group { display: flex; flex-direction: column; gap: 7px; }
        .field-group label { font-size: 12px; font-weight: 700; color: rgba(255,255,255,0.72); text-transform: uppercase; letter-spacing: 0.7px; }
        .field-group label .req { color: #7fecf8; margin-left: 2px; }
        .field-group input, .field-group textarea { width: 100%; background: rgba(255,255,255,0.10); border: 1px solid rgba(255,255,255,0.18); border-radius: 10px; padding: 11px 14px; color: #fff; font-family: 'Nunito', sans-serif; font-size: 14px; outline: none; transition: border-color 0.2s, background 0.2s, box-shadow 0.2s; }
        .field-group input::placeholder, .field-group textarea::placeholder { color: rgba(255,255,255,0.30); }
        .field-group input:focus, .field-group textarea:focus { border-color: #f59e0b; background: rgba(255,255,255,0.15); box-shadow: 0 0 0 3px rgba(245,158,11,0.18); }
        .input-wrap { position: relative; }
        .input-wrap .prefix { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: rgba(255,255,255,0.42); font-size: 14px; pointer-events: none; }
        .input-wrap input.has-prefix { padding-left: 30px; }
        .field-group textarea { resize: vertical; min-height: 90px; }
        .divider { border: none; border-top: 1px solid rgba(255,255,255,0.10); margin: 24px 0; }
        .form-actions { display: flex; gap: 12px; justify-content: flex-end; align-items: center; }
        .btn-guardar { display: inline-flex; align-items: center; gap: 8px; background: linear-gradient(135deg,#92400e,#f59e0b); color: #fff; border: none; border-radius: 10px; padding: 11px 28px; font-family: 'Nunito', sans-serif; font-size: 14px; font-weight: 800; cursor: pointer; box-shadow: 0 4px 16px rgba(245,158,11,0.32); transition: opacity 0.2s, transform 0.15s; }
        .btn-guardar:hover { opacity: .88; transform: translateY(-1px); }
        .btn-cancelar { display: inline-flex; align-items: center; gap: 6px; background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.72); border: 1px solid rgba(255,255,255,0.16); border-radius: 10px; padding: 11px 22px; font-family: 'Nunito', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; transition: background 0.2s, color 0.2s; }
        .btn-cancelar:hover { background: rgba(255,255,255,0.15); color: #fff; }
        @media (max-width: 520px) { .fields-grid { grid-template-columns: 1fr; } .field-full { grid-column: 1; } .form-card-header, .form-body { padding-left: 20px; padding-right: 20px; } .form-actions { flex-direction: column-reverse; } .btn-guardar, .btn-cancelar { width: 100%; justify-content: center; } }
    </style>
</head>
<body>
<a class="skip-link" href="#contenido-principal" style="position:absolute;top:-50px;left:10px;background:#00c8e8;color:#003;padding:8px 16px;border-radius:8px;font-weight:700;z-index:9999;transition:top .2s;text-decoration:none">Saltar al contenido principal</a>
<div class="form-wrapper">
    <div class="form-topbar">
        <div class="form-logo">🗂️ OfficeStock Pro</div>
        <a class="btn-back" href="<?php echo htmlspecialchars($back_url); ?>" aria-label="<?php echo $back_label; ?>"><?php echo $back_label; ?></a>
    </div>

    <!-- ♿ BARRA DE VOZ COMPACTA -->
    <div class="voz-bar" role="region" aria-label="Asistente de voz">
        <span class="voz-title">♿ Asistente de Voz</span>
        <span class="voz-status" id="vozStatus" aria-live="polite">Listo</span>
        <div class="voz-btns">
            <button class="btn-voz bv-green" onclick="leerPagina()" aria-label="Leer instrucciones del formulario en voz alta">🔊 Leer</button>
            <button class="btn-voz bv-red" onclick="detenerVoz()" aria-label="Detener voz">⏹ Detener</button>
        </div>
    </div>

    <div class="form-card" role="main">
        <div class="form-card-header">
            <div class="header-icon" aria-hidden="true">✏️</div>
            <div>
                <h2>Editar Producto</h2>
                <p>Modifica los datos del producto seleccionado.</p>
            </div>
        </div>
        <div class="form-body">
            <div class="info-box" role="note">
                ⚠️ <span>Estás editando <strong><?php echo htmlspecialchars($producto['nombre']); ?></strong>. Los cambios se guardarán en auditoría.</span>
            </div>
            <?php if (isset($error)): ?>
                <div class="alert-err" role="alert">⚠️ <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="POST" action="" aria-label="Formulario editar producto <?php echo htmlspecialchars($producto['nombre']); ?>">
                <!-- Pasar back_url como campo oculto para redirigir correctamente -->
                <input type="hidden" name="back_url" value="<?php echo htmlspecialchars($back_url); ?>">
                <div class="fields-grid">
                    <div class="field-group field-full">
                        <label for="nombre">Nombre del Producto <span class="req" aria-hidden="true">*</span></label>
                        <input type="text" id="nombre" name="nombre"
                               value="<?php echo htmlspecialchars($_POST['nombre'] ?? $producto['nombre']); ?>"
                               required aria-required="true">
                    </div>
                    <div class="field-group field-full">
                        <label for="descripcion">Descripción</label>
                        <textarea id="descripcion" name="descripcion" placeholder="Describe brevemente el producto..."><?php echo htmlspecialchars($_POST['descripcion'] ?? $producto['descripcion']); ?></textarea>
                    </div>
                    <div class="field-group">
                        <label for="cantidad">Cantidad <span class="req" aria-hidden="true">*</span></label>
                        <div class="input-wrap">
                            <span class="prefix" aria-hidden="true">#</span>
                            <input class="has-prefix" type="number" id="cantidad" name="cantidad" min="0"
                                   value="<?php echo intval($_POST['cantidad'] ?? $producto['cantidad']); ?>" required aria-required="true">
                        </div>
                    </div>
                    <div class="field-group">
                        <label for="precio">Precio Unitario <span class="req" aria-hidden="true">*</span></label>
                        <div class="input-wrap">
                            <span class="prefix" aria-hidden="true">$</span>
                            <input class="has-prefix" type="number" id="precio" name="precio" min="0" step="0.01"
                                   value="<?php echo floatval($_POST['precio'] ?? $producto['precio']); ?>" required aria-required="true">
                        </div>
                    </div>
                    <div class="field-group field-full">
                        <label for="proveedores">Proveedor <span class="req" aria-hidden="true">*</span></label>
                        <input type="text" id="proveedores" name="proveedores"
                               placeholder="Ej: Walmart, HP, Samsung..."
                               value="<?php echo htmlspecialchars($_POST['proveedores'] ?? $producto['proveedores']); ?>" required aria-required="true">
                    </div>
                </div>
                <hr class="divider">
                <div class="form-actions">
                    <a href="<?php echo htmlspecialchars($back_url); ?>" class="btn-cancelar" aria-label="Cancelar y volver">✕ Cancelar</a>
                    <button type="submit" class="btn-guardar" aria-label="Guardar los cambios del producto">💾 Guardar Cambios</button>
                </div>
            </form>
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
function detenerVoz() { window.speechSynthesis.cancel(); statusEl.textContent = 'Detenido'; statusEl.className = 'voz-status'; }
function leerPagina() {
    window.speechSynthesis.cancel();
    const nombre = <?php echo json_encode($producto['nombre']); ?>;
    [
        'Estás editando el producto: ' + nombre + '.',
        'Completa los campos: nombre, descripción, cantidad, precio unitario y proveedor.',
        'Los campos marcados con asterisco son obligatorios.',
        'Cuando termines, presiona el botón Guardar Cambios.',
        'Para cancelar sin guardar, presiona el botón Cancelar.'
    ].forEach(t => hablar(t, true));
}
document.getElementById('nombre').addEventListener('focus', () => hablar('Campo nombre del producto.', true));
document.getElementById('cantidad').addEventListener('focus', () => hablar('Campo cantidad en inventario.', true));
document.getElementById('precio').addEventListener('focus', () => hablar('Campo precio unitario en dólares.', true));
document.getElementById('proveedores').addEventListener('focus', () => hablar('Campo proveedor del producto.', true));
</script>
</body>
</html>
