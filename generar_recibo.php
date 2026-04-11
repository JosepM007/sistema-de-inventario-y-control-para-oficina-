<?php
session_start();
if (!isset($_SESSION['usuario'])) { header("Location: login.php"); exit; }
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) { header("Location: nuevo_inventario.php"); exit; }

require 'db.php';
$id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT id, nombre, descripcion, cantidad, precio, proveedores FROM productos WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if (!$p) { echo "<p style='font-family:sans-serif;color:red;padding:30px;'>Producto no encontrado.</p>"; exit; }

$precio_unit  = floatval($p['precio']);
$cantidad     = intval($p['cantidad']);
$subtotal     = $precio_unit * $cantidad;
$iva_pct      = 13;
$iva_monto    = $subtotal * ($iva_pct / 100);
$total        = $subtotal + $iva_monto;
$fecha_emision  = date('d/m/Y');
$hora_emision   = date('H:i:s');
$fecha_completa = date('d/m/Y H:i:s');
$num_recibo     = 'R-' . date('Y') . '-' . str_pad($p['id'], 4, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo <?php echo $num_recibo; ?> - OfficeStock Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Nunito', sans-serif; background: linear-gradient(135deg, #0f4c8a 0%, #0a7abf 45%, #00b4d8 100%); display: flex; flex-direction: column; align-items: center; padding: 40px 16px 60px; min-height: 100vh; }

        .recibo { background: #ffffff; width: 100%; max-width: 600px; border-radius: 18px; box-shadow: 0 20px 60px rgba(0,0,0,0.30); overflow: hidden; }

        .rec-header { background: linear-gradient(135deg, #0077b6, #00c8e8); color: #fff; padding: 28px 32px 22px; position: relative; }
        .rec-header .logo    { font-size: 22px; font-weight: 800; letter-spacing: -0.5px; }
        .rec-header .tagline { font-size: 12px; opacity: 0.78; margin-top: 2px; }
        .rec-header .num-box { position: absolute; top: 28px; right: 32px; text-align: right; }
        .rec-header .num-box .label { font-size: 11px; opacity: 0.72; text-transform: uppercase; letter-spacing: 1px; }
        .rec-header .num-box .num   { font-size: 20px; font-weight: 800; }

        .rec-body { padding: 28px 32px; }

        .rec-titulo { text-align:center; margin-bottom:20px; }
        .rec-titulo h2 { font-size: 13px; font-weight: 700; color: #0077b6; text-transform: uppercase; letter-spacing: 1.5px; }

        .divider { border:none; border-top:2px dashed #e0f4fb; margin:18px 0; }

        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px 24px; margin-bottom: 22px; }
        .info-item .lbl { font-size: 11px; text-transform: uppercase; letter-spacing: 0.8px; color: #7a95a8; font-weight: 700; margin-bottom: 2px; }
        .info-item .val { font-size: 14px; font-weight: 700; color: #1c2b3a; }
        .info-item.full { grid-column: 1 / -1; }

        .det-table { width:100%; border-collapse:collapse; margin-bottom:20px; font-size:13.5px; }
        .det-table thead th { background:#dceefb; color:#0077b6; font-weight:700; padding:10px 12px; text-align:left; border-bottom:2px solid #b8ddf0; }
        .det-table thead th:last-child { text-align:right; }
        .det-table tbody td { padding:10px 12px; border-bottom:1px solid #f0faff; color:#1c2b3a; vertical-align:top; }
        .det-table tbody td:nth-child(2) { text-align:center; }
        .det-table tbody td:nth-child(3),
        .det-table tbody td:last-child   { text-align:right; font-weight:700; }

        .totales { background:#f0faff; border-radius:10px; padding:16px 18px; margin-bottom:22px; }
        .tot-row { display:flex; justify-content:space-between; font-size:13.5px; color:#4a6275; padding:4px 0; }
        .tot-row.total { border-top:2px solid #b8ddf0; margin-top:8px; padding-top:10px; font-size:17px; font-weight:800; color:#0077b6; }

        .estado-badge { display:inline-flex; align-items:center; gap:6px; background:#d1fae5; color:#065f46; border-radius:20px; padding:4px 14px; font-size:12.5px; font-weight:700; }

        .rec-footer { background:#f4faff; border-top:1px solid #dceefb; padding:16px 32px; text-align:center; font-size:11.5px; color:#7a95a8; }
        .rec-footer strong { color:#0077b6; }

        .actions { display:flex; gap:12px; justify-content:center; margin-top:24px; }
        .btn-print, .btn-back { padding:10px 26px; border-radius:10px; font-size:14px; font-weight:700; cursor:pointer; border:none; font-family:'Nunito',sans-serif; transition:opacity 0.2s; }
        .btn-print { background:linear-gradient(135deg,#0077b6,#00c8e8); color:#fff; box-shadow: 0 6px 18px rgba(0,119,182,0.35); }
        .btn-back  { background:#f0f4f8; color:#1c2b3a; }
        .btn-print:hover, .btn-back:hover { opacity:0.88; }

        @media print {
            body { background:#fff; padding:0; }
            .recibo { box-shadow:none; border-radius:0; max-width:100%; }
            .actions { display:none; }
        }
    </style>
</head>
<body>
<div class="recibo">
    <div class="rec-header">
        <div class="logo">🗂️ OfficeStock Pro</div>
        <div class="tagline">Sistema de Inventario y Control Logístico</div>
        <div class="num-box">
            <div class="label">Recibo N°</div>
            <div class="num"><?php echo $num_recibo; ?></div>
        </div>
    </div>

    <div class="rec-body">
        <div class="rec-titulo"><h2>Recibo de Ingreso de Producto</h2></div>

        <div class="info-grid">
            <div class="info-item"><div class="lbl">Fecha de Emisión</div><div class="val"><?php echo $fecha_emision; ?></div></div>
            <div class="info-item"><div class="lbl">Hora de Emisión</div><div class="val"><?php echo $hora_emision; ?></div></div>
            <div class="info-item"><div class="lbl">Proveedor</div><div class="val"><?php echo htmlspecialchars($p['proveedores'] ?? 'N/A'); ?></div></div>
            <div class="info-item"><div class="lbl">ID de Producto</div><div class="val">#<?php echo intval($p['id']); ?></div></div>
            <div class="info-item"><div class="lbl">Registrado por</div><div class="val"><?php echo htmlspecialchars($_SESSION['usuario']); ?></div></div>
            <div class="info-item"><div class="lbl">Estado</div><div class="val"><span class="estado-badge">✅ Recibido</span></div></div>
            <?php if (!empty($p['descripcion'])): ?>
            <div class="info-item full"><div class="lbl">Descripción</div><div class="val" style="font-weight:400;color:#4a6275;"><?php echo htmlspecialchars($p['descripcion']); ?></div></div>
            <?php endif; ?>
        </div>

        <hr class="divider">

        <table class="det-table">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th style="text-align:center;">Cantidad Recibida</th>
                    <th style="text-align:right;">Precio Unit.</th>
                    <th style="text-align:right;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($p['nombre']); ?></strong><br>
                        <span style="font-size:12px;color:#7a95a8;">Proveedor: <?php echo htmlspecialchars($p['proveedores'] ?? '—'); ?></span>
                    </td>
                    <td><?php echo $cantidad; ?> unidades</td>
                    <td>$<?php echo number_format($precio_unit, 2); ?></td>
                    <td>$<?php echo number_format($subtotal, 2); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="totales">
            <div class="tot-row"><span>Subtotal</span><span>$<?php echo number_format($subtotal, 2); ?></span></div>
            <div class="tot-row"><span>IVA (<?php echo $iva_pct; ?>%)</span><span>$<?php echo number_format($iva_monto, 2); ?></span></div>
            <div class="tot-row total"><span>TOTAL</span><span>$<?php echo number_format($total, 2); ?></span></div>
        </div>

        <div style="text-align:right; font-size:12px; color:#7a95a8;">
            Recibo generado el: <strong><?php echo $fecha_completa; ?></strong>
        </div>
    </div>

    <div class="rec-footer">
        <strong>OfficeStock Pro</strong> · Sistema de Inventario y Control Logístico<br>
        Comprobante interno de ingreso de suministros de oficina.<br>
        Recibo N° <strong><?php echo $num_recibo; ?></strong>
    </div>
</div>

<div class="actions">
    <button class="btn-print" onclick="window.print()">🖨️ Imprimir / Guardar PDF</button>
    <button class="btn-back"  onclick="window.close()">✕ Cerrar</button>
</div>
</body>
</html>
