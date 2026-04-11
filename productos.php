<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'admin') { $_SESSION['error'] = "Sin permisos"; header("Location: dashboard.php"); exit; }
require 'db.php';
if (file_exists('auditoria_fn.php')) require 'auditoria_fn.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre      = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $cantidad    = intval($_POST['cantidad']);
    $precio      = floatval($_POST['precio']);
    $proveedores = trim($_POST['proveedores']);

    if (!empty($nombre) && $cantidad >= 0 && $precio >= 0) {

        // ✅ Verificar si ya existe el mismo producto con el mismo proveedor
        $check = $conn->prepare("SELECT id, cantidad FROM productos WHERE nombre = ? AND proveedores = ? LIMIT 1");
        $check->bind_param("ss", $nombre, $proveedores);
        $check->execute();
        $existe = $check->get_result()->fetch_assoc();
        $check->close();

        if ($existe) {
            // Producto ya existe → sumar cantidad al stock
            $nueva_cantidad = intval($existe['cantidad']) + $cantidad;
            $upd = $conn->prepare("UPDATE productos SET cantidad = ? WHERE id = ?");
            $upd->bind_param("ii", $nueva_cantidad, $existe['id']);
            if ($upd->execute()) {
                if (function_exists('registrar_auditoria')) {
                    $detalle = "Stock actualizado de \"{$nombre}\" — Se agregaron {$cantidad} uds. Stock anterior: {$existe['cantidad']}, nuevo stock: {$nueva_cantidad}. Proveedor: {$proveedores}.";
                    registrar_auditoria($conn, $_SESSION['usuario'], 'ENTRADA', $detalle, 'productos');
                }
                $_SESSION['success'] = "✅ \"{$nombre}\" ya existía. Se sumaron {$cantidad} unidades. Nuevo stock: {$nueva_cantidad} uds.";
                $upd->close(); $conn->close();
                header("Location: dashboard.php"); exit;
            } else { $error = "Error al actualizar el stock."; }
            $upd->close();
        } else {
            // Producto nuevo → insertarlo
            $stmt = $conn->prepare("INSERT INTO productos (nombre, descripcion, cantidad, precio, proveedores) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssids", $nombre, $descripcion, $cantidad, $precio, $proveedores);
            if ($stmt->execute()) {
                if (function_exists('registrar_auditoria')) {
                    $detalle = "Nuevo producto registrado: \"{$nombre}\" — Cantidad: {$cantidad} uds, Precio: \${$precio}, Proveedor: {$proveedores}.";
                    registrar_auditoria($conn, $_SESSION['usuario'], 'ENTRADA', $detalle, 'productos');
                }
                $_SESSION['success'] = "✅ Producto \"{$nombre}\" agregado correctamente al inventario.";
                $stmt->close(); $conn->close();
                header("Location: dashboard.php"); exit;
            } else { $error = "Error al guardar el producto."; }
            $stmt->close();
        }
    } else { $error = "Por favor completa todos los campos obligatorios."; }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Producto - OfficeStock Pro</title>
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
        .form-card { background: rgba(255,255,255,0.10); border: 1px solid rgba(255,255,255,0.18); border-radius: 20px; backdrop-filter: blur(16px); overflow: hidden; box-shadow: 0 24px 64px rgba(0,0,0,0.28); }
        .form-card-header { background: linear-gradient(135deg, rgba(0,119,182,0.60), rgba(0,180,216,0.40)); border-bottom: 1px solid rgba(255,255,255,0.14); padding: 26px 32px 22px; display: flex; align-items: center; gap: 16px; }
        .header-icon { width: 50px; height: 50px; border-radius: 14px; background: linear-gradient(135deg,#0077b6,#00c8e8); display: flex; align-items: center; justify-content: center; font-size: 24px; box-shadow: 0 6px 20px rgba(0,0,0,0.22); flex-shrink: 0; }
        .form-card-header h2 { font-size: 20px; font-weight: 800; color: #fff; }
        .form-card-header p  { font-size: 12.5px; color: rgba(255,255,255,0.60); margin-top: 2px; }
        .form-body { padding: 30px 32px 28px; }
        .info-box { display:flex; align-items:flex-start; gap:10px; background:rgba(0,200,232,0.12); border:1px solid rgba(0,200,232,0.28); border-radius:10px; padding:12px 14px; margin-bottom:22px; font-size:13px; color:rgba(255,255,255,0.85); line-height:1.5; }
        .info-box strong { color:#7fecf8; }
        .alert-err { display: flex; align-items: center; gap: 10px; background: rgba(239,68,68,0.16); border: 1px solid rgba(239,68,68,0.32); color: #fca5a5; padding: 12px 16px; border-radius: 10px; font-size: 13.5px; font-weight: 600; margin-bottom: 24px; }
        .fields-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px 20px; }
        .field-full { grid-column: 1 / -1; }
        .field-group { display: flex; flex-direction: column; gap: 7px; }
        .field-group label { font-size: 12px; font-weight: 700; color: rgba(255,255,255,0.72); text-transform: uppercase; letter-spacing: 0.7px; }
        .field-group label .req { color: #7fecf8; margin-left: 2px; }
        .field-group input, .field-group textarea { width: 100%; background: rgba(255,255,255,0.10); border: 1px solid rgba(255,255,255,0.18); border-radius: 10px; padding: 11px 14px; color: #fff; font-family: 'Nunito', sans-serif; font-size: 14px; outline: none; transition: border-color 0.2s, background 0.2s, box-shadow 0.2s; }
        .field-group input::placeholder, .field-group textarea::placeholder { color: rgba(255,255,255,0.30); }
        .field-group input:focus, .field-group textarea:focus { border-color: #00c8e8; background: rgba(255,255,255,0.15); box-shadow: 0 0 0 3px rgba(0,200,232,0.18); }
        .input-wrap { position: relative; }
        .input-wrap .prefix { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: rgba(255,255,255,0.42); font-size: 14px; pointer-events: none; }
        .input-wrap input.has-prefix { padding-left: 30px; }
        .field-group textarea { resize: vertical; min-height: 90px; }
        .divider { border: none; border-top: 1px solid rgba(255,255,255,0.10); margin: 24px 0; }
        .form-actions { display: flex; gap: 12px; justify-content: flex-end; align-items: center; }
        .btn-guardar { display: inline-flex; align-items: center; gap: 8px; background: linear-gradient(135deg,#065f46,#10b981); color: #fff; border: none; border-radius: 10px; padding: 11px 28px; font-family: 'Nunito', sans-serif; font-size: 14px; font-weight: 800; cursor: pointer; box-shadow: 0 4px 16px rgba(16,185,129,0.32); transition: opacity 0.2s, transform 0.15s; }
        .btn-guardar:hover { opacity: .88; transform: translateY(-1px); }
        .btn-cancelar { display: inline-flex; align-items: center; gap: 6px; background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.72); border: 1px solid rgba(255,255,255,0.16); border-radius: 10px; padding: 11px 22px; font-family: 'Nunito', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; transition: background 0.2s, color 0.2s; }
        .btn-cancelar:hover { background: rgba(255,255,255,0.15); color: #fff; }
        @media (max-width: 520px) { .fields-grid { grid-template-columns: 1fr; } .field-full { grid-column: 1; } .form-card-header, .form-body { padding-left: 20px; padding-right: 20px; } .form-actions { flex-direction: column-reverse; } .btn-guardar, .btn-cancelar { width: 100%; justify-content: center; } }
    </style>
</head>
<body>
<div class="form-wrapper">
    <div class="form-topbar">
        <div class="form-logo">🗂️ OfficeStock Pro</div>
        <a class="btn-back" href="dashboard.php">← Volver al Dashboard</a>
    </div>
    <div class="form-card">
        <div class="form-card-header">
            <div class="header-icon">📦</div>
            <div>
                <h2>Agregar / Reponer Producto</h2>
                <p>Registra un nuevo producto o repón stock de uno existente.</p>
            </div>
        </div>
        <div class="form-body">
            <div class="info-box">
                💡 <span>Si ingresas un producto con el <strong>mismo nombre y proveedor</strong> que ya existe, el sistema <strong>sumará automáticamente</strong> la cantidad al stock en vez de duplicarlo.</span>
            </div>
            <?php if (isset($error)): ?>
                <div class="alert-err">⚠️ <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="fields-grid">
                    <div class="field-group field-full">
                        <label>Nombre del Producto <span class="req">*</span></label>
                        <input type="text" name="nombre" placeholder="Ej: Resma de papel A4" value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>" required>
                    </div>
                    <div class="field-group field-full">
                        <label>Descripción</label>
                        <textarea name="descripcion" placeholder="Describe brevemente el producto..."><?php echo isset($_POST['descripcion']) ? htmlspecialchars($_POST['descripcion']) : ''; ?></textarea>
                    </div>
                    <div class="field-group">
                        <label>Cantidad <span class="req">*</span></label>
                        <div class="input-wrap"><span class="prefix">#</span><input class="has-prefix" type="number" name="cantidad" min="0" placeholder="0" value="<?php echo isset($_POST['cantidad']) ? intval($_POST['cantidad']) : ''; ?>" required></div>
                    </div>
                    <div class="field-group">
                        <label>Precio Unitario <span class="req">*</span></label>
                        <div class="input-wrap"><span class="prefix">$</span><input class="has-prefix" type="number" name="precio" min="0" step="0.01" placeholder="0.00" value="<?php echo isset($_POST['precio']) ? floatval($_POST['precio']) : ''; ?>" required></div>
                    </div>
                    <div class="field-group field-full">
                        <label>Proveedor <span class="req">*</span></label>
                        <input type="text" name="proveedores" placeholder="Ej: Walmart, HP, Samsung..." value="<?php echo isset($_POST['proveedores']) ? htmlspecialchars($_POST['proveedores']) : ''; ?>" required>
                    </div>
                </div>
                <hr class="divider">
                <div class="form-actions">
                    <a href="dashboard.php" class="btn-cancelar">✕ Cancelar</a>
                    <button type="submit" class="btn-guardar">💾 Guardar Producto</button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
