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
<body class="login-page">
    <a class="skip-link" href="#login-form">Saltar al formulario</a>
<div class="voz-bar" style="max-width:400px;margin:0 auto 16px;" role="region" aria-label="Asistente de voz">
    <span class="voz-title">♿ Asistente de Voz</span>
    <span class="voz-status" id="vozStatus" aria-live="polite">Listo</span>
    <div class="voz-btns">
        <button class="btn-voz bv-green" onclick="VOZ.hablarPagina()" aria-label="Leer instrucciones de login">🔊 Leer</button>
        <button class="btn-voz bv-red"   onclick="VOZ.detener()" aria-label="Detener voz">⏹</button>
    </div>
</div>
<div class="login-card" id="login-form">
        <div class="logo-wrapper">
            <img src="https://cdn-icons-png.flaticon.com/512/2910/2910791.png" alt="Logo" class="logo-img">
        </div>
        <h1>OfficeStock Pro</h1>
        <p class="subtitle">Control de Inventario Empresarial</p>

        <?php
        if (isset($_SESSION['error']))   { echo '<div class="msg-error">'   . htmlspecialchars($_SESSION['error'])   . '</div>'; unset($_SESSION['error']); }
        if (isset($_SESSION['success'])) { echo '<div class="msg-success">' . htmlspecialchars($_SESSION['success']) . '</div>'; unset($_SESSION['success']); }
        ?>

        <form id="login-form-inner" action="validar_login.php" method="POST">
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

<script src="js/voz.js"></script>
<script>
/* ── Lectura de página: Login ── */
VOZ.hablarPagina = function() {
    window.speechSynthesis && window.speechSynthesis.cancel();
    
    [
        'Pantalla de inicio de sesión de OfficeStock Pro.',
        'Ingresa tu nombre de usuario en el primer campo y tu contraseña en el segundo.',
        'Luego presiona el botón Iniciar Sesión.',
        'Si olvidaste tu usuario o contraseña, usa los enlaces en la parte inferior.',
        'Fin.'
    ].forEach(t => VOZ.hablar(t, true));

};
/* Anunciar alertas automáticamente */
document.querySelectorAll('[role="alert"]').forEach(el => {
    if (el.textContent.trim()) setTimeout(() => VOZ.hablar(el.textContent.trim(), true), 600);
});
</script>
</body>
</html>
