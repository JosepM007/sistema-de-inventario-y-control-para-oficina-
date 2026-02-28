<?php
session_start();
if (isset($_SESSION['usuario'])) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - OfficeStock Pro</title>
    <link rel="stylesheet" href="css/style.css">
    <!-- Fuente moderna de Google -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body.login-page {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1a0533 0%, #3b0d6e 50%, #6d28d9 100%);
            padding: 20px;
        }

        /* Tarjeta central */
        .login-card {
            background: rgba(255, 255, 255, 0.07);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 24px;
            padding: 36px 40px 32px;
            width: 100%;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 25px 60px rgba(0,0,0,0.4);
        }

        /* Logo con animación */
        .logo-wrapper {
            margin-bottom: 14px;
        }

        .logo-img {
            width: 80px;
            height: 80px;
            filter: drop-shadow(0 0 18px rgba(167, 139, 250, 0.7));
            animation: flotar 3s ease-in-out infinite;
        }

        @keyframes flotar {
            0%   { transform: translateY(0px) rotate(0deg); filter: drop-shadow(0 0 18px rgba(167,139,250,0.7)); }
            25%  { transform: translateY(-10px) rotate(-3deg); filter: drop-shadow(0 0 28px rgba(167,139,250,1)); }
            50%  { transform: translateY(-14px) rotate(0deg); filter: drop-shadow(0 8px 24px rgba(139,92,246,0.9)); }
            75%  { transform: translateY(-8px) rotate(3deg); filter: drop-shadow(0 0 28px rgba(167,139,250,1)); }
            100% { transform: translateY(0px) rotate(0deg); filter: drop-shadow(0 0 18px rgba(167,139,250,0.7)); }
        }

        /* Título */
        .login-card h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: -0.5px;
            margin-bottom: 4px;
            text-shadow: 0 2px 12px rgba(139,92,246,0.5);
        }

        .login-card .subtitle {
            font-size: 0.82rem;
            font-weight: 300;
            color: rgba(220, 210, 255, 0.7);
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 24px;
        }

        /* Mensajes */
        .msg-error, .msg-success {
            border-radius: 10px;
            padding: 10px 14px;
            margin-bottom: 16px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .msg-error   { background: rgba(239,68,68,0.2);  border: 1px solid rgba(239,68,68,0.4);  color: #fca5a5; }
        .msg-success { background: rgba(34,197,94,0.2);  border: 1px solid rgba(34,197,94,0.4);  color: #86efac; }

        /* Inputs */
        .input-group {
            position: relative;
            margin-bottom: 14px;
            text-align: left;
        }

        .input-group span {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1rem;
            pointer-events: none;
        }

        .input-group input {
            width: 100%;
            padding: 13px 14px 13px 42px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 12px;
            color: #fff;
            font-family: 'Poppins', sans-serif;
            font-size: 0.9rem;
            outline: none;
            transition: border-color .2s, background .2s;
        }

        .input-group input::placeholder { color: rgba(255,255,255,0.4); }

        .input-group input:focus {
            border-color: #a78bfa;
            background: rgba(255,255,255,0.12);
        }

        /* Botón */
        .btn-login {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #7c3aed, #a78bfa);
            color: white;
            border: none;
            border-radius: 12px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            letter-spacing: 0.5px;
            margin-top: 6px;
            transition: opacity .2s, transform .15s, box-shadow .2s;
            box-shadow: 0 6px 20px rgba(124,58,237,0.45);
        }

        .btn-login:hover {
            opacity: 0.92;
            transform: translateY(-2px);
            box-shadow: 0 10px 28px rgba(124,58,237,0.6);
        }

        .btn-login:active { transform: translateY(0); }

        /* Divisor */
        .divider {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 20px 0 16px;
        }
        .divider hr {
            flex: 1;
            border: none;
            border-top: 1px solid rgba(255,255,255,0.12);
        }
        .divider span {
            font-size: 0.75rem;
            color: rgba(255,255,255,0.35);
            white-space: nowrap;
        }

        /* Links */
        .nav-links {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .nav-links a {
            color: rgba(196, 181, 253, 0.8);
            text-decoration: none;
            font-size: 0.83rem;
            font-weight: 400;
            transition: color .2s;
        }
        .nav-links a:hover { color: #c4b5fd; text-decoration: underline; }
    </style>
</head>
<body class="login-page">

    <div class="login-card">

        <!-- Logo animado -->
        <div class="logo-wrapper">
            <img src="https://cdn-icons-png.flaticon.com/512/2910/2910791.png" alt="Logo" class="logo-img">
        </div>

        <h1>OfficeStock Pro</h1>
        <p class="subtitle">Control de Inventario Empresarial</p>

        <?php
        if (isset($_SESSION['error'])) {
            echo '<div class="msg-error">' . htmlspecialchars($_SESSION['error']) . '</div>';
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            echo '<div class="msg-success">' . htmlspecialchars($_SESSION['success']) . '</div>';
            unset($_SESSION['success']);
        }
        ?>

        <form action="validar_login.php" method="POST">
            <div class="input-group">
                <span>👤</span>
                <input type="text" name="usuario" placeholder="Usuario" required>
            </div>
            <div class="input-group">
                <span>🔒</span>
                <input type="password" name="password" placeholder="Contraseña" required>
            </div>
            <button type="submit" class="btn-login">Iniciar Sesión</button>
        </form>

        <div class="divider">
            <hr><span>accesos</span><hr>
        </div>

        <div class="nav-links">
            <a href="recuperar_usuario.php">¿Olvidaste tu usuario?</a>
            <a href="recuperar_password.php">¿Olvidaste tu contraseña?</a>
        </div>

    </div>

</body>
</html>
