<?php
session_start();
if (!isset($_SESSION['usuario'])) { header("Location: login.php"); exit; }
require 'db.php';

$success = $_SESSION['success'] ?? ''; $error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// ── Crear tabla salidas si no existe ──────────────────────────────────────────
$conn->query("
    CREATE TABLE IF NOT EXISTS `salidas` (
        `id`               INT(11) NOT NULL AUTO_INCREMENT,
        `producto_id`      INT(11) DEFAULT NULL,
        `producto_nombre`  VARCHAR(150) DEFAULT NULL,
        `cantidad_salida`  INT(11) DEFAULT 0,
        `motivo`           TEXT DEFAULT NULL,
        `area_destino`     VARCHAR(150) DEFAULT NULL,
        `usuario`          VARCHAR(100) DEFAULT NULL,
        `fecha`            DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// ── Agregar columna producto_nombre si la tabla existe pero le falta la columna ─
$col_check = $conn->query("SHOW COLUMNS FROM `salidas` LIKE 'producto_nombre'");
if ($col_check && $col_check->num_rows === 0) {
    $conn->query("ALTER TABLE `salidas` ADD COLUMN `producto_nombre` VARCHAR(150) DEFAULT NULL AFTER `producto_id`");
}

$productos_res = $conn->query("SELECT id, nombre, cantidad, precio, proveedores FROM productos ORDER BY nombre ASC");
$productos = [];
if ($productos_res) {
    while ($r = $productos_res->fetch_assoc()) $productos[] = $r;
}

$historial_res = $conn->query("SELECT s.id, s.producto_id, s.producto_nombre, s.cantidad_salida, s.motivo, s.area_destino, s.usuario, s.fecha, p.cantidad AS stock_actual FROM salidas s LEFT JOIN productos p ON s.producto_id = p.id ORDER BY s.fecha DESC LIMIT 50");
$historial = [];
if ($historial_res) {
    while ($r = $historial_res->fetch_assoc()) $historial[] = $r;
}

$res_ts = $conn->query("SELECT COUNT(*) as t FROM salidas");
$total_salidas  = $res_ts ? $res_ts->fetch_assoc()['t'] : 0;
$res_tu = $conn->query("SELECT SUM(cantidad_salida) as t FROM salidas");
$total_unidades = ($res_tu ? $res_tu->fetch_assoc()['t'] : 0) ?? 0;
$res_th = $conn->query("SELECT COUNT(*) as t FROM salidas WHERE DATE(fecha) = CURDATE()");
$salidas_hoy    = $res_th ? $res_th->fetch_assoc()['t'] : 0;
$conn->close();

$es_admin_jose = (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin' && strtolower($_SESSION['usuario']) === 'jose');
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
        /* Botones admin */
        .admin-actions{display:flex;gap:5px}
        .btn-edit{display:inline-flex;align-items:center;gap:3px;background:rgba(245,158,11,0.18);border:1px solid rgba(245,158,11,0.35);color:#fcd34d;border-radius:7px;padding:4px 10px;font-size:11.5px;font-weight:700;text-decoration:none;transition:background .15s}
        .btn-edit:hover{background:rgba(245,158,11,0.35)}
        .btn-del{display:inline-flex;align-items:center;gap:3px;background:rgba(239,68,68,0.18);border:1px solid rgba(239,68,68,0.35);color:#fca5a5;border-radius:7px;padding:4px 10px;font-size:11.5px;font-weight:700;cursor:pointer;text-decoration:none;transition:background .15s}
        .btn-del:hover{background:rgba(239,68,68,0.35)}
        /* ♿ Barra de accesibilidad */
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
        /* Skip link accesibilidad */
        .skip-link{position:absolute;top:-50px;left:10px;background:#ef4444;color:#fff;padding:8px 16px;border-radius:8px;font-weight:700;font-size:14px;z-index:9999;transition:top .2s;text-decoration:none}
        .skip-link:focus{top:10px}
        /* Alto contraste (respeta preferencia del SO) */
        @media(prefers-contrast:more){.sidebar,.voz-bar{border-width:2px}.btn-salida,.btn-voz{outline:2px solid #fff}}
        @media(max-width:900px){.two-col{grid-template-columns:1fr}}
        @media(max-width:768px){.sidebar{display:none}.page-body{padding:16px}}
    </style>
</head>
<body>
<!-- ♿ Skip link para usuarios de teclado/lector de pantalla -->
<a class="skip-link" href="#contenido-principal">Saltar al contenido principal</a>

<div class="layout">
    <nav class="sidebar" role="navigation" aria-label="Menú principal">
        <div class="logo" role="banner">🗂️ OfficeStock</div>
        <a href="dashboard.php">🏠 Dashboard</a>
        <?php if ($_SESSION['rol'] == 'admin'): ?>
            <a href="productos.php">📦 Productos</a>
            <a href="usuarios.php">👥 Usuarios</a>
        <?php endif; ?>
        <a href="categorias.php">📂 Categorías</a>
        <a href="proveedores.php">🏢 Proveedores</a>
        <a href="nuevo_inventario.php">📋 Inventario</a>
        <a href="salidas.php" class="active" aria-current="page">📤 Salidas</a>
        <a href="auditoria.php">🔍 Auditoría</a>
        <a href="logout.php" class="logout-link">🚪 Cerrar Sesión</a>
    </nav>

    <div class="main-content" role="main" id="contenido-principal">
        <header class="top-bar">
            <div>
                <h1>📤 Salidas de Productos</h1>
                <div class="sub">Inicio / Salidas</div>
            </div>
            <div class="user-chip" aria-label="Usuario activo">
                <span class="dot-red" aria-hidden="true"></span>
                <?php echo htmlspecialchars($_SESSION['usuario']); ?> · <?php echo ucfirst($_SESSION['rol']); ?>
            </div>
        </header>

        <div class="page-body">

            <?php if ($success): ?>
                <div class="alert alert-ok" role="alert" aria-live="polite">✅ <?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-err" role="alert" aria-live="polite">⚠️ <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- ♿ BARRA DE ACCESIBILIDAD POR VOZ -->
            <section class="voz-bar" role="region" aria-label="Asistente de voz para personas con discapacidad visual">
                <div class="voz-title" aria-hidden="true">♿ 🔊 Asistente de Voz</div>
                <span class="voz-status" id="vozStatus" aria-live="polite" aria-atomic="true">Listo</span>
                <div class="voz-btns">
                    <button class="btn-voz bv-green" onclick="leerPagina()" aria-label="Leer toda la página en voz alta para personas con discapacidad visual">
                        🔊 Leer página
                    </button>
                    <button class="btn-voz bv-cyan" onclick="leerAyuda()" aria-label="Escuchar instrucciones de ayuda de accesibilidad">
                        ❓ Ayuda
                    </button>
                    <button class="btn-voz bv-red" onclick="detenerVoz()" aria-label="Detener la lectura de voz">
                        ⏹ Detener
                    </button>
                </div>
            </section>

            <!-- ESTADÍSTICAS -->
            <section aria-label="Resumen estadístico de salidas">
                <div class="stats-grid">
                    <div class="stat-card" tabindex="0" aria-label="Total salidas: <?php echo $total_salidas; ?>">
                        <div class="stat-icon ic-red" aria-hidden="true">📤</div>
                        <div><div class="stat-num"><?php echo $total_salidas; ?></div><div class="stat-label">Total Salidas</div></div>
                    </div>
                    <div class="stat-card" tabindex="0" aria-label="Unidades retiradas: <?php echo number_format($total_unidades); ?>">
                        <div class="stat-icon ic-amber" aria-hidden="true">📦</div>
                        <div><div class="stat-num"><?php echo number_format($total_unidades); ?></div><div class="stat-label">Unidades Retiradas</div></div>
                    </div>
                    <div class="stat-card" tabindex="0" aria-label="Salidas hoy: <?php echo $salidas_hoy; ?>">
                        <div class="stat-icon ic-cyan" aria-hidden="true">📅</div>
                        <div><div class="stat-num"><?php echo $salidas_hoy; ?></div><div class="stat-label">Salidas Hoy</div></div>
                    </div>
                </div>
            </section>

            <div class="two-col">
                <!-- FORMULARIO REGISTRAR SALIDA -->
                <div class="card">
                    <div class="card-head"><h3>📝 Registrar Salida</h3></div>
                    <div class="card-body">
                        <form method="POST" action="procesar_salida.php" id="formSalida" aria-label="Formulario para registrar una salida de producto">
                            <div class="field">
                                <label for="selectProducto">Producto <span class="req" aria-hidden="true">*</span></label>
                                <select name="producto_id" id="selectProducto" required aria-required="true" onchange="mostrarStock()">
                                    <option value="">— Selecciona un producto —</option>
                                    <?php foreach ($productos as $prod): ?>
                                        <option value="<?php echo $prod['id']; ?>"
                                            data-stock="<?php echo $prod['cantidad']; ?>"
                                            data-precio="<?php echo $prod['precio']; ?>"
                                            data-prov="<?php echo htmlspecialchars($prod['proveedores']); ?>"
                                            data-nombre="<?php echo htmlspecialchars($prod['nombre']); ?>">
                                            <?php echo htmlspecialchars($prod['nombre']); ?> (Stock: <?php echo $prod['cantidad']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="stock-preview" id="stockPreview" aria-live="polite" aria-label="Información del producto seleccionado">
                                    <div class="sp-row"><span class="sp-label">📦 Producto:</span><span class="sp-val" id="spNombre">—</span></div>
                                    <div class="sp-row"><span class="sp-label">🏢 Proveedor:</span><span class="sp-val" id="spProv">—</span></div>
                                    <div class="sp-row"><span class="sp-label">📊 Stock disponible:</span><span class="sp-val" id="spStock">—</span></div>
                                    <div class="sp-row"><span class="sp-label">💲 Precio unitario:</span><span class="sp-val" id="spPrecio">—</span></div>
                                </div>
                            </div>
                            <div class="field">
                                <label for="inputCantidad">Cantidad a Retirar <span class="req" aria-hidden="true">*</span></label>
                                <input type="number" name="cantidad_salida" id="inputCantidad" min="1" placeholder="Ej: 5" required aria-required="true" onchange="validarCantidad()">
                            </div>
                            <div class="field">
                                <label for="inputArea">Área / Destino <span class="req" aria-hidden="true">*</span></label>
                                <input type="text" name="area_destino" id="inputArea" placeholder="Ej: Recursos Humanos, Contabilidad..." required aria-required="true">
                            </div>
                            <div class="field">
                                <label for="inputMotivo">Motivo de Salida</label>
                                <textarea name="motivo" id="inputMotivo" placeholder="Ej: Reposición mensual de papelería..."></textarea>
                            </div>
                            <hr class="divider">
                            <button type="submit" class="btn-salida" aria-label="Confirmar y registrar la salida del producto">📤 Registrar Salida</button>
                        </form>
                    </div>
                </div>

                <!-- HISTORIAL -->
                <div class="card">
                    <div class="card-head">
                        <h3>🕒 Historial de Salidas</h3>
                        <span class="count-badge" aria-label="<?php echo $total_salidas; ?> registros"><?php echo $total_salidas; ?> registros</span>
                    </div>
                    <div class="table-wrap">
                        <table aria-label="Historial de salidas de productos">
                            <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">Producto</th>
                                    <th scope="col">Cant.</th>
                                    <th scope="col">Área</th>
                                    <th scope="col">Usuario</th>
                                    <th scope="col">Fecha</th>
                                    <?php if ($es_admin_jose): ?><th scope="col">Acciones</th><?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($historial)): ?>
                                <tr>
                                    <td colspan="<?php echo $es_admin_jose ? 7 : 6; ?>" class="empty-msg">
                                        📭 No hay salidas registradas aún.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($historial as $s): ?>
                                <tr>
                                    <td><span class="id-badge" aria-label="ID <?php echo $s['id']; ?>"><?php echo $s['id']; ?></span></td>
                                    <td>
                                        <div class="prod-name"><?php echo htmlspecialchars($s['producto_nombre']); ?></div>
                                        <?php if (!empty($s['motivo'])): ?>
                                            <div class="motivo-cell"><?php echo htmlspecialchars($s['motivo']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="qty-salida" aria-label="<?php echo intval($s['cantidad_salida']); ?> unidades retiradas">-<?php echo intval($s['cantidad_salida']); ?> uds</span></td>
                                    <td><span class="area-badge"><?php echo htmlspecialchars($s['area_destino']); ?></span></td>
                                    <td><span class="usuario-cell">👤 <?php echo htmlspecialchars($s['usuario']); ?></span></td>
                                    <td>
                                        <div class="fecha-cell"><?php echo date('d/m/Y', strtotime($s['fecha'])); ?></div>
                                        <div class="fecha-cell"><?php echo date('H:i', strtotime($s['fecha'])); ?></div>
                                    </td>
                                    <?php if ($es_admin_jose): ?>
                                    <td>
                                        <div class="admin-actions" role="group" aria-label="Acciones para salida #<?php echo $s['id']; ?>">
                                            <a href="editar_salida.php?id=<?php echo $s['id']; ?>"
                                               class="btn-edit"
                                               aria-label="Editar salida número <?php echo $s['id']; ?>, producto <?php echo htmlspecialchars($s['producto_nombre']); ?>">
                                                ✏️ Editar
                                            </a>
                                            <a href="eliminar_salida.php?id=<?php echo $s['id']; ?>"
                                               class="btn-del"
                                               aria-label="Eliminar salida número <?php echo $s['id']; ?>, producto <?php echo htmlspecialchars($s['producto_nombre']); ?>"
                                               onclick="return confirm('¿Eliminar esta salida?\nNota: el stock del producto NO se restaurará automáticamente.')">
                                                🗑️ Eliminar
                                            </a>
                                        </div>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div><!-- /two-col -->
        </div><!-- /page-body -->
    </div><!-- /main-content -->
</div><!-- /layout -->

<script>
/* ── Datos productos ── */
const productosData = {<?php foreach ($productos as $prod): ?><?php echo $prod['id']; ?>:{nombre:"<?php echo addslashes($prod['nombre']); ?>",stock:<?php echo intval($prod['cantidad']); ?>,precio:<?php echo floatval($prod['precio']); ?>,prov:"<?php echo addslashes($prod['proveedores']); ?>"},<?php endforeach; ?>};

function mostrarStock() {
    const sel  = document.getElementById('selectProducto');
    const id   = parseInt(sel.value);
    const prev = document.getElementById('stockPreview');
    if (!id || !productosData[id]) { prev.classList.remove('show'); return; }
    const p = productosData[id];
    prev.classList.add('show');
    document.getElementById('spNombre').textContent = p.nombre;
    document.getElementById('spProv').textContent   = p.prov;
    document.getElementById('spPrecio').textContent = '$' + p.precio.toFixed(2);
    const stockEl = document.getElementById('spStock');
    stockEl.textContent = p.stock + ' unidades';
    stockEl.className   = 'sp-val ' + (p.stock < 10 ? 'low' : 'ok');
    document.getElementById('inputCantidad').max = p.stock;
    hablar('Producto seleccionado: ' + p.nombre + '. Stock disponible: ' + p.stock + ' unidades.', true);
}

function validarCantidad() {
    const sel   = document.getElementById('selectProducto');
    const id    = parseInt(sel.value);
    if (!id || !productosData[id]) return;
    const cant  = parseInt(document.getElementById('inputCantidad').value);
    const stock = productosData[id].stock;
    if (cant > stock) {
        hablar('Advertencia: la cantidad ingresada supera el stock disponible de ' + stock + ' unidades.');
        alert('⚠️ La cantidad (' + cant + ') supera el stock disponible (' + stock + ' uds).');
        document.getElementById('inputCantidad').value = stock;
    }
}

document.getElementById('formSalida').addEventListener('submit', function(e) {
    const sel  = document.getElementById('selectProducto');
    const cant = document.getElementById('inputCantidad').value;
    const id   = parseInt(sel.value);
    if (!id) {
        e.preventDefault();
        hablar('Por favor selecciona un producto antes de continuar.');
        alert('Selecciona un producto.');
        return;
    }
    const nombre = productosData[id]?.nombre ?? 'el producto';
    if (!confirm('¿Confirmas la salida de ' + cant + ' unidad(es) de "' + nombre + '"?')) {
        e.preventDefault();
    }
});

/* ═══════════════════════════════════════
   ♿ SISTEMA DE VOZ — inclusivo
   Usa la Web Speech API (SpeechSynthesis)
   ═══════════════════════════════════════ */
const statusEl = document.getElementById('vozStatus');

function hablar(texto, encolar = false) {
    if (!('speechSynthesis' in window)) return;
    if (!encolar) window.speechSynthesis.cancel();
    const utt    = new SpeechSynthesisUtterance(texto);
    utt.lang     = 'es-ES';
    utt.rate     = 0.92;
    utt.pitch    = 1;
    utt.volume   = 1;
    utt.onstart  = () => { statusEl.textContent = '🔊 Hablando...'; statusEl.className = 'voz-status activo'; };
    utt.onend    = () => { statusEl.textContent = 'Listo'; statusEl.className = 'voz-status'; };
    window.speechSynthesis.speak(utt);
}

function detenerVoz() {
    window.speechSynthesis.cancel();
    statusEl.textContent = 'Detenido';
    statusEl.className   = 'voz-status';
}

function leerPagina() {
    window.speechSynthesis.cancel();

    const usuario  = <?php echo json_encode($_SESSION['usuario']); ?>;
    const totalSal = <?php echo intval($total_salidas); ?>;
    const totalUnd = <?php echo intval($total_unidades); ?>;
    const salHoy   = <?php echo intval($salidas_hoy); ?>;
    const esAdmin  = <?php echo $es_admin_jose ? 'true' : 'false'; ?>;
    const historial = <?php
        $h = [];
        foreach ($historial as $s) {
            $h[] = [
                'id'       => $s['id'],
                'nombre'   => $s['producto_nombre'],
                'cantidad' => $s['cantidad_salida'],
                'area'     => $s['area_destino'],
                'usuario'  => $s['usuario'],
                'fecha'    => date('d/m/Y', strtotime($s['fecha']))
            ];
        }
        echo json_encode($h, JSON_UNESCAPED_UNICODE);
    ?>;

    const textos = [];
    textos.push('Hola ' + usuario + '. Estás en la sección de Salidas de Productos de OfficeStock.');
    textos.push('Resumen: ' + totalSal + ' salidas totales. ' + totalUnd + ' unidades retiradas en total. ' + salHoy + ' salidas registradas hoy.');
    textos.push('Para registrar una salida: elige un producto, indica cuántas unidades retirar, el área que las recibirá y opcionalmente el motivo. Luego presiona el botón Registrar Salida.');

    if (historial.length === 0) {
        textos.push('El historial está vacío. No hay salidas registradas.');
    } else {
        textos.push('Historial: se muestran las últimas ' + historial.length + ' salidas.');
        historial.slice(0, 5).forEach((s, i) => {
            textos.push(
                'Registro ' + (i + 1) + ': ' + s.nombre +
                '. Cantidad: ' + s.cantidad + ' unidades. Área: ' + s.area +
                '. Registrado por ' + s.usuario + ' el ' + s.fecha + '.'
            );
        });
        if (historial.length > 5) {
            textos.push('Hay ' + (historial.length - 5) + ' registros adicionales en la tabla.');
        }
    }

    if (esAdmin) {
        textos.push('Eres administrador José. Puedes editar o eliminar cualquier registro usando los botones de la columna Acciones en el historial.');
    }

    textos.push('Fin de la lectura. Para escuchar de nuevo presiona el botón Leer Página. Para detener la voz presiona Detener.');

    textos.forEach(t => hablar(t, true));
}

function leerAyuda() {
    window.speechSynthesis.cancel();
    [
        'Ayuda del asistente de voz para personas con discapacidad visual.',
        'Botón Leer Página: lee en voz alta toda la información y el historial de salidas.',
        'Botón Ayuda: reproduce estas instrucciones.',
        'Botón Detener: para la voz inmediatamente.',
        'Al seleccionar un producto, escucharás el nombre y el stock disponible.',
        'Si la cantidad ingresada supera el stock, recibirás una alerta de voz.',
        'Usa la tecla Tab para moverte entre los campos del formulario.',
        'Usa la tecla Escape para cerrar diálogos.',
        'Este sistema es compatible con lectores de pantalla como NVDA y JAWS.',
        'Un enlace Saltar al contenido principal está disponible al inicio de la página. Presiónalo para ir directo al formulario.',
        'Fin de ayuda.'
    ].forEach(t => hablar(t, true));
}

/* Alertas automáticas al cargar la página */
window.addEventListener('load', () => {
    <?php if ($success): ?>
    setTimeout(() => hablar(<?php echo json_encode('Operación exitosa. ' . strip_tags($success)); ?>), 700);
    <?php elseif ($error): ?>
    setTimeout(() => hablar(<?php echo json_encode('Atención. Error: ' . strip_tags($error)); ?>), 700);
    <?php endif; ?>
});

/* Anunciar campos al recibir foco */
document.getElementById('selectProducto').addEventListener('focus', () =>
    hablar('Campo producto. Selecciona el producto a retirar del inventario.', true));
document.getElementById('inputArea').addEventListener('focus', () =>
    hablar('Campo área de destino. Escribe el departamento que recibirá el producto.', true));
document.getElementById('inputMotivo').addEventListener('focus', () =>
    hablar('Campo motivo. Opcionalmente describe la razón de la salida.', true));
</script>
</body>
</html>
