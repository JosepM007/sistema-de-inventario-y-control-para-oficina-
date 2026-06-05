<?php
session_start();
if (!isset($_SESSION['usuario'])) { header("Location: login.php"); exit; }
require 'db.php';
 
$usuario = $_SESSION['usuario'];
$rol     = $_SESSION['rol'];
 
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) { header("Location: devoluciones.php"); exit; }
$id = intval($_GET['id']);
 
$stmt = $conn->prepare("SELECT d.*, p.cantidad AS stock_actual FROM devoluciones d LEFT JOIN productos p ON d.producto_id = p.id WHERE d.id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$d = $stmt->get_result()->fetch_assoc();
$stmt->close();
 
if (!$d) { header("Location: devoluciones.php"); exit; }
 
// Historial
$hist_res = $conn->query("SELECT * FROM devoluciones_historial WHERE devolucion_id = $id ORDER BY fecha ASC");
$historial = [];
while ($r = $hist_res->fetch_assoc()) $historial[] = $r;
 
// Procesar acción de cambio de estado (solo admin)
$msg_ok = ''; $msg_err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $rol === 'admin') {
    $nuevo_estado = trim($_POST['nuevo_estado'] ?? '');
    $comentario   = trim($_POST['comentario']   ?? '');
    $estados_validos = ['revisado','aprobado','reingresado','finalizado','rechazado'];
 
    if (!in_array($nuevo_estado, $estados_validos)) {
        $msg_err = "Estado inválido.";
    } else {
        $estado_anterior = $d['estado'];
 
        // Campos de fecha y responsable según estado
        $extra_sql = "";
        $extra_vals = [];
        $extra_types = "";
 
        if ($nuevo_estado === 'revisado') {
            $extra_sql   = ", revisado_por = ?, fecha_revision = NOW()";
            $extra_vals  = [$usuario];
            $extra_types = "s";
        } elseif ($nuevo_estado === 'aprobado') {
            $extra_sql   = ", aprobado_por = ?, fecha_aprobacion = NOW()";
            $extra_vals  = [$usuario];
            $extra_types = "s";
        } elseif ($nuevo_estado === 'finalizado') {
            $extra_sql   = ", fecha_finalizacion = NOW()";
        }
 
        $upd = $conn->prepare("UPDATE devoluciones SET estado = ? $extra_sql WHERE id = ?");
        $params = array_merge([$nuevo_estado], $extra_vals, [$id]);
        $types  = "s" . $extra_types . "i";
        $upd->bind_param($types, ...$params);
 
        if ($upd->execute()) {
            $upd->close();
 
            // Si se aprueba y reingresa stock
            if ($nuevo_estado === 'aprobado' && $d['reingresa_stock'] && $estado_anterior !== 'aprobado') {
                // Solo reingresar si no se hizo antes (al crear)
                // Verificar si ya fue reingresado en creación: si reingresa_stock=1 y estado anterior era pendiente, ya se sumó
                // Para evitar doble suma, solo reingresar si el flag está activo y no se sumó en creación
                // (La lógica simplificada: si reingresa_stock=1 en nueva_devolucion ya sumó, así que aquí no sumamos de nuevo)
            }
 
            // Si estado es reingresado (bodega confirma físicamente)
            if ($nuevo_estado === 'reingresado' && !$d['reingresa_stock']) {
                $upd2 = $conn->prepare("UPDATE productos SET cantidad = cantidad + ? WHERE id = ?");
                $upd2->bind_param("ii", $d['cantidad'], $d['producto_id']);
                $upd2->execute(); $upd2->close();
                // Marcar que ya se reingresó
                $conn->query("UPDATE devoluciones SET reingresa_stock = 1 WHERE id = $id");
            }
 
            // Registrar en historial
            $h = $conn->prepare("INSERT INTO devoluciones_historial (devolucion_id, estado_anterior, estado_nuevo, usuario, comentario) VALUES (?,?,?,?,?)");
            $h->bind_param("issss", $id, $estado_anterior, $nuevo_estado, $usuario, $comentario);
            $h->execute(); $h->close();
 
            $msg_ok = "Estado actualizado a: " . ucfirst($nuevo_estado);
 
            // Recargar datos
            $stmt2 = $conn->prepare("SELECT d.*, p.cantidad AS stock_actual FROM devoluciones d LEFT JOIN productos p ON d.producto_id = p.id WHERE d.id = ? LIMIT 1");
            $stmt2->bind_param("i", $id);
            $stmt2->execute();
            $d = $stmt2->get_result()->fetch_assoc();
            $stmt2->close();
 
            $hist_res2 = $conn->query("SELECT * FROM devoluciones_historial WHERE devolucion_id = $id ORDER BY fecha ASC");
            $historial = [];
            while ($r = $hist_res2->fetch_assoc()) $historial[] = $r;
        } else {
            $msg_err = "Error al actualizar: " . $conn->error;
        }
    }
}
$conn->close();
 
