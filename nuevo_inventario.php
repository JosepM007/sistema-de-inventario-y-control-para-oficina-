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
        <a class="back-link" href="categorias.php">← Volver a categorías</a>

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
</body>
</html>
<?php $conn->close(); ?>
