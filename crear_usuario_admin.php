<?php
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] != 'admin') {
    $_SESSION['error'] = "Sin permisos";
    header("Location: dashboard.php");
    exit;
}

require 'db.php';

$success = false;
$usuario_creado = '';
$rol_creado = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario  = trim($_POST['usuario']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $rol      = $_POST['rol'];

    if (strlen($password) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres";
    } else {
        $sql_check = "SELECT id FROM usuarios WHERE usuario = ? OR email = ?";
        $stmt = $conn->prepare($sql_check);
        $stmt->bind_param("ss", $usuario, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "El usuario o email ya existe";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO usuarios (usuario, email, password, rol) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $usuario, $email, $password_hash, $rol);

            if ($stmt->execute()) {
                $success        = true;
                $usuario_creado = $usuario;
                $rol_creado     = $rol;
            } else {
                $error = "Error al crear usuario";
            }
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }

        body { font-family: 'Poppins', sans-serif; }

        /* ── Área de contenido ── */
        .page-area {
            display: flex;
            gap: 24px;
            padding: 28px;
            align-items: flex-start;
        }

        /* ── Tarjeta del formulario ── */
        .form-card {
            flex: 1;
            max-width: 560px;
            background: rgba(255,255,255,0.07);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255,255,255,0.13);
            border-radius: 20px;
            padding: 32px 28px;
            box-shadow: 0 16px 48px rgba(0,0,0,0.3);
        }

        .form-card .card-header {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 26px;
        }

        .icon-box {
            width: 48px;
            height: 48px;
            border-radius: 13px;
            background: linear-gradient(135deg, #7c3aed, #a78bfa);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            box-shadow: 0 6px 16px rgba(124,58,237,0.45);
            flex-shrink: 0;
        }

        .card-header h3 { margin: 0; font-size: 1.2rem; font-weight: 700; color: #fff; }
        .card-header p  { margin: 2px 0 0; font-size: 0.78rem; color: rgba(200,185,255,0.65); }

        /* ── Inputs ── */
        .field { margin-bottom: 16px; }

        .field label {
            display: block;
            font-size: 0.78rem;
            font-weight: 600;
            color: rgba(220,210,255,0.8);
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .field-wrap { position: relative; }

        .field-wrap .fi {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 0.95rem;
            pointer-events: none;
        }

        .field input {
            width: 100%;
            padding: 11px 13px 11px 40px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.14);
            border-radius: 10px;
            color: #fff;
            font-family: 'Poppins', sans-serif;
            font-size: 0.88rem;
            outline: none;
            transition: border-color .2s, background .2s;
        }

        .field input::placeholder { color: rgba(255,255,255,0.3); }
        .field input:focus {
            border-color: #a78bfa;
            background: rgba(255,255,255,0.12);
        }

        /* ── Selector de rol ── */
        .rol-grid { display: flex; gap: 10px; }

        .rol-opt { flex: 1; position: relative; }
        .rol-opt input[type="radio"] { position: absolute; opacity: 0; width: 0; }

        .rol-opt label {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
            padding: 13px 8px;
            border-radius: 12px;
            border: 2px solid rgba(255,255,255,0.1);
            background: rgba(255,255,255,0.04);
            cursor: pointer;
            transition: all .2s;
        }

        .rol-opt label .ri  { font-size: 1.5rem; }
        .rol-opt label .rn  { font-size: 0.88rem; font-weight: 600; color: #fff; }
        .rol-opt label .rd  { font-size: 0.7rem; color: rgba(200,185,255,0.55); text-align: center; }

        .rol-opt input:checked + label {
            border-color: #a78bfa;
            background: rgba(124,58,237,0.28);
            box-shadow: 0 0 0 3px rgba(167,139,250,0.18);
        }

        .rol-opt label:hover {
            border-color: rgba(167,139,250,0.45);
            background: rgba(255,255,255,0.08);
        }

        /* ── Mensaje error ── */
        .msg-error {
            background: rgba(239,68,68,0.16);
            border: 1px solid rgba(239,68,68,0.38);
            border-radius: 10px;
            padding: 10px 14px;
            color: #fca5a5;
            font-size: 0.85rem;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* ── Botones ── */
        .btn-row { display: flex; gap: 10px; margin-top: 22px; }

        .btn-crear {
            flex: 1;
            padding: 12px;
            background: linear-gradient(135deg, #7c3aed, #a78bfa);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.92rem;
            font-weight: 600;
            cursor: pointer;
            transition: opacity .2s, transform .15s, box-shadow .2s;
            box-shadow: 0 5px 18px rgba(124,58,237,0.42);
        }
        .btn-crear:hover { opacity: 0.9; transform: translateY(-2px); }

        .btn-cancel {
            padding: 12px 18px;
            background: rgba(255,255,255,0.06);
            color: rgba(220,210,255,0.8);
            border: 1px solid rgba(255,255,255,0.13);
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.88rem;
            text-decoration: none;
            display: flex;
            align-items: center;
            transition: background .2s;
        }
        .btn-cancel:hover { background: rgba(255,255,255,0.11); }

        /* ── Panel lateral info ── */
        .info-panel { width: 200px; flex-shrink: 0; }

        .info-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.09);
            border-radius: 14px;
            padding: 18px;
            margin-bottom: 12px;
        }

        .info-card h4 {
            margin: 0 0 10px;
            font-size: 0.78rem;
            font-weight: 600;
            color: #c4b5fd;
            text-transform: uppercase;
            letter-spacing: 0.7px;
        }

        .info-item {
            display: flex;
            gap: 8px;
            align-items: flex-start;
            margin-bottom: 8px;
        }

        .info-item .dot {
            width: 7px; height: 7px;
            border-radius: 50%;
            background: #a78bfa;
            margin-top: 5px;
            flex-shrink: 0;
        }

        .info-item span {
            font-size: 0.77rem;
            color: rgba(210,200,255,0.65);
            line-height: 1.4;
        }

        /* ════════════════════════════
           PANTALLA DE ÉXITO
        ════════════════════════════ */
        .success-wrap {
            padding: 40px 28px;
            display: flex;
            justify-content: center;
        }

        .success-card {
            background: rgba(255,255,255,0.07);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255,255,255,0.13);
            border-radius: 22px;
            padding: 44px 36px;
            text-align: center;
            max-width: 420px;
            width: 100%;
            box-shadow: 0 16px 48px rgba(0,0,0,0.3);
            animation: popIn .4s cubic-bezier(.175,.885,.32,1.275);
        }

        @keyframes popIn {
            from { opacity: 0; transform: scale(0.87); }
            to   { opacity: 1; transform: scale(1); }
        }

        .success-circle {
            width: 86px;
            height: 86px;
            border-radius: 50%;
            background: linear-gradient(135deg, #22c55e, #86efac);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            margin: 0 auto 22px;
            box-shadow: 0 8px 28px rgba(34,197,94,0.45);
            animation: glow 2s ease-in-out infinite;
        }

        @keyframes glow {
            0%, 100% { box-shadow: 0 8px 28px rgba(34,197,94,0.45); }
            50%       { box-shadow: 0 8px 42px rgba(34,197,94,0.72); }
        }

        .success-card h3 { font-size: 1.5rem; font-weight: 700; color: #fff; margin: 0 0 6px; }
        .success-card .sub { font-size: 0.87rem; color: rgba(200,185,255,0.65); margin-bottom: 26px; }

        .user-badge {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.13);
            border-radius: 13px;
            padding: 14px 20px;
            margin-bottom: 26px;
        }

        .user-badge .bi { font-size: 1.7rem; }
        .user-badge .bn { font-size: 1rem; font-weight: 700; color: #fff; text-align: left; }
        .user-badge .br { font-size: 0.77rem; color: #86efac; font-weight: 500; }

        .success-btns { display: flex; gap: 10px; justify-content: center; }

        .btn-go {
            padding: 11px 20px;
            background: linear-gradient(135deg, #7c3aed, #a78bfa);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.88rem;
            font-weight: 600;
            text-decoration: none;
            transition: opacity .2s, transform .15s;
            box-shadow: 0 5px 18px rgba(124,58,237,0.42);
        }
        .btn-go:hover { opacity: 0.9; transform: translateY(-2px); }

        .btn-new {
            padding: 11px 20px;
            background: rgba(255,255,255,0.07);
            color: rgba(220,210,255,0.85);
            border: 1px solid rgba(255,255,255,0.13);
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.88rem;
            text-decoration: none;
            transition: background .2s;
        }
        .btn-new:hover { background: rgba(255,255,255,0.12); }
    </style>
</head>
<body>
<div class="layout">

    <!-- ── Sidebar ── -->
    <div class="sidebar">
        <h2 class="logo">OfficeStock</h2>
        <a href="dashboard.php">🏠 Dashboard</a>
        <a href="productos.php">📦 Productos</a>
        <a href="usuarios.php" class="active">👥 Usuarios</a>
        <a href="categorias.php">📂 Categorías</a>
        <a href="proveedores.php">🏢 Proveedores</a>
        <a href="logout.php" class="logout-link">🚪 Cerrar Sesión</a>
    </div>

    <!-- ── Contenido ── -->
    <div class="main-content">

        <div class="header">
            <div>
                <h2>Crear Nuevo Usuario</h2>
                <p class="user-info">👤 <?php echo htmlspecialchars($_SESSION['usuario']); ?> | Administrador</p>
            </div>
            <div>
                <a href="usuarios.php" class="btn btn-secondary btn-small">← Volver a Usuarios</a>
            </div>
        </div>

        <?php if ($success): ?>

        <!-- ÉXITO -->
        <div class="success-wrap">
            <div class="success-card">
                <div class="success-circle">✓</div>
                <h3>¡Usuario Creado!</h3>
                <p class="sub">El usuario fue registrado exitosamente en el sistema.</p>

                <div class="user-badge">
                    <div class="bi"><?php echo $rol_creado === 'admin' ? '👑' : '👤'; ?></div>
                    <div>
                        <div class="bn"><?php echo htmlspecialchars($usuario_creado); ?></div>
                        <div class="br">
                            <?php echo $rol_creado === 'admin' ? 'Administrador' : 'Usuario'; ?>
                            &nbsp;•&nbsp; Cuenta activa
                        </div>
                    </div>
                </div>

                <div class="success-btns">
                    <a href="usuarios.php" class="btn-go">👥 Ver Usuarios</a>
                    <a href="crear_usuario_admin.php" class="btn-new">➕ Crear otro</a>
                </div>
            </div>
        </div>

        <?php else: ?>

        <!-- FORMULARIO -->
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
                        <div class="field-wrap">
                            <span class="fi">👤</span>
                            <input type="text" name="usuario" placeholder="Nombre de usuario" required
                                   value="<?php echo isset($_POST['usuario']) ? htmlspecialchars($_POST['usuario']) : ''; ?>">
                        </div>
                    </div>

                    <div class="field">
                        <label>Correo Electrónico</label>
                        <div class="field-wrap">
                            <span class="fi">📧</span>
                            <input type="email" name="email" placeholder="correo@ejemplo.com" required
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                    </div>

                    <div class="field">
                        <label>Contraseña</label>
                        <div class="field-wrap">
                            <span class="fi">🔒</span>
                            <input type="password" name="password" placeholder="Mínimo 6 caracteres" required>
                        </div>
                    </div>

                    <div class="field">
                        <label>Rol del Usuario</label>
                        <div class="rol-grid">

                            <div class="rol-opt">
                                <input type="radio" name="rol" id="rol-usuario" value="usuario"
                                    <?php echo (!isset($_POST['rol']) || $_POST['rol'] === 'usuario') ? 'checked' : ''; ?>>
                                <label for="rol-usuario">
                                    <span class="ri">👤</span>
                                    <span class="rn">Usuario</span>
                                    <span class="rd">Solo lectura del inventario</span>
                                </label>
                            </div>

                            <div class="rol-opt">
                                <input type="radio" name="rol" id="rol-admin" value="admin"
                                    <?php echo (isset($_POST['rol']) && $_POST['rol'] === 'admin') ? 'checked' : ''; ?>>
                                <label for="rol-admin">
                                    <span class="ri">👑</span>
                                    <span class="rn">Admin</span>
                                    <span class="rd">Control total del sistema</span>
                                </label>
                            </div>

                        </div>
                    </div>

                    <div class="btn-row">
                        <button type="submit" class="btn-crear">✓ &nbsp;Crear Usuario</button>
                        <a href="usuarios.php" class="btn-cancel">Cancelar</a>
                    </div>

                </form>
            </div>

            <!-- Panel lateral -->
            <div class="info-panel">
                <div class="info-card">
                    <h4>📋 Requisitos</h4>
                    <div class="info-item"><div class="dot"></div><span>Contraseña mínimo 6 caracteres</span></div>
                    <div class="info-item"><div class="dot"></div><span>Usuario único en el sistema</span></div>
                    <div class="info-item"><div class="dot"></div><span>Email no registrado previamente</span></div>
                </div>
                <div class="info-card">
                    <h4>🔐 Roles</h4>
                    <div class="info-item"><div class="dot"></div><span><strong style="color:#c4b5fd">Admin:</strong> gestiona productos, usuarios y todo el sistema</span></div>
                    <div class="info-item"><div class="dot"></div><span><strong style="color:#c4b5fd">Usuario:</strong> solo puede ver categorías y proveedores</span></div>
                </div>
            </div>

        </div>

        <?php endif; ?>

    </div>
</div>
</body>
</html>