function badge_estado($e) {
    $map = [
        'pendiente'   => ['🕐','#fcd34d','rgba(245,158,11,0.25)','rgba(245,158,11,0.40)'],
        'revisado'    => ['🔍','#7fecf8','rgba(0,200,232,0.25)','rgba(0,200,232,0.40)'],
        'aprobado'    => ['✅','#6ee7b7','rgba(16,185,129,0.25)','rgba(16,185,129,0.40)'],
        'reingresado' => ['📦','#c4b5fd','rgba(139,92,246,0.25)','rgba(139,92,246,0.40)'],
        'finalizado'  => ['🏁','#86efac','rgba(34,197,94,0.25)','rgba(34,197,94,0.40)'],
        'rechazado'   => ['❌','#fca5a5','rgba(239,68,68,0.25)','rgba(239,68,68,0.40)'],
    ];
    $x = $map[$e] ?? ['📋','#fff','rgba(255,255,255,0.12)','rgba(255,255,255,0.22)'];
    return "<span style='display:inline-flex;align-items:center;gap:6px;padding:5px 16px;border-radius:20px;font-size:13px;font-weight:800;color:{$x[1]};background:{$x[2]};border:1px solid {$x[3]}'>{$x[0]} " . ucfirst($e) . "</span>";
}
 
