<?php
session_start();
if (!isset($_SESSION['usuario'])) { header("Location: login.php"); exit; }
require 'db.php';

$success = $_SESSION['success'] ?? ''; $error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

$productos_res = $conn->query("SELECT id, nombre, cantidad, precio, proveedores FROM productos ORDER BY nombre ASC");
$productos = [];
while ($r = $productos_res->fetch_assoc()) $productos[] = $r;

$historial_res = $conn->query("SELECT s.*, p.cantidad AS stock_actual FROM salidas s LEFT JOIN productos p ON s.producto_id = p.id ORDER BY s.fecha DESC LIMIT 50");
$historial = [];
while ($r = $historial_res->fetch_assoc()) $historial[] = $r;

$total_salidas  = $conn->query("SELECT COUNT(*) as t FROM salidas")->fetch_assoc()['t'];
$total_unidades = $conn->query("SELECT SUM(cantidad_salida) as t FROM salidas")->fetch_assoc()['t'] ?? 0;
$salidas_hoy    = $conn->query("SELECT COUNT(*) as t FROM salidas WHERE DATE(fecha) = CURDATE()")->fetch_assoc()['t'];
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salidas de Productos - OfficeStock Pro</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --card-bg:rgba(255,255,255,0.10); --card-border:rgba(255,255,255,0.16); --text-muted:rgba(255,255,255,0.55); }
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Nunito',sans-serif;background:linear-gradient(135deg,#0f4c8a 0%,#0a7abf 45%,#00b4d8 100%);min-height:100vh;color:#fff}
        .layout{display:flex;min-height:100vh}
        .sidebar{width:220px;flex-shrink:0;background:rgba(0,0,0,0.28);backdrop-filter:blur(16px);border-right:1px solid var(--card-border);display:flex;flex-direction:column;padding:28px 0 24px;position:sticky;top:0;height:100vh}
        .logo{font-size:19px;font-weight:800;color:#fff;padding:0 24px 24px;border-bottom:1px solid var(--card-border);margin-bottom:12px}
        .sidebar a{display:flex;align-items:center;gap:10px;padding:11px 24px;color:var(--text-muted);text-decoration:none;font-size:13.5px;font-weight:600;border-left:3px solid transparent;transition:all 0.18s}
        .sidebar a:hover{color:#fff;background:rgba(255,255,255,0.08)}
        .sidebar a.active{color:#fff;border-left-color:#ef4444;background:rgba(239,68,68,0.14)}
        .sidebar .logout-link{margin-top:auto;color:#fca5a5;border-top:1px solid var(--card-border);padding-top:14px}
        .main-content{flex:1;display:flex;flex-direction:column;overflow-y:auto}
        .top-bar{padding:22px 30px 18px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid var(--card-border);background:rgba(0,0,0,0.15);backdrop-filter:blur(6px);position:sticky;top:0;z-index:10}
        .top-bar h1{font-size:20px;font-weight:800}.top-bar .sub{font-size:12px;color:var(--text-muted);margin-top:1px}
        .user-chip{display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,0.12);border:1px solid var(--card-border);border-radius:20px;padding:6px 14px;font-size:13px;font-weight:600}
        .dot-red{width:8px;height:8px;border-radius:50%;background:#ef4444}
        .page-body{padding:26px 30px 40px;display:flex;flex-direction:column;gap:22px}
        .alert{display:flex;align-items:center;gap:10px;padding:13px 16px;border-radius:11px;font-size:13.5px;font-weight:600;animation:slideIn .3s ease}
        @keyframes slideIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}
        .alert-ok{background:rgba(16,185,129,0.16);border:1px solid rgba(16,185,129,0.32);color:#6ee7b7}
        .alert-err{background:rgba(239,68,68,0.16);border:1px solid rgba(239,68,68,0.32);color:#fca5a5}
        .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px}
        .stat-card{background:var(--card-bg);border:1px solid var(--card-border);border-radius:14px;padding:18px 20px;display:flex;align-items:center;gap:14px;transition:transform .18s}
        .stat-card:hover{transform:translateY(-3px)}
        .stat-icon{width:46px;height:46px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0}
        .ic-red{background:rgba(239,68,68,0.22)}.ic-amber{background:rgba(245,158,11,0.22)}.ic-cyan{background:rgba(0,200,232,0.22)}
        .stat-num{font-size:22px;font-weight:800;line-height:1}.stat-label{font-size:11.5px;color:var(--text-muted);margin-top:3px;font-weight:600}
        .two-col{display:grid;grid-template-columns:1fr 1.6fr;gap:20px;align-items:start}
        .card{background:var(--card-bg);border:1px solid var(--card-border);border-radius:16px;overflow:hidden;box-shadow:0 12px 36px rgba(0,0,0,0.18)}
        .card-head{padding:16px 22px;border-bottom:1px solid var(--card-border);background:rgba(255,255,255,0.04);display:flex;justify-content:space-between;align-items:center}
        .card-head h3{font-size:15px;font-weight:700;display:flex;align-items:center;gap:8px}
        .card-body{padding:22px}
        .field{margin-bottom:16px}
        .field label{display:block;font-size:11.5px;font-weight:700;color:rgba(255,255,255,0.68);text-transform:uppercase;letter-spacing:.7px;margin-bottom:7px}
        .field label .req{color:#7fecf8;margin-left:2px}
        .field select,.field input,.field textarea{width:100%;background:rgba(255,255,255,0.10);border:1px solid rgba(255,255,255,0.18);border-radius:10px;padding:11px 14px;color:#fff;font-family:'Nunito',sans-serif;font-size:14px;outline:none;transition:border-color .2s,background .2s,box-shadow .2s}
        .field select option{background:#0a4a72;color:#fff}
        .field select:focus,.field input:focus,.field textarea:focus{border-color:#ef4444;background:rgba(255,255,255,0.14);box-shadow:0 0 0 3px rgba(239,68,68,0.16)}
        .field input::placeholder,.field textarea::placeholder{color:rgba(255,255,255,0.28)}
        .field textarea{resize:vertical;min-height:80px}
        .stock-preview{display:none;background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.12);border-radius:10px;padding:12px 14px;margin-top:8px;font-size:13px}
        .stock-preview.show{display:block}
        .sp-row{display:flex;justify-content:space-between;margin-bottom:4px}.sp-row:last-child{margin-bottom:0}
        .sp-label{color:var(--text-muted)}.sp-val{font-weight:700;color:#fff}
        .sp-val.low{color:#fca5a5}.sp-val.ok{color:#6ee7b7}
        .divider{border:none;border-top:1px solid rgba(255,255,255,0.10);margin:18px 0}
        .btn-salida{width:100%;padding:12px;background:linear-gradient(135deg,#991b1b,#ef4444);color:#fff;border:none;border-radius:11px;font-family:'Nunito',sans-serif;font-size:14px;font-weight:800;cursor:pointer;box-shadow:0 6px 20px rgba(239,68,68,0.32);transition:opacity .2s,transform .15s;display:flex;align-items:center;justify-content:center;gap:8px}
        .btn-salida:hover{opacity:.88;transform:translateY(-1px)}.btn-salida:active{transform:translateY(0)}
        .table-wrap{overflow-x:auto}
        table{width:100%;border-collapse:collapse;font-size:13px}
        thead th{padding:12px 16px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#fca5a5;border-bottom:1px solid var(--card-border);white-space:nowrap}
        tbody tr{border-bottom:1px solid rgba(255,255,255,0.06);transition:background .15s}
        tbody tr:last-child{border-bottom:none}
        tbody tr:hover{background:rgba(255,255,255,0.06)}
        tbody td{padding:12px 16px;color:#fff;vertical-align:middle}
        .id-badge{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:7px;background:rgba(239,68,68,0.20);color:#fca5a5;font-size:12px;font-weight:700}
        .prod-name{font-weight:700;color:#fff}.motivo-cell{color:var(--text-muted);font-size:12.5px}
        .qty-salida{color:#fca5a5;font-weight:800}
        .area-badge{background:rgba(0,200,232,0.20);color:#7fecf8;border-radius:6px;padding:2px 9px;font-size:11.5px;font-weight:700}
        .usuario-cell{color:#fcd34d;font-size:12.5px;font-weight:600}
        .fecha-cell{color:var(--text-muted);font-size:12px}
        .empty-msg{text-align:center;padding:40px;color:var(--text-muted)}
        .count-badge{background:rgba(239,68,68,0.20);color:#fca5a5;border-radius:20px;padding:3px 12px;font-size:12px;font-weight:700}
        @media(max-width:900px){.two-col{grid-template-columns:1fr}}
        @media(max-width:768px){.sidebar{display:none}.page-body{padding:16px}}
    </style>
</head>
<body>
<div class="layout">
    <div class="sidebar">
        <div class="logo">🗂️ OfficeStock</div>
        <a href="dashboard.php">🏠 Dashboard</a>
        <?php if ($_SESSION['rol'] == 'admin'): ?><a href="productos.php">📦 Productos</a><a href="usuarios.php">👥 Usuarios</a><?php endif; ?>
        <a href="categorias.php">📂 Categorías</a>
        <a href="proveedores.php">🏢 Proveedores</a>
        <a href="nuevo_inventario.php">📋 Inventario</a>
        <a href="salidas.php" class="active">📤 Salidas</a>
        <a href="auditoria.php">🔍 Auditoría</a>
        <a href="logout.php" class="logout-link">🚪 Cerrar Sesión</a>
    </div>
    <div class="main-content">
        <div class="top-bar">
            <div><h1>📤 Salidas de Productos</h1><div class="sub">Inicio / Salidas</div></div>
            <div class="user-chip"><span class="dot-red"></span><?php echo htmlspecialchars($_SESSION['usuario']); ?> · <?php echo ucfirst($_SESSION['rol']); ?></div>
        </div>
        <div class="page-body">
            <?php if ($success): ?><div class="alert alert-ok">✅ <?php echo htmlspecialchars($success); ?></div><?php endif; ?>
            <?php if ($error):   ?><div class="alert alert-err">⚠️ <?php echo htmlspecialchars($error); ?></div><?php endif; ?>
            <div class="stats-grid">
                <div class="stat-card"><div class="stat-icon ic-red">📤</div><div><div class="stat-num"><?php echo $total_salidas; ?></div><div class="stat-label">Total Salidas</div></div></div>
                <div class="stat-card"><div class="stat-icon ic-amber">📦</div><div><div class="stat-num"><?php echo number_format($total_unidades); ?></div><div class="stat-label">Unidades Retiradas</div></div></div>
                <div class="stat-card"><div class="stat-icon ic-cyan">📅</div><div><div class="stat-num"><?php echo $salidas_hoy; ?></div><div class="stat-label">Salidas Hoy</div></div></div>
            </div>
            <div class="two-col">
                <div class="card">
                    <div class="card-head"><h3>📝 Registrar Salida</h3></div>
                    <div class="card-body">
                        <form method="POST" action="procesar_salida.php" id="formSalida">
                            <div class="field">
                                <label>Producto <span class="req">*</span></label>
                                <select name="producto_id" id="selectProducto" required onchange="mostrarStock()">
                                    <option value="">— Selecciona un producto —</option>
                                    <?php foreach ($productos as $prod): ?>
                                        <option value="<?php echo $prod['id']; ?>" data-stock="<?php echo $prod['cantidad']; ?>" data-precio="<?php echo $prod['precio']; ?>" data-prov="<?php echo htmlspecialchars($prod['proveedores']); ?>" data-nombre="<?php echo htmlspecialchars($prod['nombre']); ?>">
                                            <?php echo htmlspecialchars($prod['nombre']); ?> (Stock: <?php echo $prod['cantidad']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="stock-preview" id="stockPreview">
                                    <div class="sp-row"><span class="sp-label">📦 Producto:</span><span class="sp-val" id="spNombre">—</span></div>
                                    <div class="sp-row"><span class="sp-label">🏢 Proveedor:</span><span class="sp-val" id="spProv">—</span></div>
                                    <div class="sp-row"><span class="sp-label">📊 Stock disponible:</span><span class="sp-val" id="spStock">—</span></div>
                                    <div class="sp-row"><span class="sp-label">💲 Precio unitario:</span><span class="sp-val" id="spPrecio">—</span></div>
                                </div>
                            </div>
                            <div class="field"><label>Cantidad a Retirar <span class="req">*</span></label><input type="number" name="cantidad_salida" id="inputCantidad" min="1" placeholder="Ej: 5" required onchange="validarCantidad()"></div>
                            <div class="field"><label>Área / Destino <span class="req">*</span></label><input type="text" name="area_destino" placeholder="Ej: Recursos Humanos, Contabilidad..." required></div>
                            <div class="field"><label>Motivo de Salida</label><textarea name="motivo" placeholder="Ej: Reposición mensual de papelería..."></textarea></div>
                            <hr class="divider">
                            <button type="submit" class="btn-salida">📤 Registrar Salida</button>
                        </form>
                    </div>
                </div>
                <div class="card">
                    <div class="card-head"><h3>🕒 Historial de Salidas</h3><span class="count-badge"><?php echo $total_salidas; ?> registros</span></div>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>#</th><th>Producto</th><th>Cant.</th><th>Área</th><th>Usuario</th><th>Fecha</th></tr></thead>
                            <tbody>
                            <?php if (empty($historial)): ?>
                                <tr><td colspan="6" class="empty-msg">📭 No hay salidas registradas aún.</td></tr>
                            <?php else: ?>
                                <?php foreach ($historial as $s): ?>
                                <tr>
                                    <td><span class="id-badge"><?php echo $s['id']; ?></span></td>
                                    <td><div class="prod-name"><?php echo htmlspecialchars($s['producto_nombre']); ?></div><?php if (!empty($s['motivo'])): ?><div class="motivo-cell"><?php echo htmlspecialchars($s['motivo']); ?></div><?php endif; ?></td>
                                    <td><span class="qty-salida">-<?php echo intval($s['cantidad_salida']); ?> uds</span></td>
                                    <td><span class="area-badge"><?php echo htmlspecialchars($s['area_destino']); ?></span></td>
                                    <td><span class="usuario-cell">👤 <?php echo htmlspecialchars($s['usuario']); ?></span></td>
                                    <td><div class="fecha-cell"><?php echo date('d/m/Y', strtotime($s['fecha'])); ?></div><div class="fecha-cell"><?php echo date('H:i', strtotime($s['fecha'])); ?></div></td>
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
</div>
<script>
const productosData = {<?php foreach ($productos as $prod): ?><?php echo $prod['id']; ?>:{nombre:"<?php echo addslashes($prod['nombre']); ?>",stock:<?php echo intval($prod['cantidad']); ?>,precio:<?php echo floatval($prod['precio']); ?>,prov:"<?php echo addslashes($prod['proveedores']); ?>"},<?php endforeach; ?>};
function mostrarStock(){const sel=document.getElementById('selectProducto');const id=parseInt(sel.value);const prev=document.getElementById('stockPreview');if(!id||!productosData[id]){prev.classList.remove('show');return;}const p=productosData[id];prev.classList.add('show');document.getElementById('spNombre').textContent=p.nombre;document.getElementById('spProv').textContent=p.prov;document.getElementById('spPrecio').textContent='$'+p.precio.toFixed(2);const stockEl=document.getElementById('spStock');stockEl.textContent=p.stock+' unidades';stockEl.className='sp-val '+(p.stock<10?'low':'ok');document.getElementById('inputCantidad').max=p.stock;}
function validarCantidad(){const sel=document.getElementById('selectProducto');const id=parseInt(sel.value);if(!id||!productosData[id])return;const cant=parseInt(document.getElementById('inputCantidad').value);const stock=productosData[id].stock;if(cant>stock){alert('⚠️ La cantidad ('+cant+') supera el stock disponible ('+stock+' uds).');document.getElementById('inputCantidad').value=stock;}}
document.getElementById('formSalida').addEventListener('submit',function(e){const sel=document.getElementById('selectProducto');const cant=document.getElementById('inputCantidad').value;const id=parseInt(sel.value);if(!id){e.preventDefault();alert('Selecciona un producto.');return;}const nombre=productosData[id]?.nombre??'el producto';if(!confirm('¿Confirmas la salida de '+cant+' unidad(es) de "'+nombre+'"?'))e.preventDefault();});
</script>
</body>
</html>
