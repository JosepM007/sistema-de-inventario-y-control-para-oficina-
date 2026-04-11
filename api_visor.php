<?php
session_start();
if (!isset($_SESSION['usuario'])) { header("Location: login.php"); exit; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API REST - OfficeStock Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg:       #071e38;
            --card:     rgba(255,255,255,0.08);
            --border:   rgba(255,255,255,0.14);
            --cyan:     #00c8e8;
            --cyan-l:   #7fecf8;
            --green:    #10b981;
            --text:     #e0f4ff;
            --muted:    rgba(224,244,255,0.50);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Nunito', sans-serif;
            background: linear-gradient(135deg, #071e38 0%, #0a4a72 55%, #0a7abf 100%);
            min-height: 100vh; color: var(--text);
        }
        .layout { display: flex; min-height: 100vh; }

        .sidebar { width: 220px; flex-shrink: 0; background: rgba(0,0,0,0.32); backdrop-filter: blur(16px); border-right: 1px solid var(--border); display: flex; flex-direction: column; padding: 28px 0 24px; position: sticky; top: 0; height: 100vh; }
        .logo { font-size: 18px; font-weight: 800; color: #fff; padding: 0 22px 22px; border-bottom: 1px solid var(--border); margin-bottom: 12px; }
        .sidebar a { display: flex; align-items: center; gap: 9px; padding: 10px 22px; color: var(--muted); text-decoration: none; font-size: 13px; font-weight: 600; border-left: 3px solid transparent; transition: all .18s; }
        .sidebar a:hover  { color: #fff; background: rgba(255,255,255,0.07); }
        .sidebar a.active { color: #fff; border-left-color: var(--cyan); background: rgba(0,200,232,0.15); }
        .sidebar .logout-link { margin-top: auto; color: #fca5a5; border-top: 1px solid var(--border); padding-top: 12px; }
        .sidebar .logout-link:hover { background: rgba(239,68,68,0.14); color: #fff; }

        .main { flex: 1; display: flex; flex-direction: column; }

        .topbar { padding: 20px 28px 16px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); background: rgba(0,0,0,0.20); }
        .topbar h1 { font-size: 19px; font-weight: 800; }
        .topbar .sub { font-size: 11.5px; color: var(--muted); margin-top: 1px; }
        .api-badge { background: rgba(16,185,129,0.18); border: 1px solid rgba(16,185,129,0.30); color: #6ee7b7; border-radius: 20px; padding: 5px 14px; font-size: 12px; font-weight: 700; display:flex; align-items:center; gap:5px; }
        .pulse { width:7px; height:7px; border-radius:50%; background:#10b981; animation: pulse 1.6s infinite; }
        @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.35} }

        .body { padding: 24px 28px 40px; display: flex; flex-direction: column; gap: 20px; }

        .key-banner { background: rgba(245,158,11,0.10); border: 1px solid rgba(245,158,11,0.25); border-radius: 12px; padding: 14px 18px; display: flex; align-items: center; gap: 12px; font-size: 13.5px; }
        .key-banner strong { color: #fcd34d; }
        code { font-family: 'JetBrains Mono', monospace; background: rgba(255,255,255,0.10); border-radius: 5px; padding: 2px 8px; font-size: 13px; color: var(--cyan-l); }

        .endpoints-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 14px; }

        .ep-card { background: var(--card); border: 1px solid var(--border); border-radius: 14px; padding: 18px; cursor: pointer; transition: transform .18s, box-shadow .18s, background .18s; }
        .ep-card:hover { transform: translateY(-3px); box-shadow: 0 12px 32px rgba(0,0,0,0.25); background: rgba(255,255,255,0.12); }
        .ep-card.active { border-color: var(--cyan); background: rgba(0,200,232,0.12); }

        .ep-top { display:flex; align-items:center; gap:10px; margin-bottom:8px; }
        .method-badge { font-family: 'JetBrains Mono', monospace; font-size:11px; font-weight:700; padding:3px 9px; border-radius:5px; background: rgba(16,185,129,0.22); color:#6ee7b7; border: 1px solid rgba(16,185,129,0.28); }
        .ep-action { font-size:14px; font-weight:800; color:#fff; }
        .ep-desc { font-size:12.5px; color:var(--muted); margin-bottom:12px; }
        .ep-url { font-family: 'JetBrains Mono', monospace; font-size:11.5px; color:var(--cyan-l); background: rgba(0,200,232,0.10); border-radius:7px; padding:7px 10px; word-break:break-all; line-height:1.5; }

        .param-row { display:none; margin-top:10px; gap:8px; align-items:center; }
        .param-row.show { display:flex; }
        .param-input { flex:1; background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.16); border-radius:8px; padding:7px 11px; color:#fff; font-family:'Nunito',sans-serif; font-size:13px; outline:none; }
        .param-input:focus { border-color:var(--cyan); }
        .btn-run { background: linear-gradient(90deg,#0077b6,#00c8e8); color:#fff; border:none; border-radius:8px; padding:8px 18px; font-size:13px; font-weight:700; cursor:pointer; font-family:'Nunito',sans-serif; transition:opacity .2s; }
        .btn-run:hover { opacity:.85; }

        .response-panel { background:rgba(0,0,0,0.30); border:1px solid var(--border); border-radius:16px; overflow:hidden; }
        .resp-header { padding:13px 20px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center; background:rgba(255,255,255,0.03); }
        .resp-header h3 { font-size:14px; font-weight:700; }
        .resp-meta { display:flex; gap:10px; align-items:center; font-size:12px; color:var(--muted); }
        .status-ok  { color:#6ee7b7; font-weight:700; }
        .status-err { color:#fca5a5; font-weight:700; }
        .resp-body { padding:18px 20px; font-family:'JetBrains Mono',monospace; font-size:12.5px; line-height:1.7; max-height:480px; overflow-y:auto; white-space:pre-wrap; word-break:break-word; }
        .json-key  { color:var(--cyan-l); }
        .json-str  { color:#6ee7b7; }
        .json-num  { color:#fcd34d; }
        .json-bool { color:#f87171; }
        .json-null { color:#94a3b8; }
        .placeholder { color:var(--muted); text-align:center; padding:40px 20px; font-size:13.5px; }
        .placeholder .ico { font-size:36px; margin-bottom:10px; }
        .loading { display:none; align-items:center; gap:10px; color:var(--cyan-l); padding:20px; font-size:13.5px; }
        .loading.show { display:flex; }
        .spinner { width:18px; height:18px; border:2px solid rgba(0,200,232,0.25); border-top-color:var(--cyan-l); border-radius:50%; animation:spin .7s linear infinite; }
        @keyframes spin { to{transform:rotate(360deg)} }
        .copy-btn { background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.14); color:rgba(255,255,255,0.65); border-radius:7px; padding:4px 12px; font-size:12px; font-family:'Nunito',sans-serif; cursor:pointer; transition:background .2s, color .2s; }
        .copy-btn:hover { background:rgba(255,255,255,0.14); color:#fff; }

        @media (max-width:768px) { .sidebar{display:none;} .body{padding:14px;} }
    </style>
</head>
<body>
<div class="layout">
    <div class="sidebar">
        <div class="logo">🗂️ OfficeStock</div>
        <a href="dashboard.php">🏠 Dashboard</a>
        <?php if ($_SESSION['rol'] == 'admin'): ?>
            <a href="productos.php">📦 Productos</a>
            <a href="usuarios.php">👥 Usuarios</a>
        <?php endif; ?>
        <a href="categorias.php">📂 Categorías</a>
        <a href="proveedores.php">🏢 Proveedores</a>
        <a href="nuevo_inventario.php">📋 Inventario</a>
        <a href="salidas.php">📤 Salidas</a>
        <a href="auditoria.php">🔍 Auditoría</a>
        <a href="api_visor.php" class="active">⚡ API REST</a>
        <a href="logout.php" class="logout-link">🚪 Cerrar Sesión</a>
    </div>

    <div class="main">
        <div class="topbar">
            <div>
                <h1>⚡ API REST — OfficeStock Pro</h1>
                <div class="sub">Consulta los datos del inventario en formato JSON</div>
            </div>
            <div class="api-badge"><span class="pulse"></span> API activa</div>
        </div>

        <div class="body">
            <div class="key-banner">
                🔑 <span>API Key requerida: <strong>officestock2026</strong> — agrégala como <code>&api_key=officestock2026</code> en cada petición.</span>
            </div>

            <div>
                <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--cyan-l);margin-bottom:12px;">
                    📋 Endpoints disponibles — haz clic para probar
                </div>
                <div class="endpoints-grid">
                    <div class="ep-card" data-url="api.php?action=productos&api_key=officestock2026" data-param="">
                        <div class="ep-top"><span class="method-badge">GET</span><span class="ep-action">productos</span></div>
                        <div class="ep-desc">Devuelve todos los productos del inventario.</div>
                        <div class="ep-url">api.php?action=productos&amp;api_key=officestock2026</div>
                    </div>
                    <div class="ep-card" data-url="api.php?action=producto&id={id}&api_key=officestock2026" data-param="id">
                        <div class="ep-top"><span class="method-badge">GET</span><span class="ep-action">producto</span></div>
                        <div class="ep-desc">Detalle de un producto específico por su ID.</div>
                        <div class="ep-url">api.php?action=producto&amp;id=<strong>{id}</strong>&amp;api_key=officestock2026</div>
                        <div class="param-row" id="param-producto">
                            <input class="param-input" type="number" placeholder="ID del producto (ej: 1)" id="val-producto" min="1">
                            <button class="btn-run" onclick="consultarConParam('api.php?action=producto&id={id}&api_key=officestock2026','val-producto','id')">▶ Consultar</button>
                        </div>
                    </div>
                    <div class="ep-card" data-url="api.php?action=stats&api_key=officestock2026" data-param="">
                        <div class="ep-top"><span class="method-badge">GET</span><span class="ep-action">stats</span></div>
                        <div class="ep-desc">Estadísticas generales del inventario.</div>
                        <div class="ep-url">api.php?action=stats&amp;api_key=officestock2026</div>
                    </div>
                    <div class="ep-card" data-url="api.php?action=stock_bajo&api_key=officestock2026" data-param="">
                        <div class="ep-top"><span class="method-badge">GET</span><span class="ep-action">stock_bajo</span></div>
                        <div class="ep-desc">Productos con cantidad menor a 10 unidades.</div>
                        <div class="ep-url">api.php?action=stock_bajo&amp;api_key=officestock2026</div>
                    </div>
                    <div class="ep-card" data-url="api.php?action=proveedores&api_key=officestock2026" data-param="">
                        <div class="ep-top"><span class="method-badge">GET</span><span class="ep-action">proveedores</span></div>
                        <div class="ep-desc">Resumen de productos agrupado por proveedor.</div>
                        <div class="ep-url">api.php?action=proveedores&amp;api_key=officestock2026</div>
                    </div>
                    <div class="ep-card" data-url="api.php?action=buscar&q={q}&api_key=officestock2026" data-param="q">
                        <div class="ep-top"><span class="method-badge">GET</span><span class="ep-action">buscar</span></div>
                        <div class="ep-desc">Busca productos por nombre, descripción o proveedor.</div>
                        <div class="ep-url">api.php?action=buscar&amp;q=<strong>{q}</strong>&amp;api_key=officestock2026</div>
                        <div class="param-row" id="param-buscar">
                            <input class="param-input" type="text" placeholder="Ej: laptop" id="val-buscar">
                            <button class="btn-run" onclick="consultarConParam('api.php?action=buscar&q={q}&api_key=officestock2026','val-buscar','q')">▶ Buscar</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="response-panel">
                <div class="resp-header">
                    <h3>📄 Respuesta JSON</h3>
                    <div class="resp-meta">
                        <span id="respStatus"></span>
                        <span id="respTime"></span>
                        <button class="copy-btn" onclick="copiarJSON()">📋 Copiar</button>
                    </div>
                </div>
                <div class="loading" id="loading"><div class="spinner"></div> Consultando API...</div>
                <div class="resp-body" id="respBody">
                    <div class="placeholder"><div class="ico">🔌</div><div>Selecciona un endpoint arriba para ver la respuesta JSON.</div></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let lastJSON = '';
function highlightJSON(json) {
    return json.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
        .replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function(m) {
            let cls = 'json-num';
            if (/^"/.test(m)) cls = /:$/.test(m) ? 'json-key' : 'json-str';
            else if (/true|false/.test(m)) cls = 'json-bool';
            else if (/null/.test(m)) cls = 'json-null';
            return '<span class="'+cls+'">'+m+'</span>';
        });
}
async function consultarEndpoint(url) {
    const start = Date.now();
    document.getElementById('loading').classList.add('show');
    document.getElementById('respBody').innerHTML = '';
    document.getElementById('respStatus').textContent = '';
    document.getElementById('respTime').textContent = '';
    try {
        const res = await fetch(url);
        const text = await res.text();
        const ms = Date.now() - start;
        lastJSON = text;
        document.getElementById('loading').classList.remove('show');
        document.getElementById('respTime').textContent = ms + ' ms';
        const statusEl = document.getElementById('respStatus');
        statusEl.textContent = res.status + ' ' + (res.ok ? 'OK' : 'ERROR');
        statusEl.className = res.ok ? 'status-ok' : 'status-err';
        try { document.getElementById('respBody').innerHTML = highlightJSON(JSON.stringify(JSON.parse(text), null, 2)); }
        catch { document.getElementById('respBody').textContent = text; }
    } catch(err) {
        document.getElementById('loading').classList.remove('show');
        document.getElementById('respBody').textContent = 'Error al conectar: ' + err.message;
    }
}
function consultarConParam(urlTemplate, inputId, paramName) {
    const val = document.getElementById(inputId).value.trim();
    if (!val) { alert('Por favor ingresa un valor.'); return; }
    consultarEndpoint(urlTemplate.replace('{'+paramName+'}', encodeURIComponent(val)));
}
function copiarJSON() {
    if (!lastJSON) return;
    navigator.clipboard.writeText(lastJSON).then(() => {
        const btn = document.querySelector('.copy-btn');
        btn.textContent = '✅ Copiado';
        setTimeout(() => btn.textContent = '📋 Copiar', 2000);
    });
}
document.querySelectorAll('.ep-card').forEach(card => {
    card.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-run') || e.target.classList.contains('param-input')) return;
        document.querySelectorAll('.ep-card').forEach(c => c.classList.remove('active'));
        this.classList.add('active');
        const param = this.dataset.param;
        const url = this.dataset.url;
        document.querySelectorAll('.param-row').forEach(r => r.classList.remove('show'));
        if (param) {
            const action = url.split('action=')[1].split('&')[0];
            document.getElementById('param-' + action)?.classList.add('show');
        } else { consultarEndpoint(url); }
    });
});
</script>
</body>
</html>