// Transiciones válidas según estado actual
$transiciones = [
    'pendiente'   => ['revisado','rechazado'],
    'revisado'    => ['aprobado','rechazado'],
    'aprobado'    => ['reingresado','finalizado'],
    'reingresado' => ['finalizado'],
    'finalizado'  => [],
    'rechazado'   => [],
];
$siguientes = $transiciones[$d['estado']] ?? [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Devolución <?php echo htmlspecialchars($d['codigo']); ?> - OfficeStock Pro</title>
<link rel="stylesheet" href="css/style.css">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
        :root{--card-bg:rgba(255,255,255,0.10);--card-border:rgba(255,255,255,0.16);--text-muted:rgba(255,255,255,0.55)}
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Nunito',sans-serif;background:linear-gradient(135deg,#0f4c8a 0%,#0a7abf 45%,#00b4d8 100%);min-height:100vh;color:#fff}
        .layout{display:flex;min-height:100vh}
        .sidebar{width:220px;flex-shrink:0;background:rgba(0,0,0,0.28);backdrop-filter:blur(16px);border-right:1px solid var(--card-border);display:flex;flex-direction:column;padding:28px 0 24px;position:sticky;top:0;height:100vh}
        .logo{font-size:19px;font-weight:800;color:#fff;padding:0 24px 24px;border-bottom:1px solid var(--card-border);margin-bottom:12px}
        .sidebar a{display:flex;align-items:center;gap:10px;padding:11px 24px;color:var(--text-muted);text-decoration:none;font-size:13.5px;font-weight:600;border-left:3px solid transparent;transition:all .18s}
        .sidebar a:hover{color:#fff;background:rgba(255,255,255,0.08)}
        .sidebar a.active{color:#fff;border-left-color:#00c8e8;background:rgba(0,200,232,0.15)}
        .sidebar .logout-link{margin-top:auto;color:#fca5a5;border-top:1px solid var(--card-border);padding-top:14px}
        .main-content{flex:1;display:flex;flex-direction:column;overflow-y:auto}
        .top-bar{padding:22px 30px 18px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid var(--card-border);background:rgba(0,0,0,0.15);backdrop-filter:blur(6px)}
        .top-bar h1{font-size:19px;font-weight:800;display:flex;align-items:center;gap:10px}
        .top-bar .sub{font-size:12px;color:var(--text-muted);margin-top:1px}
        .user-chip{display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,0.12);border:1px solid var(--card-border);border-radius:20px;padding:6px 14px;font-size:13px;font-weight:600}
        .dot{width:8px;height:8px;border-radius:50%;background:#00c8e8}
        .page-body{padding:26px 30px 40px;display:flex;flex-direction:column;gap:20px}
 
        /* Alerts */
        .alert{display:flex;align-items:center;gap:10px;padding:13px 16px;border-radius:11px;font-size:13.5px;font-weight:600;animation:slideIn .3s ease}
        @keyframes slideIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}
        .alert-ok{background:rgba(16,185,129,0.16);border:1px solid rgba(16,185,129,0.32);color:#6ee7b7}
        .alert-err{background:rgba(239,68,68,0.16);border:1px solid rgba(239,68,68,0.32);color:#fca5a5}
 
        /* Btn volver */
        .btn-back{display:inline-flex;align-items:center;gap:7px;background:rgba(255,255,255,0.10);border:1px solid rgba(255,255,255,0.18);color:rgba(255,255,255,0.80);border-radius:9px;padding:8px 16px;font-size:13px;font-weight:700;text-decoration:none;align-self:flex-start;transition:background .2s,color .2s}
        .btn-back:hover{background:rgba(255,255,255,0.18);color:#fff}
 
        /* Flujo de estados */
        .flujo{display:flex;align-items:center;gap:0;background:var(--card-bg);border:1px solid var(--card-border);border-radius:14px;padding:18px 24px;overflow-x:auto;flex-wrap:nowrap}
        .flujo-step{display:flex;align-items:center;gap:0;flex-shrink:0}
        .fs-circle{width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;border:2px solid transparent;transition:all .3s}
        .fs-label{font-size:11px;font-weight:700;margin-top:4px;text-align:center;white-space:nowrap}
        .fs-wrap{display:flex;flex-direction:column;align-items:center;gap:2px}
        .flujo-arrow{color:rgba(255,255,255,0.25);font-size:18px;padding:0 8px;flex-shrink:0}
        .fs-done{background:rgba(16,185,129,0.30);border-color:#10b981;color:#6ee7b7}
        .fs-done .fs-label{color:#6ee7b7}
        .fs-current{background:rgba(0,200,232,0.30);border-color:#00c8e8;color:#7fecf8;box-shadow:0 0 0 4px rgba(0,200,232,0.18)}
        .fs-current .fs-label{color:#7fecf8}
        .fs-pending{background:rgba(255,255,255,0.07);border-color:rgba(255,255,255,0.18);color:rgba(255,255,255,0.35)}
        .fs-pending .fs-label{color:rgba(255,255,255,0.35)}
        .fs-rejected{background:rgba(239,68,68,0.28);border-color:#ef4444;color:#fca5a5}
        .fs-rejected .fs-label{color:#fca5a5}
 
        /* Dos columnas */
        .two-col{display:grid;grid-template-columns:1fr 1fr;gap:20px}
 
        /* Cards */
        .card{background:var(--card-bg);border:1px solid var(--card-border);border-radius:16px;overflow:hidden}
        .card-head{padding:14px 20px;border-bottom:1px solid var(--card-border);background:rgba(255,255,255,0.04);font-size:14px;font-weight:700;display:flex;align-items:center;gap:8px}
        .card-body{padding:20px}
 
        /* Info list */
        .info-list{display:flex;flex-direction:column;gap:12px}
        .il-row{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;padding-bottom:10px;border-bottom:1px solid rgba(255,255,255,0.07)}
        .il-row:last-child{border-bottom:none;padding-bottom:0}
        .il-label{font-size:12px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;flex-shrink:0}
        .il-val{font-size:13.5px;font-weight:700;color:#fff;text-align:right;word-break:break-word}
        .il-val.muted{color:var(--text-muted);font-weight:600}
 
        /* Acciones de estado */
        .acciones-card{background:var(--card-bg);border:1px solid rgba(0,200,232,0.28);border-radius:16px;overflow:hidden}
        .acciones-head{padding:14px 20px;border-bottom:1px solid rgba(0,200,232,0.22);background:rgba(0,200,232,0.10);font-size:14px;font-weight:700;color:#7fecf8;display:flex;align-items:center;gap:8px}
        .acciones-body{padding:20px;display:flex;flex-direction:column;gap:14px}
        .field-ac label{display:block;font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.7px;margin-bottom:6px}
        .field-ac textarea{width:100%;background:rgba(255,255,255,0.10);border:1px solid rgba(255,255,255,0.18);border-radius:10px;padding:10px 14px;color:#fff;font-family:'Nunito',sans-serif;font-size:13.5px;outline:none;resize:vertical;min-height:70px;transition:border-color .2s}
        .field-ac textarea:focus{border-color:#00c8e8}
        .field-ac textarea::placeholder{color:rgba(255,255,255,0.30)}
        .btns-estado{display:flex;flex-wrap:wrap;gap:10px}
        .btn-estado{display:inline-flex;align-items:center;gap:6px;padding:10px 18px;border-radius:10px;border:none;font-family:'Nunito',sans-serif;font-size:13px;font-weight:800;cursor:pointer;transition:all .2s}
        .btn-revisar   {background:linear-gradient(135deg,rgba(0,200,232,0.40),rgba(0,180,216,0.60));color:#7fecf8;border:1px solid rgba(0,200,232,0.50)}
        .btn-aprobar   {background:linear-gradient(135deg,rgba(16,185,129,0.40),rgba(5,150,105,0.60));color:#6ee7b7;border:1px solid rgba(16,185,129,0.50)}
        .btn-reingresar{background:linear-gradient(135deg,rgba(139,92,246,0.40),rgba(124,58,237,0.60));color:#c4b5fd;border:1px solid rgba(139,92,246,0.50)}
        .btn-finalizar {background:linear-gradient(135deg,rgba(34,197,94,0.40),rgba(21,128,61,0.60));color:#86efac;border:1px solid rgba(34,197,94,0.50)}
        .btn-rechazar  {background:linear-gradient(135deg,rgba(239,68,68,0.40),rgba(185,28,28,0.60));color:#fca5a5;border:1px solid rgba(239,68,68,0.50)}
        .btn-estado:hover{transform:translateY(-2px);filter:brightness(1.15)}
        .no-acciones{color:var(--text-muted);font-size:13px;padding:10px 0;text-align:center}
 
        /* Timeline historial */
        .timeline{display:flex;flex-direction:column;gap:0}
        .tl-item{display:flex;gap:14px;position:relative}
        .tl-line{display:flex;flex-direction:column;align-items:center;flex-shrink:0}
        .tl-dot{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0;border:2px solid rgba(255,255,255,0.18);background:rgba(255,255,255,0.08)}
        .tl-connector{width:2px;flex:1;background:rgba(255,255,255,0.10);min-height:20px;margin:4px 0}
        .tl-item:last-child .tl-connector{display:none}
        .tl-content{padding-bottom:20px;flex:1}
        .tl-header{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
        .tl-estado{font-size:12px;font-weight:800;color:#7fecf8}
        .tl-user{font-size:12px;color:#fcd34d;font-weight:600}
        .tl-fecha{font-size:11.5px;color:var(--text-muted)}
        .tl-comment{font-size:13px;color:rgba(255,255,255,0.70);margin-top:6px;line-height:1.5;background:rgba(255,255,255,0.05);border-radius:8px;padding:8px 12px}
        .empty-hist{text-align:center;color:var(--text-muted);padding:20px;font-size:13px}
 
        @media(max-width:900px){.two-col{grid-template-columns:1fr}}
        @media(max-width:768px){.sidebar{display:none}.page-body{padding:16px}}
</style>
</head>
<body>
<div class="layout">
<div class="sidebar">
<div class="logo">🗂️ OfficeStock</div>
<a href="dashboard.php">🏠 Dashboard</a>
<?php if ($rol=='admin'): ?>
<a href="productos.php">📦 Productos</a>
<a href="usuarios.php">👥 Usuarios</a>
<?php endif; ?>
<a href="categorias.php">📂 Categorías</a>
<a href="proveedores.php">🏢 Proveedores</a>
<a href="nuevo_inventario.php">📋 Inventario</a>
<a href="salidas.php">📤 Salidas</a>
<a href="devoluciones.php" class="active">↩️ Devoluciones</a>
<?php if ($rol=='admin'): ?>
<a href="auditoria.php">🔍 Auditoría</a>
<?php endif; ?>
<a href="logout.php" class="logout-link">🚪 Cerrar Sesión</a>
</div>
 
    <div class="main-content">
<div class="top-bar">
<div>
<h1>↩️ <?php echo htmlspecialchars($d['codigo']); ?> &nbsp;<?php echo badge_estado($d['estado']); ?></h1>
<div class="sub">Inicio / Devoluciones / <?php echo htmlspecialchars($d['codigo']); ?></div>
</div>
<div class="user-chip"><span class="dot"></span><?php echo htmlspecialchars($usuario); ?> · <?php echo ucfirst($rol); ?></div>
</div>
 
        <div class="page-body">
 
            <?php if ($msg_ok): ?><div class="alert alert-ok">✅ <?php echo htmlspecialchars($msg_ok); ?></div><?php endif; ?>
<?php if ($msg_err): ?><div class="alert alert-err">⚠️ <?php echo htmlspecialchars($msg_err); ?></div><?php endif; ?>
 
            <a class="btn-back" href="devoluciones.php">← Volver a Devoluciones</a>
 
            <!-- Flujo visual de estados -->
<?php
            $estados_flujo = [
                ['pendiente',   '🕐', 'Pendiente'],
                ['revisado',    '🔍', 'Revisado'],
                ['aprobado',    '✅', 'Aprobado'],
                ['reingresado', '📦', 'Reingresado'],
                ['finalizado',  '🏁', 'Finalizado'],
            ];
            $orden = ['pendiente'=>0,'revisado'=>1,'aprobado'=>2,'reingresado'=>3,'finalizado'=>4,'rechazado'=>99];
            $actual_orden = $orden[$d['estado']] ?? 0;
            ?>
<div class="flujo">
<?php foreach ($estados_flujo as $i => [$est, $ico, $nom]): ?>
<?php
                    $est_orden = $orden[$est] ?? 0;
                    if ($d['estado'] === 'rechazado') $class = 'fs-rejected';
                    elseif ($est_orden < $actual_orden) $class = 'fs-done';
                    elseif ($est_orden === $actual_orden) $class = 'fs-current';
                    else $class = 'fs-pending';
                    ?>
<div class="flujo-step">
<div class="fs-wrap">
<div class="fs-circle <?php echo $class; ?>"><?php echo $ico; ?></div>
<div class="fs-label <?php echo $class; ?>"><?php echo $nom; ?></div>
</div>
<?php if ($i < count($estados_flujo)-1): ?>
<span class="flujo-arrow">→</span>
<?php endif; ?>
</div>
<?php endforeach; ?>
<?php if ($d['estado'] === 'rechazado'): ?>
<span class="flujo-arrow">→</span>
<div class="fs-wrap">
<div class="fs-circle fs-rejected">❌</div>
<div class="fs-label fs-rejected">Rechazado</div>
</div>
<?php endif; ?>
</div>
 
            <!-- Dos columnas: detalles + acciones -->
<div class="two-col">
 
                <!-- Detalle de la devolución -->
<div class="card">
<div class="card-head">📋 Detalle de la Devolución</div>
<div class="card-body">
<div class="info-list">
<div class="il-row"><span class="il-label">Código</span><span class="il-val" style="font-family:'Courier New',monospace;color:#7fecf8"><?php echo htmlspecialchars($d['codigo']); ?></span></div>
<div class="il-row"><span class="il-label">Producto</span><span class="il-val"><?php echo htmlspecialchars($d['producto_nombre']); ?></span></div>
<div class="il-row"><span class="il-label">Cantidad devuelta</span><span class="il-val"><?php echo intval($d['cantidad']); ?> unidades</span></div>
<div class="il-row"><span class="il-label">Stock actual</span><span class="il-val <?php echo intval($d['stock_actual'])<5?'':''; ?>" style="color:<?php echo intval($d['stock_actual'])<5?'#fca5a5':'#6ee7b7'; ?>"><?php echo intval($d['stock_actual']); ?> uds en inventario</span></div>
<div class="il-row"><span class="il-label">Tipo</span><span class="il-val"><?php
                            $tipos_nom = ['dañado'=>'💔 Dañado','incorrecto'=>'❓ Incorrecto','vencido'=>'⏰ Vencido','error_despacho'=>'🚚 Error despacho','sobrante'=>'➕ Sobrante','interno'=>'🔄 Interno','cliente'=>'👤 De cliente'];
                            echo $tipos_nom[$d['tipo_devolucion']] ?? ucfirst($d['tipo_devolucion']);
                            ?></span></div>
<div class="il-row"><span class="il-label">Estado actual</span><span class="il-val"><?php echo badge_estado($d['estado']); ?></span></div>
<div class="il-row"><span class="il-label">Área origen</span><span class="il-val <?php echo empty($d['area_origen'])?'muted':''; ?>"><?php echo $d['area_origen'] ? htmlspecialchars($d['area_origen']) : '—'; ?></span></div>
<div class="il-row"><span class="il-label">Reingresa stock</span><span class="il-val" style="color:<?php echo $d['reingresa_stock']?'#6ee7b7':'#fca5a5'; ?>"><?php echo $d['reingresa_stock'] ? '✅ Sí' : '❌ No'; ?></span></div>
</div>
</div>
</div>
 
                <!-- Responsables y fechas -->
<div style="display:flex;flex-direction:column;gap:16px">
<div class="card">
<div class="card-head">👥 Responsables</div>
<div class="card-body">
<div class="info-list">
<div class="il-row"><span class="il-label">Solicitado por</span><span class="il-val" style="color:#fcd34d">👤 <?php echo htmlspecialchars($d['solicitado_por']); ?></span></div>
<div class="il-row"><span class="il-label">Revisado por</span><span class="il-val <?php echo empty($d['revisado_por'])?'muted':''; ?>"><?php echo $d['revisado_por'] ? '🔍 ' . htmlspecialchars($d['revisado_por']) : '—'; ?></span></div>
<div class="il-row"><span class="il-label">Aprobado por</span><span class="il-val <?php echo empty($d['aprobado_por'])?'muted':''; ?>"><?php echo $d['aprobado_por'] ? '✅ ' . htmlspecialchars($d['aprobado_por']) : '—'; ?></span></div>
</div>
</div>
</div>
<div class="card">
<div class="card-head">📅 Fechas del Proceso</div>
<div class="card-body">
<div class="info-list">
<div class="il-row"><span class="il-label">Fecha solicitud</span><span class="il-val"><?php echo date('d/m/Y H:i', strtotime($d['fecha_solicitud'])); ?></span></div>
<div class="il-row"><span class="il-label">Fecha revisión</span><span class="il-val <?php echo empty($d['fecha_revision'])?'muted':''; ?>"><?php echo $d['fecha_revision'] ? date('d/m/Y H:i', strtotime($d['fecha_revision'])) : '—'; ?></span></div>
<div class="il-row"><span class="il-label">Fecha aprobación</span><span class="il-val <?php echo empty($d['fecha_aprobacion'])?'muted':''; ?>"><?php echo $d['fecha_aprobacion'] ? date('d/m/Y H:i', strtotime($d['fecha_aprobacion'])) : '—'; ?></span></div>
<div class="il-row"><span class="il-label">Fecha finalización</span><span class="il-val <?php echo empty($d['fecha_finalizacion'])?'muted':''; ?>"><?php echo $d['fecha_finalizacion'] ? date('d/m/Y H:i', strtotime($d['fecha_finalizacion'])) : '—'; ?></span></div>
</div>
</div>
</div>
</div>
</div>
 
            <!-- Motivo y observaciones -->
<div class="two-col">
<div class="card">
<div class="card-head">📝 Motivo</div>
<div class="card-body" style="color:rgba(255,255,255,0.80);font-size:14px;line-height:1.6"><?php echo nl2br(htmlspecialchars($d['motivo'])); ?></div>
</div>
<div class="card">
<div class="card-head">🗒️ Observaciones</div>
<div class="card-body" style="color:rgba(255,255,255,0.65);font-size:13.5px;line-height:1.6"><?php echo $d['observaciones'] ? nl2br(htmlspecialchars($d['observaciones'])) : '<span style="color:var(--text-muted)">Sin observaciones registradas.</span>'; ?></div>
</div>
</div>
 
            <!-- Acciones (solo admin y si hay transiciones disponibles) -->
<?php if ($rol === 'admin' && !empty($siguientes)): ?>
<div class="acciones-card">
<div class="acciones-head">⚙️ Cambiar Estado — Panel de Control</div>
<div class="acciones-body">
<form method="POST" action="">
<div class="field-ac" style="margin-bottom:14px">
<label>Comentario del cambio</label>
<textarea name="comentario" placeholder="Describe el motivo del cambio de estado, observaciones o instrucciones..."></textarea>
</div>
<div class="btns-estado">
<?php
                            $btn_config = [
                                'revisado'    => ['btn-revisar',   '🔍 Marcar como Revisado'],
                                'aprobado'    => ['btn-aprobar',   '✅ Aprobar Devolución'],
                                'reingresado' => ['btn-reingresar','📦 Confirmar Reingreso'],
                                'finalizado'  => ['btn-finalizar', '🏁 Finalizar Proceso'],
                                'rechazado'   => ['btn-rechazar',  '❌ Rechazar Devolución'],
                            ];
                            foreach ($siguientes as $sig):
                                $bc = $btn_config[$sig] ?? ['btn-estado', ucfirst($sig)];
                            ?>
<button type="submit" name="nuevo_estado" value="<?php echo $sig; ?>" class="btn-estado <?php echo $bc[0]; ?>"
                                onclick="return confirm('¿Confirmas cambiar el estado a: <?php echo ucfirst($sig); ?>?')">
<?php echo $bc[1]; ?>
</button>
<?php endforeach; ?>
</div>
</form>
</div>
</div>
<?php elseif ($rol === 'admin' && empty($siguientes)): ?>
<div class="acciones-card">
<div class="acciones-head">⚙️ Estado del Proceso</div>
<div class="acciones-body"><p class="no-acciones">Este proceso está <?php echo $d['estado'] === 'finalizado' ? '🏁 finalizado' : '❌ rechazado'; ?>. No hay más acciones disponibles.</p></div>
</div>
<?php endif; ?>
 
            <!-- Historial / Timeline -->
<div class="card">
<div class="card-head">🕒 Historial de Movimientos</div>
<div class="card-body">
<?php if (empty($historial)): ?>
<div class="empty-hist">📭 Sin historial aún.</div>
<?php else: ?>
<div class="timeline">
<?php foreach ($historial as $h): ?>
<div class="tl-item">
<div class="tl-line">
<div class="tl-dot">
<?php
                                    $dot_ico = ['pendiente'=>'🕐','revisado'=>'🔍','aprobado'=>'✅','reingresado'=>'📦','finalizado'=>'🏁','rechazado'=>'❌'];
                                    echo $dot_ico[$h['estado_nuevo']] ?? '📋';
                                    ?>
</div>
<div class="tl-connector"></div>
</div>
<div class="tl-content">
<div class="tl-header">
<span class="tl-estado">
<?php if ($h['estado_anterior']): ?>
<?php echo ucfirst($h['estado_anterior']); ?> → <?php echo ucfirst($h['estado_nuevo']); ?>
<?php else: ?>
                                            Registrado como <?php echo ucfirst($h['estado_nuevo']); ?>
<?php endif; ?>
</span>
<span class="tl-user">👤 <?php echo htmlspecialchars($h['usuario']); ?></span>
<span class="tl-fecha"><?php echo date('d/m/Y H:i', strtotime($h['fecha'])); ?></span>
</div>
<?php if ($h['comentario']): ?>
<div class="tl-comment"><?php echo nl2br(htmlspecialchars($h['comentario'])); ?></div>
<?php endif; ?>
</div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>
</div>
 
        </div>
</div>
</div>
</body>
</html>