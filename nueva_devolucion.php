<?php
session_start();
if (!isset($_SESSION['usuario'])) { header("Location: login.php"); exit; }
require 'db.php';
 
$usuario = $_SESSION['usuario'];
$rol     = $_SESSION['rol'];
 
// Traer productos para el select
$productos_res = $conn->query("SELECT id, nombre, cantidad, proveedores FROM productos ORDER BY nombre ASC");
$productos = [];
while ($r = $productos_res->fetch_assoc()) $productos[] = $r;
 
$error = '';
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $producto_id      = intval($_POST['producto_id']);
    $cantidad         = intval($_POST['cantidad']);
    $tipo_devolucion  = trim($_POST['tipo_devolucion']);
    $motivo           = trim($_POST['motivo']);
    $area_origen      = trim($_POST['area_origen']);
    $observaciones    = trim($_POST['observaciones']);
    $reingresa_stock  = isset($_POST['reingresa_stock']) ? 1 : 0;
 
    $tipos_validos = ['dañado','incorrecto','vencido','error_despacho','sobrante','interno','cliente'];
 
    if (!$producto_id || $cantidad <= 0 || !in_array($tipo_devolucion, $tipos_validos) || empty($motivo)) {
        $error = "Por favor completa todos los campos obligatorios correctamente.";
    } else {
        // Obtener nombre del producto
        $stmt = $conn->prepare("SELECT nombre, cantidad FROM productos WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $producto_id);
        $stmt->execute();
        $prod = $stmt->get_result()->fetch_assoc();
        $stmt->close();
 
        if (!$prod) {
            $error = "Producto no encontrado.";
        } else {
            // Generar código único: DEV-YYYYMMDD-XXXX
            $codigo = 'DEV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
 
            $stmt = $conn->prepare("INSERT INTO devoluciones (codigo, producto_id, producto_nombre, cantidad, tipo_devolucion, motivo, area_origen, solicitado_por, observaciones, reingresa_stock, estado) VALUES (?,?,?,?,?,?,?,?,?,?,'pendiente')");
            $stmt->bind_param("sssississi",
                $codigo, $producto_id, $prod['nombre'],
                $cantidad, $tipo_devolucion, $motivo,
                $area_origen, $usuario, $observaciones, $reingresa_stock
            );
 
            if ($stmt->execute()) {
                $dev_id = $conn->insert_id;
                $stmt->close();
 
                // Registrar historial inicial
                $h = $conn->prepare("INSERT INTO devoluciones_historial (devolucion_id, estado_anterior, estado_nuevo, usuario, comentario) VALUES (?,NULL,'pendiente',?,?)");
                $comentario = "Devolución registrada. Tipo: " . ucfirst(str_replace('_',' ',$tipo_devolucion)) . ". Motivo: $motivo";
                $h->bind_param("iss", $dev_id, $usuario, $comentario);
                $h->execute(); $h->close();
 
                // Si reingresa stock, sumar de inmediato
                if ($reingresa_stock) {
                    $upd = $conn->prepare("UPDATE productos SET cantidad = cantidad + ? WHERE id = ?");
                    $upd->bind_param("ii", $cantidad, $producto_id);
                    $upd->execute(); $upd->close();
                }
 
                $conn->close();
                $_SESSION['success'] = "Devolución $codigo registrada exitosamente.";
                header("Location: devoluciones.php");
                exit;
            } else {
                $error = "Error al guardar la devolución: " . $conn->error;
                $stmt->close();
            }
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Nueva Devolución - OfficeStock Pro</title>
<link rel="stylesheet" href="css/style.css">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Nunito',sans-serif;min-height:100vh;background:linear-gradient(135deg,#0f4c8a 0%,#0a7abf 45%,#00b4d8 100%);display:flex;align-items:center;justify-content:center;padding:40px 16px}
 
        .wrapper{width:100%;max-width:780px}
        .topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}
        .top-logo{font-size:18px;font-weight:800;color:#fff}
        .btn-back{display:inline-flex;align-items:center;gap:6px;color:rgba(255,255,255,0.70);text-decoration:none;font-size:13px;font-weight:600;transition:color .2s}
        .btn-back:hover{color:#fff}
 
        .form-card{background:rgba(255,255,255,0.10);border:1px solid rgba(255,255,255,0.18);border-radius:20px;backdrop-filter:blur(16px);overflow:hidden;box-shadow:0 24px 64px rgba(0,0,0,0.28)}
 
        .form-header{background:linear-gradient(135deg,rgba(0,119,182,0.65),rgba(0,180,216,0.45));border-bottom:1px solid rgba(255,255,255,0.14);padding:26px 32px 22px;display:flex;align-items:center;gap:16px}
        .header-icon{width:52px;height:52px;border-radius:14px;background:linear-gradient(135deg,#0077b6,#00c8e8);display:flex;align-items:center;justify-content:center;font-size:26px;box-shadow:0 6px 20px rgba(0,0,0,0.22);flex-shrink:0}
        .form-header h2{font-size:20px;font-weight:800;color:#fff}
        .form-header p{font-size:12.5px;color:rgba(255,255,255,0.60);margin-top:2px}
 
        .form-body{padding:30px 32px 28px}
 
        .alert-err{display:flex;align-items:center;gap:10px;background:rgba(239,68,68,0.18);border:1px solid rgba(239,68,68,0.35);color:#fca5a5;padding:12px 16px;border-radius:10px;font-size:13.5px;font-weight:600;margin-bottom:24px}
 
        /* Sección */
        .section-title{font-size:12px;font-weight:800;color:#7fecf8;text-transform:uppercase;letter-spacing:1px;margin-bottom:16px;display:flex;align-items:center;gap:8px}
        .section-title::after{content:'';flex:1;height:1px;background:rgba(0,200,232,0.25)}
 
        .fields-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px 20px;margin-bottom:22px}
        .field-full{grid-column:1/-1}
        .field-group{display:flex;flex-direction:column;gap:7px}
        .field-group label{font-size:12px;font-weight:700;color:rgba(255,255,255,0.72);text-transform:uppercase;letter-spacing:.7px}
        .field-group label .req{color:#7fecf8;margin-left:2px}
        .field-group input,.field-group select,.field-group textarea{width:100%;background:rgba(255,255,255,0.10);border:1px solid rgba(255,255,255,0.18);border-radius:10px;padding:11px 14px;color:#fff;font-family:'Nunito',sans-serif;font-size:14px;outline:none;transition:border-color .2s,background .2s,box-shadow .2s}
        .field-group input::placeholder,.field-group textarea::placeholder{color:rgba(255,255,255,0.30)}
        .field-group input:focus,.field-group select:focus,.field-group textarea:focus{border-color:#00c8e8;background:rgba(255,255,255,0.15);box-shadow:0 0 0 3px rgba(0,200,232,0.18)}
        .field-group select option{background:#0a4a72}
        .field-group textarea{resize:vertical;min-height:90px}
 
        /* Stock preview */
        .stock-bar{display:none;background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.14);border-radius:10px;padding:12px 16px;margin-top:8px;gap:20px;flex-wrap:wrap}
        .stock-bar.show{display:flex}
        .sb-item{display:flex;flex-direction:column;gap:2px}
        .sb-label{font-size:11px;color:rgba(255,255,255,0.50);font-weight:700;text-transform:uppercase;letter-spacing:.5px}
        .sb-val{font-size:15px;font-weight:800;color:#fff}
        .sb-val.low{color:#fca5a5} .sb-val.ok{color:#6ee7b7}
 
        /* Tipos visuales */
        .tipos-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px;margin-bottom:22px}
        .tipo-opt{position:relative}
        .tipo-opt input[type="radio"]{position:absolute;opacity:0;width:0}
        .tipo-opt label{display:flex;flex-direction:column;align-items:center;gap:6px;padding:14px 10px;border-radius:12px;border:2px solid rgba(255,255,255,0.14);background:rgba(255,255,255,0.06);cursor:pointer;transition:all .2s;text-align:center}
        .tipo-opt label .ti{font-size:26px}
        .tipo-opt label .tn{font-size:12px;font-weight:700;color:#fff}
        .tipo-opt label .td{font-size:10.5px;color:rgba(255,255,255,0.48);margin-top:1px}
        .tipo-opt input:checked + label{border-color:#00c8e8;background:rgba(0,200,232,0.20);box-shadow:0 0 0 3px rgba(0,200,232,0.18)}
        .tipo-opt label:hover{border-color:rgba(0,200,232,0.45);background:rgba(255,255,255,0.10)}
 
        /* Checkbox reingreso */
        .reingreso-box{display:flex;align-items:flex-start;gap:12px;background:rgba(16,185,129,0.10);border:1px solid rgba(16,185,129,0.25);border-radius:12px;padding:16px 18px}
        .reingreso-box input[type="checkbox"]{width:18px;height:18px;accent-color:#10b981;flex-shrink:0;margin-top:2px;cursor:pointer}
        .reingreso-box .rb-text{font-size:13.5px;font-weight:600;color:#fff}
        .reingreso-box .rb-sub{font-size:12px;color:rgba(255,255,255,0.55);margin-top:3px}
 
        .divider{border:none;border-top:1px solid rgba(255,255,255,0.10);margin:24px 0}
        .form-actions{display:flex;gap:12px;justify-content:flex-end;align-items:center}
        .btn-guardar{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#0077b6,#00c8e8);color:#fff;border:none;border-radius:10px;padding:12px 30px;font-family:'Nunito',sans-serif;font-size:14px;font-weight:800;cursor:pointer;box-shadow:0 4px 16px rgba(0,119,182,0.38);transition:opacity .2s,transform .15s}
        .btn-guardar:hover{opacity:.88;transform:translateY(-1px)}
        .btn-cancelar{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,0.08);color:rgba(255,255,255,0.72);border:1px solid rgba(255,255,255,0.16);border-radius:10px;padding:12px 22px;font-family:'Nunito',sans-serif;font-size:14px;font-weight:600;cursor:pointer;text-decoration:none;transition:background .2s,color .2s}
        .btn-cancelar:hover{background:rgba(255,255,255,0.15);color:#fff}
 
        @media(max-width:600px){.fields-grid{grid-template-columns:1fr}.field-full{grid-column:1}.form-header,.form-body{padding-left:20px;padding-right:20px}.form-actions{flex-direction:column-reverse}.btn-guardar,.btn-cancelar{width:100%;justify-content:center}}
</style>
</head>
<body>
<div class="wrapper">
<div class="topbar">
<div class="top-logo">🗂️ OfficeStock Pro</div>
<a class="btn-back" href="devoluciones.php">← Volver a Devoluciones</a>
</div>
 
    <div class="form-card">
<div class="form-header">
<div class="header-icon">↩️</div>
<div>
<h2>Registrar Nueva Devolución</h2>
<p>Completa todos los campos para iniciar el proceso de devolución.</p>
</div>
</div>
 
        <div class="form-body">
<?php if ($error): ?><div class="alert-err">⚠️ <?php echo htmlspecialchars($error); ?></div><?php endif; ?>
 
            <form method="POST" action="" id="formDev">
 
                <!-- 1. Producto -->
<div class="section-title">📦 Producto a Devolver</div>
<div class="fields-grid">
<div class="field-group field-full">
<label>Producto <span class="req">*</span></label>
<select name="producto_id" id="selProducto" required onchange="mostrarStock()">
<option value="">— Selecciona un producto —</option>
<?php foreach ($productos as $p): ?>
<option value="<?php echo $p['id']; ?>"
                                    data-stock="<?php echo $p['cantidad']; ?>"
                                    data-prov="<?php echo htmlspecialchars($p['proveedores']); ?>"
                                    data-nombre="<?php echo htmlspecialchars($p['nombre']); ?>">
<?php echo htmlspecialchars($p['nombre']); ?> — Stock: <?php echo $p['cantidad']; ?> uds
</option>
<?php endforeach; ?>
</select>
<div class="stock-bar" id="stockBar">
<div class="sb-item"><div class="sb-label">Producto</div><div class="sb-val" id="sbNombre">—</div></div>
<div class="sb-item"><div class="sb-label">Proveedor</div><div class="sb-val" id="sbProv">—</div></div>
<div class="sb-item"><div class="sb-label">Stock actual</div><div class="sb-val" id="sbStock">—</div></div>
</div>
</div>
<div class="field-group">
<label>Cantidad a devolver <span class="req">*</span></label>
<input type="number" name="cantidad" id="inpCant" min="1" placeholder="Ej: 3" required>
</div>
<div class="field-group">
<label>Área de origen</label>
<input type="text" name="area_origen" placeholder="Ej: Bodega, Ventas, RRHH...">
</div>
</div>
 
                <!-- 2. Tipo -->
<div class="section-title">🏷️ Tipo de Devolución</div>
<div class="tipos-grid">
<?php
                    $tipos = [
                        ['dañado',        '💔','Dañado',         'Producto en mal estado físico'],
                        ['incorrecto',    '❓','Incorrecto',      'No es el producto solicitado'],
                        ['vencido',       '⏰','Vencido',         'Fecha de vencimiento expirada'],
                        ['error_despacho','🚚','Error despacho',  'Enviado a área equivocada'],
                        ['sobrante',      '➕','Sobrante',        'Excedente de lo requerido'],
                        ['interno',       '🔄','Interno',         'Transferencia entre áreas'],
                        ['cliente',       '👤','De cliente',      'Retorno desde usuario final'],
                    ];
                    foreach ($tipos as [$val, $ico, $nom, $desc]):
                        $checked = (isset($_POST['tipo_devolucion']) && $_POST['tipo_devolucion']==$val) ? 'checked' : '';
                    ?>
<div class="tipo-opt">
<input type="radio" name="tipo_devolucion" id="t_<?php echo $val; ?>" value="<?php echo $val; ?>" <?php echo $checked; ?> required>
<label for="t_<?php echo $val; ?>">
<span class="ti"><?php echo $ico; ?></span>
<span class="tn"><?php echo $nom; ?></span>
<span class="td"><?php echo $desc; ?></span>
</label>
</div>
<?php endforeach; ?>
</div>
 
                <!-- 3. Detalle -->
<div class="section-title">📝 Detalle y Observaciones</div>
<div class="fields-grid">
<div class="field-group field-full">
<label>Motivo de la devolución <span class="req">*</span></label>
<textarea name="motivo" placeholder="Describe con detalle el motivo de esta devolución..." required><?php echo isset($_POST['motivo']) ? htmlspecialchars($_POST['motivo']) : ''; ?></textarea>
</div>
<div class="field-group field-full">
<label>Observaciones adicionales</label>
<textarea name="observaciones" placeholder="Notas internas, condición del producto, referencias adicionales..."><?php echo isset($_POST['observaciones']) ? htmlspecialchars($_POST['observaciones']) : ''; ?></textarea>
</div>
</div>
 
</div>
 
                <hr class="divider">
 
                <div class="form-actions">
<a href="devoluciones.php" class="btn-cancelar">✕ Cancelar</a>
<button type="submit" class="btn-guardar">↩️ Registrar Devolución</button>
</div>
 
            </form>
</div>
</div>
</div>
 
<script>
const prodData = {
<?php foreach ($productos as $p): ?>
<?php echo $p['id']; ?>: { nombre: "<?php echo addslashes($p['nombre']); ?>", stock: <?php echo intval($p['cantidad']); ?>, prov: "<?php echo addslashes($p['proveedores']); ?>" },
<?php endforeach; ?>
};
 
function mostrarStock() {
    const id = parseInt(document.getElementById('selProducto').value);
    const bar = document.getElementById('stockBar');
    if (!id || !prodData[id]) { bar.classList.remove('show'); return; }
    const p = prodData[id];
    bar.classList.add('show');
    document.getElementById('sbNombre').textContent = p.nombre;
    document.getElementById('sbProv').textContent   = p.prov;
    const stockEl = document.getElementById('sbStock');
    stockEl.textContent = p.stock + ' unidades';
    stockEl.className   = 'sb-val ' + (p.stock < 5 ? 'low' : 'ok');
    document.getElementById('inpCant').max = 9999; // devoluciones no limitan al stock actual
}
 
document.getElementById('formDev').addEventListener('submit', function(e) {
    const tipo = document.querySelector('input[name="tipo_devolucion"]:checked');
    if (!tipo) { e.preventDefault(); alert('Por favor selecciona el tipo de devolución.'); return; }
    const cant = parseInt(document.getElementById('inpCant').value);
    if (cant < 1) { e.preventDefault(); alert('La cantidad debe ser al menos 1.'); return; }
});
</script>
</body>
</html>