<?php
session_start();
if (isset($_SESSION['usuario'])) { header("Location: dashboard.php"); exit; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - OfficeStock Pro</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body.login-page {
            font-family: 'Nunito', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #071e38 0%, #0a4a72 45%, #0077b6 75%, #00b4d8 100%);
            padding: 20px;
        }

        .login-card {
            background: rgba(255,255,255,0.10);
            backdrop-filter: blur(22px);
            -webkit-backdrop-filter: blur(22px);
            border: 1px solid rgba(255,255,255,0.20);
            border-radius: 24px;
            padding: 38px 42px 34px;
            width: 100%;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 28px 70px rgba(0,0,0,0.40);
        }

        .logo-wrapper { margin-bottom: 16px; }

        .logo-img {
            width: 80px; height: 80px;
            filter: drop-shadow(0 0 18px rgba(0,200,232,0.75));
            animation: flotar 3s ease-in-out infinite;
        }

        @keyframes flotar {
            0%   { transform: translateY(0px);   filter: drop-shadow(0 0 18px rgba(0,200,232,0.75)); }
            25%  { transform: translateY(-10px); filter: drop-shadow(0 0 28px rgba(0,200,232,1)); }
            50%  { transform: translateY(-14px); filter: drop-shadow(0 8px 24px rgba(0,180,216,0.90)); }
            75%  { transform: translateY(-8px);  filter: drop-shadow(0 0 28px rgba(0,200,232,1)); }
            100% { transform: translateY(0px);   filter: drop-shadow(0 0 18px rgba(0,200,232,0.75)); }
        }

        .login-card h1 {
            font-size: 1.75rem; font-weight: 800; color: #ffffff;
            letter-spacing: -0.5px; margin-bottom: 4px;
            text-shadow: 0 2px 12px rgba(0,180,216,0.45);
        }

        .login-card .subtitle {
            font-size: 0.80rem; font-weight: 600; color: rgba(200,240,255,0.68);
            letter-spacing: 1.5px; text-transform: uppercase; margin-bottom: 26px;
        }

        .msg-error, .msg-success {
            border-radius: 10px; padding: 10px 14px;
            margin-bottom: 16px; font-size: 0.85rem; font-weight: 600;
        }
        .msg-error   { background: rgba(239,68,68,0.20);  border: 1px solid rgba(239,68,68,0.40);  color: #fca5a5; }
        .msg-success { background: rgba(16,185,129,0.20); border: 1px solid rgba(16,185,129,0.40); color: #6ee7b7; }

        .input-group { position: relative; margin-bottom: 14px; text-align: left; }
        .input-group span { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); font-size: 1rem; pointer-events: none; }

        .input-group input {
            width: 100%; padding: 13px 14px 13px 42px;
            background: rgba(255,255,255,0.10);
            border: 1px solid rgba(255,255,255,0.18);
            border-radius: 12px; color: #fff;
            font-family: 'Nunito', sans-serif; font-size: 0.9rem;
            outline: none; transition: border-color .2s, background .2s;
        }
        .input-group input::placeholder { color: rgba(255,255,255,0.38); }
        .input-group input:focus {
            border-color: #00c8e8;
            background: rgba(255,255,255,0.15);
        }

        .btn-login {
            width: 100%; padding: 13px;
            background: linear-gradient(135deg, #0077b6, #00c8e8);
            color: white; border: none; border-radius: 12px;
            font-family: 'Nunito', sans-serif; font-size: 0.95rem; font-weight: 800;
            cursor: pointer; letter-spacing: 0.5px; margin-top: 6px;
            transition: opacity .2s, transform .15s, box-shadow .2s;
            box-shadow: 0 6px 22px rgba(0,119,182,0.45);
        }
        .btn-login:hover {
            opacity: 0.90; transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0,119,182,0.60);
        }
        .btn-login:active { transform: translateY(0); }

        .divider { display: flex; align-items: center; gap: 10px; margin: 20px 0 16px; }
        .divider hr { flex: 1; border: none; border-top: 1px solid rgba(255,255,255,0.14); }
        .divider span { font-size: 0.75rem; color: rgba(255,255,255,0.38); white-space: nowrap; }

        .nav-links { display: flex; flex-direction: column; gap: 6px; }
        .nav-links a { color: rgba(160,230,255,0.80); text-decoration: none; font-size: 0.83rem; font-weight: 600; transition: color .2s; }
        .nav-links a:hover { color: #7fecf8; text-decoration: underline; }
    </style>
</head>
<body class="login-page">
    <div class="login-card">
        <div class="logo-wrapper">
            <img src="https://cdn-icons-png.flaticon.com/512/2910/2910791.png" alt="Logo" class="logo-img">
        </div>
        <h1>OfficeStock Pro</h1>
        <p class="subtitle">Control de Inventario Empresarial</p>

        <?php
        if (isset($_SESSION['error']))   { echo '<div class="msg-error">'   . htmlspecialchars($_SESSION['error'])   . '</div>'; unset($_SESSION['error']); }
        if (isset($_SESSION['success'])) { echo '<div class="msg-success">' . htmlspecialchars($_SESSION['success']) . '</div>'; unset($_SESSION['success']); }
        ?>

        <form action="validar_login.php" method="POST">
            <div class="input-group"><span>👤</span><input type="text"     name="usuario"  placeholder="Usuario"    required></div>
            <div class="input-group"><span>🔒</span><input type="password" name="password" placeholder="Contraseña" required></div>
            <button type="submit" class="btn-login">Iniciar Sesión</button>
        </form>

        <div class="divider"><hr><span>accesos</span><hr></div>
        <div class="nav-links">
            <a href="recuperar_usuario.php">¿Olvidaste tu usuario?</a>
            <a href="recuperar_password.php">¿Olvidaste tu contraseña?</a>
        </div>
    </div>
</body>
</html>
