<?php
session_start();

// Solo admin jose puede acceder
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'admin' || strtolower($_SESSION['usuario']) !== 'jose') {
    $_SESSION['error'] = "Sin permisos";
    header("Location: salidas.php");
    exit;
}

require 'db.php';
if (file_exists('auditoria_fn.php')) require 'auditoria_fn.php';

if (!isset($_GET['id'])) {
    $_SESSION['error'] = "ID no especificado";
    header("Location: salidas.php");
    exit;
}

$id = intval($_GET['id']);
if ($id <= 0) {
    $_SESSION['error'] = "ID inválido";
    header("Location: salidas.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $producto_nombre = trim($_POST['producto_nombre'] ?? '');
    $cantidad_salida = intval($_POST['cantidad_salida'] ?? 0);
    $area_destino    = trim($_POST['area_destino']    ?? '');
    $motivo          = trim($_POST['motivo']          ?? '');

    if (!empty($producto_nombre) && $cantidad_salida > 0 && !empty($area_destino)) {
        $stmt = $conn->prepare("UPDATE salidas SET producto_nombre=?, cantidad_salida=?, area_destino=?, motivo=? WHERE id=?");
        $stmt->bind_param("sissi", $producto_nombre, $cantidad_salida, $area_destino, $motivo, $id);

        if ($stmt->execute()) {
            if (function_exists('registrar_auditoria')) {
                $detalle = "Salida ID {$id} editada por {$_SESSION['usuario']}: producto \"{$producto_nombre}\", cantidad {$cantidad_salida}, área \"{$area_destino}\".";
                registrar_auditoria($conn, $_SESSION['usuario'], 'EDICION', $detalle, 'salidas');
            }
            $_SESSION['success'] = "Salida #$id actualizada correctamente.";
            $stmt->close(); $conn->close();
            header("Location: salidas.php");
            exit;
        } else {
            $error = "Error al actualizar la salida.";
        }
        $stmt->close();
    } else {
        $error = "Completa todos los campos obligatorios.";
    }
}

// Cargar datos de la salida
$stmt = $conn->prepare("SELECT * FROM salidas WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$salida = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if (!$salida) {
    $_SESSION['error'] = "Salida no encontrada";
    header("Location: salidas.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Salida - OfficeStock Pro</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Nunito', sans-serif; min-height: 100vh; background: linear-gradient(135deg, #0f4c8a 0%, #0a7abf 45%, #00b4d8 100%); display: flex; align-items: center; justify-content: center; padding: 40px 16px; }
        .form-wrapper { width: 100%; max-width: 600px; }
        .form-topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .form-logo { font-size: 18px; font-weight: 800; color: #fff; }
        .btn-back { display: inline-flex; align-items: center; gap: 6px; color: rgba(255,255,255,0.68); text-decoration: none; font-size: 13px; font-weight: 600; transition: color 0.2s; }
        .btn-back:hover { color: #fff; }
        .form-card { background: rgba(255,255,255,0.10); border: 1px solid rgba(255,255,255,0.18); border-radius: 20px; backdrop-filter: blur(16px); overflow: hidden; box-shadow: 0 24px 64px rgba(0,0,0,0.28); }
        .form-card-header { background: linear-gradient(135deg, rgba(239,68,68,0.45), rgba(185,28,28,0.35)); border-bottom: 1px solid rgba(255,255,255,0.14); padding: 26px 32px 22px; display: flex; align-items: center; gap: 16px; }
        .header-icon { width: 50px; height: 50px; border-radius: 14px; background: linear-gradient(135deg,#991b1b,#ef4444); display: flex; align-items: center; justify-content: center; font-size: 24px; box-shadow: 0 6px 20px rgba(0,0,0,0.22); flex-shrink: 0; }
        .form-card-header h2 { font-size: 20px; font-weight: 800; color: #fff; }
        .form-card-header p { font-size: 12.5px; color: rgba(255,255,255,0.60); margin-top: 2px; }
        .form-body { padding: 30px 32px 28px; }
        .alert-err { display:flex; align-items:center; gap:10px; background:rgba(239,68,68,0.16); border:1px solid rgba(239,68,68,0.32); color:#fca5a5; padding:12px 16px; border-radius:10px; font-size:13.5px; font-weight:600; margin-bottom:24px; }
        .info-box { display:flex; align-items:flex-start; gap:10px; background:rgba(239,68,68,0.12); border:1px solid rgba(239,68,68,0.25); border-radius:10px; padding:12px 14px; margin-bottom:22px; font-size:13px; color:rgba(255,255,255,0.85); }
        .info-box strong { color:#fca5a5; }
        .field-group { display: flex; flex-direction: column; gap: 7px; margin-bottom: 16px; }
        .field-group label { font-size: 12px; font-weight: 700; color: rgba(255,255,255,0.72); text-transform: uppercase; letter-spacing: 0.7px; }
        .field-group label .req { color: #7fecf8; margin-left: 2px; }
        .field-group input, .field-group textarea { width: 100%; background: rgba(255,255,255,0.10); border: 1px solid rgba(255,255,255,0.18); border-radius: 10px; padding: 11px 14px; color: #fff; font-family: 'Nunito', sans-serif; font-size: 14px; outline: none; transition: border-color 0.2s, background 0.2s, box-shadow 0.2s; }
        .field-group input::placeholder, .field-group textarea::placeholder { color: rgba(255,255,255,0.30); }
        .field-group input:focus, .field-group textarea:focus { border-color: #ef4444; background: rgba(255,255,255,0.15); box-shadow: 0 0 0 3px rgba(239,68,68,0.16); }
        .field-group textarea { resize: vertical; min-height: 80px; }
        .divider { border: none; border-top: 1px solid rgba(255,255,255,0.10); margin: 24px 0; }
        .form-actions { display: flex; gap: 12px; justify-content: flex-end; }
        .btn-guardar { display: inline-flex; align-items: center; gap: 8px; background: linear-gradient(135deg,#991b1b,#ef4444); color: #fff; border: none; border-radius: 10px; padding: 11px 28px; font-family: 'Nunito', sans-serif; font-size: 14px; font-weight: 800; cursor: pointer; box-shadow: 0 4px 16px rgba(239,68,68,0.32); transition: opacity 0.2s, transform 0.15s; }
        .btn-guardar:hover { opacity: .88; transform: translateY(-1px); }
        .btn-cancelar { display: inline-flex; align-items: center; gap: 6px; background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.72); border: 1px solid rgba(255,255,255,0.16); border-radius: 10px; padding: 11px 22px; font-family: 'Nunito', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; transition: background 0.2s, color 0.2s; }
        .btn-cancelar:hover { background: rgba(255,255,255,0.15); color: #fff; }
    
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
<div class="form-wrapper">
    <div class="form-topbar">
        <div class="form-logo">🗂️ OfficeStock Pro</div>
        <a class="btn-back" href="salidas.php">← Volver a Salidas</a>
    </div>
    <div class="form-card">
        <div class="form-card-header">
            <div class="header-icon">✏️</div>
            <div>
                <h2>Editar Salida #<?php echo $id; ?></h2>
                <p>Modifica los datos del registro de salida.</p>
            </div>
        </div>
        <div class="form-body">
            <div class="info-box">
                ⚠️ <span>Solo edita datos descriptivos. <strong>El stock del producto no cambia automáticamente</strong> al editar una salida.</span>
            </div>
            <?php if (isset($error)): ?>
                <div class="alert-err">⚠️ <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="field-group">
                    <label>Nombre del Producto <span class="req">*</span></label>
                    <input type="text" name="producto_nombre"
                           value="<?php echo htmlspecialchars($_POST['producto_nombre'] ?? $salida['producto_nombre']); ?>"
                           required>
                </div>
                <div class="field-group">
                    <label>Cantidad Retirada <span class="req">*</span></label>
                    <input type="number" name="cantidad_salida" min="1"
                           value="<?php echo intval($_POST['cantidad_salida'] ?? $salida['cantidad_salida']); ?>"
                           required>
                </div>
                <div class="field-group">
                    <label>Área / Destino <span class="req">*</span></label>
                    <input type="text" name="area_destino"
                           placeholder="Ej: Recursos Humanos..."
                           value="<?php echo htmlspecialchars($_POST['area_destino'] ?? $salida['area_destino']); ?>"
                           required>
                </div>
                <div class="field-group">
                    <label>Motivo</label>
                    <textarea name="motivo" placeholder="Motivo de la salida..."><?php echo htmlspecialchars($_POST['motivo'] ?? $salida['motivo']); ?></textarea>
                </div>
                <hr class="divider">
                <div class="form-actions">
                    <a href="salidas.php" class="btn-cancelar">✕ Cancelar</a>
                    <button type="submit" class="btn-guardar">💾 Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="js/voz.js"></script>
<script>
/* ── Lectura de página: Editar Salida ── */
VOZ.hablarPagina = function() {
    window.speechSynthesis && window.speechSynthesis.cancel();
    
    [
        'Formulario para editar una salida de producto.',
        'Puedes modificar el nombre del producto, la cantidad retirada, el área de destino y el motivo.',
        'Ten en cuenta que editar este registro no cambia el stock del producto automáticamente.',
        'Cuando termines presiona Guardar Cambios. Para cancelar usa el botón Cancelar.',
        'Fin.'
    ].forEach(t => VOZ.hablar(t, true));

};
/* Anunciar alertas automáticamente */
document.querySelectorAll('[role="alert"]').forEach(el => {
    if (el.textContent.trim()) setTimeout(() => VOZ.hablar(el.textContent.trim(), true), 600);
});
</script>
</body>
</html>
