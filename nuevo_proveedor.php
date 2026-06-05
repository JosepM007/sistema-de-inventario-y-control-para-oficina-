<?php
session_start();
if (!isset($_SESSION['usuario'])) { header("Location: login.php"); exit; }
require 'db.php';
 
$mensaje  = '';
$tipo_msg = '';
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 
    $nombre      = trim($_POST['nombre']      ?? '');
    $categoria   = trim($_POST['categoria']   ?? '');
    $contacto    = trim($_POST['contacto']    ?? '');
    $telefono    = trim($_POST['telefono']    ?? '');
    $correo      = trim($_POST['correo']      ?? '');
    $direccion   = trim($_POST['direccion']   ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $sitio_web   = trim($_POST['sitio_web']   ?? '');
    $pais        = trim($_POST['pais']        ?? '');
 
    if ($nombre === '' || $categoria === '' || $contacto === '' || $correo === '') {
        $mensaje  = '⚠️ Los campos Nombre, Categoría, Contacto y Correo son obligatorios.';
        $tipo_msg = 'error';
    } else {
        $stmt = $conn->prepare("INSERT INTO proveedores (nombre, categoria, contacto, telefono, correo, direccion, descripcion, sitio_web, pais, fecha_registro) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
 
        if (!$stmt) {
            $mensaje  = '❌ Error al preparar la consulta: ' . $conn->error;
            $tipo_msg = 'error';
        } else {
            $stmt->bind_param("sssssssss",
                $nombre, $categoria, $contacto, $telefono,
                $correo, $direccion, $descripcion, $sitio_web, $pais
            );
 
            if ($stmt->execute()) {
                $mensaje  = '✅ Proveedor <strong>' . htmlspecialchars($nombre) . '</strong> registrado correctamente.';
                $tipo_msg = 'ok';
                /* Limpiar campos tras éxito */
                $nombre = $categoria = $contacto = $telefono = '';
                $correo = $direccion = $descripcion = $sitio_web = $pais = '';
            } else {
                $mensaje  = '❌ Error al guardar: ' . $stmt->error;
                $tipo_msg = 'error';
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Proveedor - OfficeStock Pro</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --card-bg:     rgba(255,255,255,0.10);
            --card-border: rgba(255,255,255,0.16);
            --text-muted:  rgba(255,255,255,0.55);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Nunito', sans-serif;
            background: linear-gradient(135deg, #0f4c8a 0%, #0a7abf 45%, #00b4d8 100%);
            min-height: 100vh;
            color: #fff;
        }
        .layout { display: flex; min-height: 100vh; }
 
        /* ── Sidebar ── */
        .sidebar { width: 220px; flex-shrink: 0; background: rgba(0,0,0,0.28); backdrop-filter: blur(16px); border-right: 1px solid var(--card-border); display: flex; flex-direction: column; padding: 28px 0 24px; position: sticky; top: 0; height: 100vh; }
        .logo { font-size: 19px; font-weight: 800; color: #fff; padding: 0 24px 24px; border-bottom: 1px solid var(--card-border); margin-bottom: 12px; }
        .sidebar a { display: flex; align-items: center; gap: 10px; padding: 11px 24px; color: var(--text-muted); text-decoration: none; font-size: 13.5px; font-weight: 600; border-left: 3px solid transparent; transition: all 0.18s; }
        .sidebar a:hover  { color: #fff; background: rgba(255,255,255,0.08); }
        .sidebar a.active { color: #fff; border-left-color: #00c8e8; background: rgba(0,200,232,0.15); }
        .sidebar .logout-link { margin-top: auto; color: #fca5a5; border-top: 1px solid var(--card-border); padding-top: 14px; }
 
        /* ── Main ── */
        .main-content { flex: 1; display: flex; flex-direction: column; }
        .top-bar { padding: 22px 30px 18px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--card-border); background: rgba(0,0,0,0.15); backdrop-filter: blur(6px); }
        .top-bar h1 { font-size: 20px; font-weight: 800; }
        .top-bar .sub { font-size: 12px; color: var(--text-muted); margin-top: 1px; }
        .user-chip { display: inline-flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.12); border: 1px solid var(--card-border); border-radius: 20px; padding: 6px 14px; font-size: 13px; font-weight: 600; }
        .dot { width:8px; height:8px; border-radius:50%; background:#00c8e8; }
        .page-body { padding: 28px 30px 50px; }
 
        /* ── Section label ── */
        .section-label { display: flex; align-items: center; gap: 10px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.4px; color: #7fecf8; margin-bottom: 20px; margin-top: 30px; }
        .section-label:first-child { margin-top: 0; }
        .section-label::after { content: ''; flex: 1; height: 1px; background: rgba(0,200,232,0.22); }
 
        /* ── Form card ── */
        .form-card { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: 20px; padding: 30px 32px 36px; max-width: 780px; }
 
        /* ── Grid ── */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px 24px; }
        .form-grid .full { grid-column: 1 / -1; }
 
        /* ── Campos ── */
        .field { display: flex; flex-direction: column; gap: 6px; }
        .field label { font-size: 11.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #7fecf8; }
        .field label .req { color: #fca5a5; margin-left: 2px; }
        .field input,
        .field select,
        .field textarea {
            background: rgba(255,255,255,0.09);
            border: 1px solid rgba(255,255,255,0.18);
            border-radius: 10px;
            padding: 11px 14px;
            color: #fff;
            font-family: 'Nunito', sans-serif;
            font-size: 14px;
            font-weight: 600;
            transition: border-color 0.18s, background 0.18s;
            outline: none;
            width: 100%;
        }
        .field input::placeholder,
        .field textarea::placeholder { color: rgba(255,255,255,0.30); font-weight: 400; }
        .field input:focus,
        .field select:focus,
        .field textarea:focus { border-color: #00c8e8; background: rgba(0,200,232,0.08); }
        .field select option { background: #0a7abf; color: #fff; }
        .field textarea { resize: vertical; min-height: 90px; }
 
        /* ── Botones ── */
        .btn-row { display: flex; gap: 12px; margin-top: 26px; flex-wrap: wrap; }
        .btn-primary { display: inline-flex; align-items: center; gap: 8px; background: linear-gradient(135deg,#065f46,#10b981); color: #fff; border: none; border-radius: 11px; padding: 12px 26px; font-family: 'Nunito', sans-serif; font-size: 14px; font-weight: 800; cursor: pointer; box-shadow: 0 5px 16px rgba(16,185,129,0.30); transition: opacity .18s, transform .15s; }
        .btn-primary:hover { opacity: .88; transform: translateY(-2px); }
        .btn-secondary { display: inline-flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.10); border: 1px solid rgba(255,255,255,0.22); color: #fff; border-radius: 11px; padding: 12px 22px; font-family: 'Nunito', sans-serif; font-size: 14px; font-weight: 700; cursor: pointer; text-decoration: none; transition: background .18s, transform .15s; }
        .btn-secondary:hover { background: rgba(255,255,255,0.16); transform: translateY(-2px); }
 
        /* ── Alertas ── */
        .alert { border-radius: 12px; padding: 14px 18px; font-size: 14px; font-weight: 700; margin-bottom: 22px; display: flex; align-items: center; gap: 10px; }
        .alert-ok    { background: rgba(16,185,129,0.18); border: 1px solid rgba(16,185,129,0.40); color: #6ee7b7; }
        .alert-error { background: rgba(239,68,68,0.18);  border: 1px solid rgba(239,68,68,0.38);  color: #fca5a5; }
 
        /* ── Voz ── */
        .voz-bar { background:rgba(0,0,0,0.22); border:1px solid rgba(255,255,255,0.16); border-radius:14px; padding:12px 18px; display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:22px; }
        .voz-title { font-size:13px; font-weight:700; color:rgba(255,255,255,0.80); white-space:nowrap; }
        .voz-status { font-size:12px; color:rgba(255,255,255,0.45); font-weight:600; }
        .voz-status.activo { color:#6ee7b7; animation:vozBlink 1.2s infinite; }
        @keyframes vozBlink{0%,100%{opacity:1}50%{opacity:.4}}
        .voz-btns { display:flex; gap:7px; flex-wrap:wrap; margin-left:auto; }
        .btn-voz { display:inline-flex; align-items:center; gap:5px; border:none; border-radius:9px; padding:7px 14px; font-family:'Nunito',sans-serif; font-size:12.5px; font-weight:700; cursor:pointer; transition:opacity .15s,transform .12s; }
        .btn-voz:hover { opacity:.84; transform:translateY(-1px); }
        .bv-green { background:linear-gradient(135deg,#065f46,#10b981); color:#fff; box-shadow:0 4px 12px rgba(16,185,129,0.28); }
        .bv-cyan  { background:rgba(0,200,232,0.18); border:1px solid rgba(0,200,232,0.30); color:#7fecf8; }
        .bv-red   { background:rgba(239,68,68,0.18); border:1px solid rgba(239,68,68,0.32); color:#fca5a5; }
 
        @media (max-width: 768px) { .sidebar{display:none;} .page-body{padding:16px;} .form-grid{grid-template-columns:1fr;} }
    </style>
</head>
<body>
<div class="layout">
 
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">🗂️ OfficeStock</div>
        <a href="dashboard.php">🏠 Dashboard</a>
        <?php if ($_SESSION['rol'] == 'admin'): ?>
            <a href="productos.php">📦 Productos</a>
            <a href="usuarios.php">👥 Usuarios</a>
        <?php endif; ?>
        <a href="categorias.php">📂 Categorías</a>
        <a href="proveedores.php" class="active">🏢 Proveedores</a>
        <a href="nuevo_inventario.php">📋 Inventario</a>
        <a href="salidas.php">📤 Salidas</a>
        <a href="devoluciones.php">↩️ Devoluciones</a>
        <a href="auditoria.php">🔍 Auditoría</a>
        <a href="logout.php" class="logout-link">🚪 Cerrar Sesión</a>
    </div>
 
    <!-- Contenido -->
    <div class="main-content">
        <div class="top-bar">
            <div>
                <h1>➕ Nuevo Proveedor</h1>
                <div class="sub">Inicio / <a href="proveedores.php" style="color:var(--text-muted);text-decoration:none;">Proveedores</a> / Nuevo Proveedor</div>
            </div>
            <div class="user-chip">
                <span class="dot"></span>
                <?php echo htmlspecialchars($_SESSION['usuario']); ?> · <?php echo ucfirst($_SESSION['rol']); ?>
            </div>
        </div>
 
        <div class="page-body">
 
            <!-- Asistente de voz -->
            <section class="voz-bar" role="region" aria-label="Asistente de voz">
                <div class="voz-title" aria-hidden="true">♿ 🔊 Asistente de Voz</div>
                <span class="voz-status" id="vozStatus" aria-live="polite" aria-atomic="true">Listo</span>
                <div class="voz-btns">
                    <button class="btn-voz bv-green" onclick="leerPagina()"  aria-label="Leer toda la página">🔊 Leer página</button>
                    <button class="btn-voz bv-cyan"  onclick="leerAyuda()"   aria-label="Escuchar ayuda">❓ Ayuda</button>
                    <button class="btn-voz bv-red"   onclick="detenerVoz()"  aria-label="Detener voz">⏹ Detener</button>
                </div>
            </section>
 
            <!-- Mensaje resultado -->
            <?php if ($mensaje !== ''): ?>
                <div class="alert alert-<?php echo $tipo_msg === 'ok' ? 'ok' : 'error'; ?>" role="alert">
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>
 
            <div class="section-label">🏢 Información del Proveedor</div>
 
            <div class="form-card">
                <form method="POST" action="nuevo_proveedor.php">
 
                    <div class="form-grid">
 
                        <!-- Nombre -->
                        <div class="field">
                            <label for="nombre">Nombre del Proveedor <span class="req">*</span></label>
                            <input type="text" id="nombre" name="nombre"
                                   placeholder="Ej. Microsoft, Dell, IKEA…"
                                   value="<?php echo htmlspecialchars($nombre ?? ''); ?>" required>
                        </div>
 
                        <!-- Categoría -->
                        <div class="field">
                            <label for="categoria">Categoría <span class="req">*</span></label>
                            <select id="categoria" name="categoria" required>
                                <option value="" disabled <?php echo empty($categoria) ? 'selected' : ''; ?>>— Seleccionar categoría —</option>
                                <?php
                                $cats = ['Tecnología','Útiles y Mobiliario','Papelería','Electrodomésticos','Otro'];
                                foreach ($cats as $c) {
                                    $sel = (($categoria ?? '') === $c) ? 'selected' : '';
                                    echo "<option value=\"$c\" $sel>$c</option>";
                                }
                                ?>
                            </select>
                        </div>
 
                        <!-- Contacto -->
                        <div class="field">
                            <label for="contacto">Nombre del Contacto <span class="req">*</span></label>
                            <input type="text" id="contacto" name="contacto"
                                   placeholder="Ej. Juan Pérez"
                                   value="<?php echo htmlspecialchars($contacto ?? ''); ?>" required>
                        </div>
 
                        <!-- Teléfono -->
                        <div class="field">
                            <label for="telefono">Teléfono / WhatsApp</label>
                            <input type="tel" id="telefono" name="telefono"
                                   placeholder="Ej. +503 7000-0000"
                                   value="<?php echo htmlspecialchars($telefono ?? ''); ?>">
                        </div>
 
                        <!-- Correo -->
                        <div class="field">
                            <label for="correo">Correo Electrónico <span class="req">*</span></label>
                            <input type="email" id="correo" name="correo"
                                   placeholder="Ej. contacto@proveedor.com"
                                   value="<?php echo htmlspecialchars($correo ?? ''); ?>" required>
                        </div>
 
                        <!-- Sitio web -->
                        <div class="field">
                            <label for="sitio_web">Sitio Web</label>
                            <input type="text" id="sitio_web" name="sitio_web"
                                   placeholder="Ej. https://www.proveedor.com"
                                   value="<?php echo htmlspecialchars($sitio_web ?? ''); ?>">
                        </div>
 
                        <!-- País -->
                        <div class="field">
                            <label for="pais">País de Origen</label>
                            <select id="pais" name="pais">
                                <option value="">— Seleccionar país —</option>
                                <?php
                                $paises = ['El Salvador','Guatemala','Honduras','México','Estados Unidos','China','Corea del Sur','Japón','Alemania','Otro'];
                                foreach ($paises as $p2) {
                                    $sel = (($pais ?? '') === $p2) ? 'selected' : '';
                                    echo "<option value=\"$p2\" $sel>$p2</option>";
                                }
                                ?>
                            </select>
                        </div>
 
                        <!-- Dirección -->
                        <div class="field">
                            <label for="direccion">Dirección / Sede</label>
                            <input type="text" id="direccion" name="direccion"
                                   placeholder="Ej. Col. Escalón, San Salvador"
                                   value="<?php echo htmlspecialchars($direccion ?? ''); ?>">
                        </div>
 
                        <!-- Descripción -->
                        <div class="field full">
                            <label for="descripcion">Descripción / Productos que ofrece</label>
                            <textarea id="descripcion" name="descripcion"
                                      placeholder="Describe brevemente qué productos o servicios ofrece este proveedor…"><?php echo htmlspecialchars($descripcion ?? ''); ?></textarea>
                        </div>
 
                    </div><!-- /form-grid -->
 
                    <div class="btn-row">
                        <button type="submit" class="btn-primary">✅ Guardar Proveedor</button>
                        <a href="proveedores.php" class="btn-secondary">← Volver a Proveedores</a>
                    </div>
 
                </form>
            </div>
 
        </div>
    </div>
</div>
 
<script>
const _vs = document.getElementById('vozStatus');
function hablar(texto, encolar) {
    encolar = encolar || false;
    if (!('speechSynthesis' in window)) return;
    if (!encolar) window.speechSynthesis.cancel();
    const u = new SpeechSynthesisUtterance(texto);
    u.lang = 'es-ES'; u.rate = 0.92; u.pitch = 1; u.volume = 1;
    u.onstart = () => { if(_vs){ _vs.textContent='🔊 Hablando...'; _vs.className='voz-status activo'; } };
    u.onend   = () => { if(_vs){ _vs.textContent='Listo';          _vs.className='voz-status'; } };
    window.speechSynthesis.speak(u);
}
function detenerVoz() { window.speechSynthesis.cancel(); if(_vs){_vs.textContent='Detenido';_vs.className='voz-status';} }
function leerAyuda() {
    window.speechSynthesis.cancel();
    ['Ayuda. Estás en el formulario para registrar un nuevo proveedor.',
     'Los campos obligatorios son: nombre del proveedor, categoría, contacto y correo electrónico.',
     'Usa la tecla Tab para moverte entre los campos.',
     'Al terminar presiona el botón Guardar Proveedor.',
     'Fin de ayuda.'
    ].forEach(t => hablar(t, true));
}
function leerPagina() {
    window.speechSynthesis && window.speechSynthesis.cancel();
    ['Formulario de Registro de Nuevo Proveedor.',
     'Campos requeridos: nombre del proveedor, categoría, nombre del contacto y correo electrónico.',
     'Campos opcionales: teléfono, sitio web, país de origen, dirección y descripción.',
     'Al terminar presiona Guardar Proveedor.','Fin.'
    ].forEach(t => hablar(t, true));
}
document.querySelectorAll('[role="alert"]').forEach(el => {
    if (el.textContent.trim()) setTimeout(() => hablar(el.textContent.replace(/<[^>]+>/g,'').trim(), true), 600);
});
</script>
</body>
</html>
<?php $conn->close(); ?>