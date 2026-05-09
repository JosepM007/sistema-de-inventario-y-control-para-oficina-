<?php
session_start(); require 'db.php';
$error = ''; $mensaje = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    if (empty($email)) { $error = "Por favor ingresa tu correo electrónico."; }
    else {
        $stmt = $conn->prepare("SELECT usuario FROM usuarios WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email); $stmt->execute(); $result = $stmt->get_result();
        if ($result->num_rows === 1) { $mensaje = $result->fetch_assoc()['usuario']; }
        else { $error = "No existe ninguna cuenta con ese correo electrónico."; }
        $stmt->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Usuario - OfficeStock Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Nunito',sans-serif;min-height:100vh;background:linear-gradient(135deg,#071e38 0%,#0a4a72 45%,#0077b6 75%,#00b4d8 100%);display:flex;align-items:center;justify-content:center;padding:30px 16px}
        .wrap{width:100%;max-width:440px;display:flex;flex-direction:column;align-items:center;gap:20px}
        .brand{text-align:center}.brand .logo-icon{font-size:40px;display:block;margin-bottom:6px}
        .brand h1{font-size:20px;font-weight:800;color:#fff}.brand p{font-size:12.5px;color:rgba(255,255,255,0.50);margin-top:2px}
        .card{width:100%;background:rgba(255,255,255,0.10);border:1px solid rgba(255,255,255,0.18);border-radius:22px;backdrop-filter:blur(18px);box-shadow:0 24px 64px rgba(0,0,0,0.32);overflow:hidden}
        .card-header{background:linear-gradient(135deg,rgba(0,119,182,0.55),rgba(0,180,216,0.35));border-bottom:1px solid rgba(255,255,255,0.14);padding:24px 28px 20px;display:flex;align-items:center;gap:14px}
        .header-icon{width:48px;height:48px;border-radius:13px;background:linear-gradient(135deg,#0077b6,#00c8e8);display:flex;align-items:center;justify-content:center;font-size:22px;box-shadow:0 6px 18px rgba(0,0,0,0.22);flex-shrink:0}
        .card-header h2{font-size:18px;font-weight:800;color:#fff}.card-header p{font-size:12px;color:rgba(255,255,255,0.50);margin-top:2px}
        .card-body{padding:26px 28px 24px}
        .alert{display:flex;align-items:flex-start;gap:10px;padding:13px 16px;border-radius:11px;font-size:13.5px;font-weight:600;margin-bottom:20px;line-height:1.45}
        .alert-err{background:rgba(239,68,68,0.16);border:1px solid rgba(239,68,68,0.32);color:#fca5a5}
        .result-box{background:rgba(0,200,232,0.10);border:1px solid rgba(0,200,232,0.28);border-radius:14px;padding:20px 22px;margin-bottom:20px;text-align:center}
        .result-box .result-label{font-size:12px;color:rgba(255,255,255,0.50);text-transform:uppercase;letter-spacing:.8px;font-weight:700;margin-bottom:8px}
        .result-box .result-user{font-size:26px;font-weight:800;color:#7fecf8;letter-spacing:-.5px}
        .result-box .result-sub{font-size:12px;color:rgba(255,255,255,0.40);margin-top:6px}
        .result-box .avatar{width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,#0077b6,#00c8e8);display:flex;align-items:center;justify-content:center;font-size:26px;margin:0 auto 12px;box-shadow:0 6px 20px rgba(0,119,182,0.32)}
        .field{margin-bottom:16px}
        .field label{display:block;font-size:11.5px;font-weight:700;color:rgba(255,255,255,0.68);text-transform:uppercase;letter-spacing:.7px;margin-bottom:7px}
        .field label .req{color:#7fecf8;margin-left:2px}
        .input-wrap{position:relative}.input-wrap .ico{position:absolute;left:13px;top:50%;transform:translateY(-50%);font-size:15px;pointer-events:none;opacity:.40}
        .input-wrap input{width:100%;padding:11px 14px 11px 38px;background:rgba(255,255,255,0.10);border:1px solid rgba(255,255,255,0.18);border-radius:10px;color:#fff;font-family:'Nunito',sans-serif;font-size:14px;outline:none;transition:border-color .2s,background .2s,box-shadow .2s}
        .input-wrap input::placeholder{color:rgba(255,255,255,0.28)}
        .input-wrap input:focus{border-color:#00c8e8;background:rgba(255,255,255,0.15);box-shadow:0 0 0 3px rgba(0,200,232,0.18)}
        .field-hint{font-size:11px;color:rgba(255,255,255,0.30);margin-top:5px;padding-left:2px}
        .divider{border:none;border-top:1px solid rgba(255,255,255,0.10);margin:20px 0}
        .btn-submit{width:100%;padding:12px;background:linear-gradient(135deg,#0077b6,#00c8e8);color:#fff;border:none;border-radius:11px;font-family:'Nunito',sans-serif;font-size:14px;font-weight:800;cursor:pointer;box-shadow:0 6px 20px rgba(0,119,182,0.38);transition:opacity .2s,transform .15s}
        .btn-submit:hover{opacity:.88;transform:translateY(-1px)}
        .btn-login{display:block;width:100%;padding:12px;margin-top:10px;background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.16);border-radius:11px;color:rgba(255,255,255,0.72);font-family:'Nunito',sans-serif;font-size:14px;font-weight:600;text-align:center;text-decoration:none;transition:background .2s,color .2s}
        .btn-login:hover{background:rgba(255,255,255,0.14);color:#fff}
        .back-link{display:block;text-align:center;margin-top:16px;font-size:13px;color:rgba(255,255,255,0.50);text-decoration:none;transition:color .2s}
        .back-link:hover{color:#fff}.back-link span{color:#7fecf8;font-weight:700}
        .help-links{display:flex;justify-content:center;gap:20px;margin-top:4px}
        .help-links a{font-size:12px;color:rgba(255,255,255,0.38);text-decoration:none;transition:color .2s}.help-links a:hover{color:#7fecf8}
        .footer-note{font-size:11.5px;color:rgba(255,255,255,0.26);text-align:center}
    
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
<div class="wrap">
    <div class="brand"><span class="logo-icon">🗂️</span><h1>OfficeStock Pro</h1><p>Sistema de Inventario y Control Logístico</p></div>
    <div class="card">
        <div class="card-header"><div class="header-icon">👤</div><div><h2>Recuperar Usuario</h2><p>Ingresa tu correo y te mostramos tu usuario</p></div></div>
        <div class="card-body">
            <?php if ($error): ?><div class="alert alert-err">⚠️ <?php echo htmlspecialchars($error); ?></div><?php endif; ?>
            <?php if ($mensaje): ?>
                <div class="result-box"><div class="avatar">😊</div><div class="result-label">Tu nombre de usuario es</div><div class="result-user"><?php echo htmlspecialchars($mensaje); ?></div><div class="result-sub">Usa este nombre para iniciar sesión en el sistema.</div></div>
                <a href="login.php" class="btn-login">🔐 Ir al Login</a>
                <a href="recuperar_password.php" class="back-link">¿Olvidaste tu contraseña? <span>Recupérala aquí</span></a>
            <?php else: ?>
                <form method="POST" action="">
                    <div class="field"><label>Correo Electrónico <span class="req">*</span></label><div class="input-wrap"><span class="ico">✉️</span><input type="email" name="email" placeholder="tu@email.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required autofocus></div><div class="field-hint">Ingresa el correo con el que te registraste.</div></div>
                    <hr class="divider">
                    <button type="submit" class="btn-submit">🔍 Buscar mi Usuario</button>
                </form>
                <a class="back-link" href="login.php">← Volver al <span>inicio de sesión</span></a>
                <div class="help-links" style="margin-top:14px;"><a href="recuperar_password.php">🔑 Olvidé mi contraseña</a></div>
            <?php endif; ?>
        </div>
    </div>
    <div class="footer-note">OfficeStock Pro · Acceso seguro</div>
</div>

<script src="js/voz.js"></script>
<script>
/* ── Lectura de página: Recuperar Usuario ── */
VOZ.hablarPagina = function() {
    window.speechSynthesis && window.speechSynthesis.cancel();
    
    ['Página para recuperar tu nombre de usuario.',
     'Ingresa tu correo electrónico registrado y presiona el botón para continuar.',
     'Fin.'].forEach(t => VOZ.hablar(t, true));

};
/* Anunciar alertas automáticamente */
document.querySelectorAll('[role="alert"]').forEach(el => {
    if (el.textContent.trim()) setTimeout(() => VOZ.hablar(el.textContent.trim(), true), 600);
});
</script>
</body>
</html>
