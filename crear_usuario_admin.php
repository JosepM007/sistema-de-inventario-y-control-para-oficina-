<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'admin') {
    $_SESSION['error'] = "Sin permisos";
    header("Location: dashboard.php"); exit;
}
require 'db.php';

$success = false; $usuario_creado = ''; $rol_creado = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario  = trim($_POST['usuario']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $rol      = $_POST['rol'];

    if (strlen($password) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres";
    } else {
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE usuario = ? OR email = ?");
        $stmt->bind_param("ss", $usuario, $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = "El usuario o email ya existe";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO usuarios (usuario, email, password, rol) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $usuario, $email, $hash, $rol);
            if ($stmt->execute()) { $success = true; $usuario_creado = $usuario; $rol_creado = $rol; }
            else { $error = "Error al crear usuario"; }
        }
        $stmt->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Usuario - OfficeStock Pro</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Nunito', sans-serif; background: linear-gradient(135deg, #0f4c8a 0%, #0a7abf 45%, #00b4d8 100%); min-height: 100vh; color: #fff; }
        .layout { display: flex; min-height: 100vh; }

        .sidebar { width: 220px; flex-shrink: 0; background: rgba(0,0,0,0.28); backdrop-filter: blur(16px); border-right: 1px solid rgba(255,255,255,0.14); display: flex; flex-direction: column; padding: 28px 0 24px; position: sticky; top: 0; height: 100vh; }
        .logo { font-size: 19px; font-weight: 800; color: #fff; padding: 0 24px 24px; border-bottom: 1px solid rgba(255,255,255,0.14); margin-bottom: 12px; }
        .sidebar a { display: flex; align-items: center; gap: 10px; padding: 11px 24px; color: rgba(255,255,255,0.58); text-decoration: none; font-size: 13.5px; font-weight: 600; border-left: 3px solid transparent; transition: all 0.18s; }
        .sidebar a:hover  { color: #fff; background: rgba(255,255,255,0.08); }
        .sidebar a.active { color: #fff; border-left-color: #00c8e8; background: rgba(0,200,232,0.15); }
        .sidebar .logout-link { margin-top: auto; color: #fca5a5; border-top: 1px solid rgba(255,255,255,0.14); padding-top: 14px; }

        .main-content { flex: 1; display: flex; flex-direction: column; overflow-y: auto; }

        .header { padding: 22px 30px 18px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.14); background: rgba(0,0,0,0.15); }
        .header h2 { font-size: 20px; font-weight: 800; }
        .user-info { color: rgba(255,255,255,0.62); font-size: 13px; }
        .btn-secondary { background: rgba(255,255,255,0.12); color: #fff; border: 1px solid rgba(255,255,255,0.20); border-radius: 8px; padding: 8px 16px; text-decoration: none; font-size: 13px; font-weight: 700; transition: background .2s; }
        .btn-secondary:hover { background: rgba(255,255,255,0.20); }

        .page-area { display: flex; gap: 24px; padding: 28px; align-items: flex-start; }

        .form-card { flex: 1; max-width: 560px; background: rgba(255,255,255,0.10); backdrop-filter: blur(16px); border: 1px solid rgba(255,255,255,0.18); border-radius: 20px; padding: 32px 28px; box-shadow: 0 16px 48px rgba(0,0,0,0.22); }
        .form-card .card-header { display: flex; align-items: center; gap: 14px; margin-bottom: 26px; }
        .icon-box { width: 48px; height: 48px; border-radius: 13px; background: linear-gradient(135deg,#0077b6,#00c8e8); display: flex; align-items: center; justify-content: center; font-size: 22px; box-shadow: 0 6px 16px rgba(0,119,182,0.45); flex-shrink: 0; }
        .card-header h3 { margin: 0; font-size: 1.2rem; font-weight: 800; color: #fff; }
        .card-header p  { margin: 2px 0 0; font-size: 0.78rem; color: rgba(255,255,255,0.55); }

        .field { margin-bottom: 16px; }
        .field label { display: block; font-size: 0.78rem; font-weight: 700; color: rgba(255,255,255,0.70); margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.5px; }
        .field-wrap { position: relative; }
        .field-wrap .fi { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); font-size: 0.95rem; pointer-events: none; }
        .field input { width: 100%; padding: 11px 13px 11px 40px; background: rgba(255,255,255,0.10); border: 1px solid rgba(255,255,255,0.18); border-radius: 10px; color: #fff; font-family: 'Nunito', sans-serif; font-size: 0.88rem; outline: none; transition: border-color .2s, background .2s; }
        .field input::placeholder { color: rgba(255,255,255,0.35); }
        .field input:focus { border-color: #00c8e8; background: rgba(255,255,255,0.15); }

        .rol-grid { display: flex; gap: 10px; }
        .rol-opt { flex: 1; position: relative; }
        .rol-opt input[type="radio"] { position: absolute; opacity: 0; width: 0; }
        .rol-opt label { display: flex; flex-direction: column; align-items: center; gap: 5px; padding: 13px 8px; border-radius: 12px; border: 2px solid rgba(255,255,255,0.14); background: rgba(255,255,255,0.06); cursor: pointer; transition: all .2s; }
        .rol-opt label .ri { font-size: 1.5rem; }
        .rol-opt label .rn { font-size: 0.88rem; font-weight: 700; color: #fff; }
        .rol-opt label .rd { font-size: 0.7rem; color: rgba(255,255,255,0.50); text-align: center; }
        .rol-opt input:checked + label { border-color: #00c8e8; background: rgba(0,200,232,0.22); box-shadow: 0 0 0 3px rgba(0,200,232,0.18); }
        .rol-opt label:hover { border-color: rgba(0,200,232,0.45); background: rgba(255,255,255,0.10); }

        .msg-error { background: rgba(239,68,68,0.18); border: 1px solid rgba(239,68,68,0.38); border-radius: 10px; padding: 10px 14px; color: #fca5a5; font-size: 0.85rem; margin-bottom: 18px; display: flex; align-items: center; gap: 8px; }

        .btn-row { display: flex; gap: 10px; margin-top: 22px; }
        .btn-crear { flex: 1; padding: 12px; background: linear-gradient(135deg,#0077b6,#00c8e8); color: #fff; border: none; border-radius: 10px; font-family: 'Nunito', sans-serif; font-size: 0.92rem; font-weight: 800; cursor: pointer; transition: opacity .2s, transform .15s; box-shadow: 0 5px 18px rgba(0,119,182,0.42); }
        .btn-crear:hover { opacity: 0.88; transform: translateY(-2px); }
        .btn-cancel { padding: 12px 18px; background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.75); border: 1px solid rgba(255,255,255,0.16); border-radius: 10px; font-family: 'Nunito', sans-serif; font-size: 0.88rem; text-decoration: none; display: flex; align-items: center; transition: background .2s; }
        .btn-cancel:hover { background: rgba(255,255,255,0.14); }

        .info-panel { width: 200px; flex-shrink: 0; }
        .info-card { background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.12); border-radius: 14px; padding: 18px; margin-bottom: 12px; }
        .info-card h4 { margin: 0 0 10px; font-size: 0.78rem; font-weight: 700; color: #7fecf8; text-transform: uppercase; letter-spacing: 0.7px; }
        .info-item { display: flex; gap: 8px; align-items: flex-start; margin-bottom: 8px; }
        .info-item .dot { width: 7px; height: 7px; border-radius: 50%; background: #00c8e8; margin-top: 5px; flex-shrink: 0; }
        .info-item span { font-size: 0.77rem; color: rgba(255,255,255,0.60); line-height: 1.4; }

        /* Éxito */
        .success-wrap { padding: 40px 28px; display: flex; justify-content: center; }
        .success-card { background: rgba(255,255,255,0.10); backdrop-filter: blur(16px); border: 1px solid rgba(255,255,255,0.18); border-radius: 22px; padding: 44px 36px; text-align: center; max-width: 420px; width: 100%; box-shadow: 0 16px 48px rgba(0,0,0,0.25); animation: popIn .4s cubic-bezier(.175,.885,.32,1.275); }
        @keyframes popIn { from{opacity:0;transform:scale(0.87)} to{opacity:1;transform:scale(1)} }
        .success-circle { width: 86px; height: 86px; border-radius: 50%; background: linear-gradient(135deg,#22c55e,#86efac); display: flex; align-items: center; justify-content: center; font-size: 40px; margin: 0 auto 22px; box-shadow: 0 8px 28px rgba(34,197,94,0.45); animation: glow 2s ease-in-out infinite; }
        @keyframes glow { 0%,100%{box-shadow:0 8px 28px rgba(34,197,94,0.45)} 50%{box-shadow:0 8px 42px rgba(34,197,94,0.72)} }
        .success-card h3 { font-size: 1.5rem; font-weight: 800; color: #fff; margin: 0 0 6px; }
        .success-card .sub { font-size: 0.87rem; color: rgba(255,255,255,0.58); margin-bottom: 26px; }
        .user-badge { display: inline-flex; align-items: center; gap: 12px; background: rgba(255,255,255,0.10); border: 1px solid rgba(255,255,255,0.16); border-radius: 13px; padding: 14px 20px; margin-bottom: 26px; }
        .user-badge .bi { font-size: 1.7rem; }
        .user-badge .bn { font-size: 1rem; font-weight: 800; color: #fff; text-align: left; }
        .user-badge .br { font-size: 0.77rem; color: #86efac; font-weight: 600; }
        .success-btns { display: flex; gap: 10px; justify-content: center; }
        .btn-go { padding: 11px 20px; background: linear-gradient(135deg,#0077b6,#00c8e8); color: #fff; border: none; border-radius: 10px; font-family: 'Nunito', sans-serif; font-size: 0.88rem; font-weight: 700; text-decoration: none; transition: opacity .2s, transform .15s; box-shadow: 0 5px 18px rgba(0,119,182,0.42); }
        .btn-go:hover { opacity: 0.88; transform: translateY(-2px); }
        .btn-new { padding: 11px 20px; background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.80); border: 1px solid rgba(255,255,255,0.16); border-radius: 10px; font-family: 'Nunito', sans-serif; font-size: 0.88rem; text-decoration: none; transition: background .2s; }
        .btn-new:hover { background: rgba(255,255,255,0.14); }
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
        <a href="logout.php" class="logout-link">🚪 Cerrar Sesión</a>
    </div>

    <div class="main-content">
        <div class="header">
            <div>
                <h2>➕ Crear Nuevo Usuario</h2>
                <p class="user-info">👤 <?php echo htmlspecialchars($_SESSION['usuario']); ?> | Administrador</p>
            </div>
            <a href="usuarios.php" class="btn-secondary">← Volver a Usuarios</a>
        </div>

        <?php if ($success): ?>
        <div class="success-wrap">
            <div class="success-card">
                <div class="success-circle">✓</div>
                <h3>¡Usuario Creado!</h3>
                <p class="sub">El usuario fue registrado exitosamente en el sistema.</p>
                <div class="user-badge">
                    <div class="bi"><?php echo $rol_creado === 'admin' ? '👑' : '👤'; ?></div>
                    <div>
                        <div class="bn"><?php echo htmlspecialchars($usuario_creado); ?></div>
                        <div class="br"><?php echo $rol_creado === 'admin' ? 'Administrador' : 'Usuario'; ?> &nbsp;•&nbsp; Cuenta activa</div>
                    </div>
                </div>
                <div class="success-btns">
                    <a href="usuarios.php" class="btn-go">👥 Ver Usuarios</a>
                    <a href="crear_usuario_admin.php" class="btn-new">➕ Crear otro</a>
                </div>
            </div>
        </div>

        <?php else: ?>
        <div class="page-area">
            <div class="form-card">
                <div class="card-header">
                    <div class="icon-box">➕</div>
                    <div>
                        <h3>Nuevo Usuario</h3>
                        <p>Solo los administradores pueden crear usuarios</p>
                    </div>
                </div>

                <?php if (isset($error)): ?>
                    <div class="msg-error">⚠️ <?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="field">
                        <label>Usuario</label>
                        <div class="field-wrap"><span class="fi">👤</span>
                            <input type="text" name="usuario" placeholder="Nombre de usuario" required value="<?php echo isset($_POST['usuario']) ? htmlspecialchars($_POST['usuario']) : ''; ?>">
                        </div>
                    </div>
                    <div class="field">
                        <label>Correo Electrónico</label>
                        <div class="field-wrap"><span class="fi">📧</span>
                            <input type="email" name="email" placeholder="correo@ejemplo.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                    </div>
                    <div class="field">
                        <label>Contraseña</label>
                        <div class="field-wrap"><span class="fi">🔒</span>
                            <input type="password" name="password" placeholder="Mínimo 6 caracteres" required>
                        </div>
                    </div>
                    <div class="field">
                        <label>Rol del Usuario</label>
                        <div class="rol-grid">
                            <div class="rol-opt">
                                <input type="radio" name="rol" id="rol-usuario" value="usuario" <?php echo (!isset($_POST['rol']) || $_POST['rol'] === 'usuario') ? 'checked' : ''; ?>>
                                <label for="rol-usuario"><span class="ri">👤</span><span class="rn">Usuario</span><span class="rd">Solo lectura del inventario</span></label>
                            </div>
                            <div class="rol-opt">
                                <input type="radio" name="rol" id="rol-admin" value="admin" <?php echo (isset($_POST['rol']) && $_POST['rol'] === 'admin') ? 'checked' : ''; ?>>
                                <label for="rol-admin"><span class="ri">👑</span><span class="rn">Admin</span><span class="rd">Control total del sistema</span></label>
                            </div>
                        </div>
                    </div>
                    <div class="btn-row">
                        <button type="submit" class="btn-crear">✓ &nbsp;Crear Usuario</button>
                        <a href="usuarios.php" class="btn-cancel">Cancelar</a>
                    </div>
                </form>
            </div>

            <div class="info-panel">
                <div class="info-card">
                    <h4>📋 Requisitos</h4>
                    <div class="info-item"><div class="dot"></div><span>Contraseña mínimo 6 caracteres</span></div>
                    <div class="info-item"><div class="dot"></div><span>Usuario único en el sistema</span></div>
                    <div class="info-item"><div class="dot"></div><span>Email no registrado previamente</span></div>
                </div>
                <div class="info-card">
                    <h4>🔐 Roles</h4>
                    <div class="info-item"><div class="dot"></div><span><strong style="color:#7fecf8">Admin:</strong> gestiona productos, usuarios y todo el sistema</span></div>
                    <div class="info-item"><div class="dot"></div><span><strong style="color:#7fecf8">Usuario:</strong> solo puede ver categorías y proveedores</span></div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
