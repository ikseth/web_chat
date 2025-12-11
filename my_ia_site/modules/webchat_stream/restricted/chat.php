<?php
require_once __DIR__ . '/../../../inc/auth.php';
require_login();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Webchat IA - My IA Portal</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="/static/css/main.css">
    <link rel="stylesheet" href="/modules/webchat_stream/assets/style.css">
    <script>
        window.USER_ROLE = "<?= htmlspecialchars(auth_role()) ?>";
    </script>
</head>
<body>
<div id="vanta-bg"></div>
<?php include __DIR__ . '/../../../inc/sidebar.php'; ?>
<div class="main-content">
    <div id="chat-app">
        <h2>Webchat IA (Streaming)</h2>
        <div id="chat-window" class="chat-window">
            <!-- Aquí se mostrarán los mensajes -->
        </div>

        <form id="chat-form" class="chat-form" autocomplete="off">
            <label for="format-select">Formato:</label>
            <select id="format-select">
                <option value="html">HTML</option>
                <option value="texto plano">Texto plano</option>
                <option value="markdown">Markdown</option>
            </select>

            <label for="expert-select">Experto:</label>
            <select id="expert-select">
                <option value="general">General</option>
                <!-- El resto se carga por JS -->
            </select>

            <input type="text" id="user-input" class="user-input" placeholder="Escribe tu pregunta..." required>
            <button type="button" id="lang-es" class="btn-lang active" title="Español">🇪🇸</button>
            <button type="button" id="lang-en" class="btn-lang" title="English">🇬🇧</button>
            <button type="submit" class="btn">Enviar</button>
        </form>
    </div>
</div>

<script src="/modules/webchat_stream/assets/chat.js"></script>
<script src="/static/js/libs/three.min.js"></script>
<script src="/static/js/libs/vanta.net.min.js"></script>
<script src="/static/js/vanta-init.js"></script>
</body>
</html>

