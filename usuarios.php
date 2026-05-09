<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'admin') { $_SESSION['error'] = "Sin permisos"; header("Location: dashboard.php"); exit; }
require 'db.php';
$usuarios = $conn->query("SELECT * FROM usuarios ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - OfficeStock Pro</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root{--card-bg:rgba(255,255,255,0.10);--card-border:rgba(255,255,255,0.16);--text-muted:rgba(255,255,255,0.55);--row-hover:rgba(255,255,255,0.07)}
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Nunito',sans-serif;background:linear-gradient(135deg,#0f4c8a 0%,#0a7abf 45%,#00b4d8 100%);min-height:100vh;color:#fff}
        .layout{display:flex;min-height:100vh}
        .sidebar{width:220px;flex-shrink:0;background:rgba(0,0,0,0.28);backdrop-filter:blur(16px);border-right:1px solid var(--card-border);display:flex;flex-direction:column;padding:28px 0 24px;position:sticky;top:0;height:100vh}
        .logo{font-size:19px;font-weight:800;color:#fff;padding:0 24px 24px;border-bottom:1px solid var(--card-border);margin-bottom:12px}
        .sidebar a{display:flex;align-items:center;gap:10px;padding:11px 24px;color:var(--text-muted);text-decoration:none;font-size:13.5px;font-weight:600;border-left:3px solid transparent;transition:all 0.18s}
        .sidebar a:hover{color:#fff;background:rgba(255,255,255,0.08)}
        .sidebar a.active{color:#fff;border-left-color:#00c8e8;background:rgba(0,200,232,0.15)}
        .sidebar .logout-link{margin-top:auto;color:#fca5a5;border-top:1px solid var(--card-border);padding-top:14px}
        .main-content{flex:1;display:flex;flex-direction:column;overflow:hidden}
        .top-bar{padding:24px 32px 20px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid var(--card-border);background:rgba(0,0,0,0.15);backdrop-filter:blur(6px)}
        .top-bar-left h1{font-size:22px;font-weight:800}.top-bar-left .breadcrumb{font-size:12px;color:var(--text-muted);margin-top:2px}
        .user-chip{display:inline-flex;align-items:center;gap:7px;background:rgba(255,255,255,0.12);border:1px solid var(--card-border);border-radius:20px;padding:6px 14px;font-size:13px;font-weight:600}
        .role-dot{width:8px;height:8px;border-radius:50%;background:#00c8e8;display:inline-block}
        .page-body{padding:28px 32px;flex:1}
        .alert{display:flex;align-items:center;gap:10px;padding:12px 16px;border-radius:10px;font-size:13.5px;font-weight:600;margin-bottom:20px;animation:slideIn .3s ease}
        .alert-ok{background:rgba(16,185,129,0.16);border:1px solid rgba(16,185,129,0.32);color:#6ee7b7}
        .alert-err{background:rgba(239,68,68,0.16);border:1px solid rgba(239,68,68,0.32);color:#fca5a5}
        @keyframes slideIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}
        .stats-strip{display:flex;gap:14px;margin-bottom:24px;flex-wrap:wrap}
        .stat-card{flex:1;min-width:130px;background:var(--card-bg);border:1px solid var(--card-border);border-radius:12px;padding:16px 20px;display:flex;align-items:center;gap:14px}
        .stat-icon{width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0}
        .stat-icon.cyan{background:rgba(0,200,232,0.22)}.stat-icon.amber{background:rgba(245,158,11,0.22)}.stat-icon.green{background:rgba(16,185,129,0.22)}
        .stat-num{font-size:22px;font-weight:800;line-height:1}.stat-label{font-size:11.5px;color:var(--text-muted);margin-top:2px;font-weight:600}
        .card{background:var(--card-bg);border:1px solid var(--card-border);border-radius:16px;overflow:hidden;box-shadow:0 16px 48px rgba(0,0,0,0.18)}
        .card-header{display:flex;justify-content:space-between;align-items:center;padding:18px 22px;border-bottom:1px solid var(--card-border);background:rgba(255,255,255,0.04)}
        .card-header h3{font-size:15px;font-weight:700;display:flex;align-items:center;gap:8px}
        .btn-crear{display:inline-flex;align-items:center;gap:7px;background:linear-gradient(135deg,#065f46,#10b981);color:#fff;text-decoration:none;padding:8px 18px;border-radius:9px;font-size:13px;font-weight:700;transition:opacity .2s,transform .15s;box-shadow:0 4px 14px rgba(16,185,129,0.30)}
        .btn-crear:hover{opacity:.88;transform:translateY(-1px)}
        .table-wrap{overflow-x:auto}
        table{width:100%;border-collapse:collapse;font-size:13.5px}
        thead th{padding:13px 18px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.9px;color:#7fecf8;border-bottom:1px solid var(--card-border);white-space:nowrap}
        tbody tr{border-bottom:1px solid rgba(255,255,255,0.06);transition:background .15s}
        tbody tr:last-child{border-bottom:none}
        tbody tr:hover{background:var(--row-hover)}
        tbody td{padding:14px 18px;color:#fff;vertical-align:middle}
        .id-badge{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:7px;background:rgba(0,200,232,0.20);color:#7fecf8;font-size:12px;font-weight:700}
        .avatar{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#0077b6,#00c8e8);display:inline-flex;align-items:center;justify-content:center;font-weight:800;font-size:14px;color:#fff;margin-right:10px;flex-shrink:0;box-shadow:0 3px 10px rgba(0,0,0,0.22)}
        .user-cell{display:flex;align-items:center}.user-name{font-weight:700;color:#fff}.user-you{font-size:11px;color:#7fecf8;margin-top:1px}
        .email-cell{color:#7fecf8;font-size:13px}
        .rol-badge{display:inline-flex;align-items:center;gap:5px;padding:4px 11px;border-radius:20px;font-size:12px;font-weight:700}
        .rol-badge.admin{background:rgba(245,158,11,0.20);color:#fcd34d;border:1px solid rgba(245,158,11,0.28)}
        .rol-badge.usuario{background:rgba(0,200,232,0.18);color:#7fecf8;border:1px solid rgba(0,200,232,0.28)}
        .acciones{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
        .btn-accion{display:inline-flex;align-items:center;gap:5px;padding:6px 13px;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;transition:opacity .2s,transform .15s;white-space:nowrap}
        .btn-accion:hover{opacity:.82;transform:translateY(-1px)}
        .btn-admin{background:rgba(245,158,11,0.22);color:#fcd34d;border:1px solid rgba(245,158,11,0.32)}
        .btn-quitar{background:rgba(0,200,232,0.20);color:#7fecf8;border:1px solid rgba(0,200,232,0.30)}
        .btn-eliminar{background:rgba(239,68,68,0.18);color:#fca5a5;border:1px solid rgba(239,68,68,0.28)}
        .tu-cuenta{font-size:12px;color:#7fecf8;background:rgba(0,200,232,0.12);border:1px solid rgba(0,200,232,0.22);padding:4px 12px;border-radius:20px;font-weight:600}
        @media(max-width:768px){.sidebar{display:none}.page-body{padding:16px}.top-bar{padding:16px}}
    
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
<div class="layout">
    <div class="sidebar">
        <div class="logo">🗂️ OfficeStock</div>
        <a href="dashboard.php">🏠 Dashboard</a>
        <a href="productos.php">📦 Productos</a>
        <a href="usuarios.php" class="active">👥 Usuarios</a>
        <a href="categorias.php">📂 Categorías</a>
        <a href="proveedores.php">🏢 Proveedores</a>
        <a href="salidas.php">📤 Salidas</a>
        <a href="auditoria.php">🔍 Auditoría</a>
        <a href="logout.php" class="logout-link">🚪 Cerrar Sesión</a>
    </div>
    <div class="main-content">
        <div class="top-bar">
            <div class="top-bar-left"><h1>👥 Gestión de Usuarios</h1><div class="breadcrumb">Inicio / Administración / Usuarios</div></div>
            <div class="user-chip"><span class="role-dot"></span><?php echo htmlspecialchars($_SESSION['usuario']); ?> · Admin</div>
        </div>
        <div class="page-body">
            <!-- ♿ ASISTENTE DE VOZ -->
            <section class="voz-bar" role="region" aria-label="Asistente de voz para personas con discapacidad visual">
                <div class="voz-title" aria-hidden="true">♿ 🔊 Asistente de Voz</div>
                <span class="voz-status" id="vozStatus" aria-live="polite" aria-atomic="true">Listo</span>
                <div class="voz-btns">
                    <button class="btn-voz bv-green" onclick="leerPagina()" aria-label="Leer toda la pagina en voz alta">🔊 Leer página</button>
                    <button class="btn-voz bv-cyan"  onclick="leerAyuda()"  aria-label="Escuchar instrucciones de ayuda">❓ Ayuda</button>
                    <button class="btn-voz bv-red"   onclick="detenerVoz()" aria-label="Detener la lectura de voz">⏹ Detener</button>
                </div>
            </section>

            <?php if (isset($_SESSION['success'])): ?><div class="alert alert-ok">✅ <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div><?php endif; ?>
            <?php if (isset($_SESSION['error'])):   ?><div class="alert alert-err">⚠️ <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div><?php endif; ?>
            <?php
                $total_u  = $conn->query("SELECT COUNT(*) as t FROM usuarios")->fetch_assoc()['t'];
                $total_a  = $conn->query("SELECT COUNT(*) as t FROM usuarios WHERE rol='admin'")->fetch_assoc()['t'];
                $total_us = $conn->query("SELECT COUNT(*) as t FROM usuarios WHERE rol='usuario'")->fetch_assoc()['t'];
            ?>
            <div class="stats-strip">
                <div class="stat-card"><div class="stat-icon cyan">👥</div><div><div class="stat-num"><?php echo $total_u; ?></div><div class="stat-label">Total usuarios</div></div></div>
                <div class="stat-card"><div class="stat-icon amber">👑</div><div><div class="stat-num"><?php echo $total_a; ?></div><div class="stat-label">Administradores</div></div></div>
                <div class="stat-card"><div class="stat-icon green">👤</div><div><div class="stat-num"><?php echo $total_us; ?></div><div class="stat-label">Usuarios estándar</div></div></div>
            </div>
            <div class="card">
                <div class="card-header"><h3><span>📋</span> Usuarios Registrados</h3><a href="crear_usuario_admin.php" class="btn-crear">➕ Crear Usuario</a></div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>ID</th><th>Usuario</th><th>Email</th><th>Rol</th><th>Acciones</th></tr></thead>
                        <tbody>
                        <?php
                            $usuarios = $conn->query("SELECT * FROM usuarios ORDER BY id ASC");
                            while ($user = $usuarios->fetch_assoc()):
                            $es_yo = ($user['usuario'] === $_SESSION['usuario']);
                            $inicial = strtoupper(mb_substr($user['usuario'], 0, 1));
                        ?>
                        <tr>
                            <td><span class="id-badge"><?php echo $user['id']; ?></span></td>
                            <td><div class="user-cell"><div class="avatar"><?php echo htmlspecialchars($inicial); ?></div><div><div class="user-name"><?php echo htmlspecialchars($user['usuario']); ?></div><?php if ($es_yo): ?><div class="user-you">✦ Tu cuenta</div><?php endif; ?></div></div></td>
                            <td class="email-cell"><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php if ($user['rol']==='admin'): ?><span class="rol-badge admin">👑 Admin</span><?php else: ?><span class="rol-badge usuario">👤 Usuario</span><?php endif; ?></td>
                            <td>
                                <?php if (!$es_yo): ?>
                                <div class="acciones">
                                    <?php if ($user['rol']==='usuario'): ?>
                                        <a href="cambiar_rol.php?id=<?php echo $user['id']; ?>&rol=admin" class="btn-accion btn-admin">👑 Hacer Admin</a>
                                    <?php else: ?>
                                        <a href="cambiar_rol.php?id=<?php echo $user['id']; ?>&rol=usuario" class="btn-accion btn-quitar">👤 Quitar Admin</a>
                                    <?php endif; ?>
                                    <a href="eliminar_usuario.php?id=<?php echo $user['id']; ?>" class="btn-accion btn-eliminar" onclick="return confirm('¿Eliminar al usuario «<?php echo htmlspecialchars($user['usuario']); ?>»?')">🗑️ Eliminar</a>
                                </div>
                                <?php else: ?><span class="tu-cuenta">⭐ Tu cuenta</span><?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
/* ── Lectura de página: Gestión de Usuarios ── */

/* ═══════════════════════════════════════
   ♿ ASISTENTE DE VOZ — inline, sin voz.js
   ═══════════════════════════════════════ */
const _vs = document.getElementById('vozStatus');
function hablar(texto, encolar) {
    encolar = encolar || false;
    if (!('speechSynthesis' in window)) return;
    if (!encolar) window.speechSynthesis.cancel();
    const u = new SpeechSynthesisUtterance(texto);
    u.lang = 'es-ES'; u.rate = 0.92; u.pitch = 1; u.volume = 1;
    u.onstart = function() { if (_vs) { _vs.textContent = '🔊 Hablando...'; _vs.className = 'voz-status activo'; } };
    u.onend   = function() { if (_vs) { _vs.textContent = 'Listo'; _vs.className = 'voz-status'; } };
    window.speechSynthesis.speak(u);
}
function detenerVoz() {
    window.speechSynthesis.cancel();
    if (_vs) { _vs.textContent = 'Detenido'; _vs.className = 'voz-status'; }
}
function leerAyuda() {
    window.speechSynthesis.cancel();
    var msgs = [
        'Ayuda del asistente de voz para personas con discapacidad visual.',
        'Boton Leer Pagina: lee en voz alta toda la informacion de esta seccion.',
        'Boton Ayuda: reproduce estas instrucciones.',
        'Boton Detener: para la voz inmediatamente.',
        'Usa la tecla Tab para navegar entre los controles.',
        'Usa Enter o Espacio para activar botones y enlaces.',
        'Fin de ayuda.'
    ];
    msgs.forEach(function(t) { hablar(t, true); });
}

function leerPagina() {
    window.speechSynthesis && window.speechSynthesis.cancel();
    
    const rows = document.querySelectorAll('tbody tr');
    const frases = ['Página de gestión de usuarios. Se muestran ' + rows.length + ' usuarios registrados.'];
    rows.forEach((r, i) => {
        const nombre = r.querySelector('.user-name')?.textContent.trim() || '';
        const rol    = r.querySelector('.rol-badge')?.textContent.trim() || '';
        const email  = r.querySelector('.email-cell')?.textContent.trim() || '';
        if (nombre) frases.push('Usuario ' + (i+1) + ': ' + nombre + '. Rol: ' + rol + '. Correo: ' + email + '.');
    });
    frases.push('Para crear un usuario nuevo usa el botón Crear Usuario en la parte superior derecha.');
    frases.push('Fin.');
    frases.forEach(t => hablar(t, true));

};
/* Anunciar alertas automáticamente */
document.querySelectorAll('[role="alert"]').forEach(el => {
    if (el.textContent.trim()) setTimeout(() => hablar(el.textContent.trim(), true), 600);
});
</script>
</body>
</html>
<?php $conn->close(); ?>
