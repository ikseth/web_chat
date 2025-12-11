<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require_once __DIR__ . '/inc/auth.php';

if (!auth_user()) {
    header('Location: /public/login.php');
    exit;
}

require_once __DIR__ . '/config/users.php';
$user = auth_user();
$rol = auth_role();

$edad = '';
if (isset($USERS[$user]['fecha_nacimiento'])) {
    $nac = new DateTime($USERS[$user]['fecha_nacimiento']);
    $hoy = new DateTime();
    $edad = $hoy->diff($nac)->y;
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>My IA Portal</title>
    <link rel="stylesheet" href="/static/css/main.css">
    <link rel="stylesheet" href="/static/css/index.css">
</head>
<body>
<div id="vanta-bg"></div>
<?php include __DIR__ . '/inc/sidebar.php'; ?>
<div style="margin-left:220px; padding:24px;">
    <h2>Bienvenido a My IA Portal</h2>

	<div class="user-info-card">
	    <p><b>Usuario:</b> <?=htmlspecialchars($user)?></p>
	    <p><b>Rol:</b> <?=htmlspecialchars($rol)?></p>
	    <p><b>Edad:</b> <?=$edad ? "$edad años" : "N/D"?></p>
	</div>

    <!-- Aquí puedes poner el contenido principal de cada página -->
</div>

<script src="/static/js/libs/three.min.js"></script>
<script src="/static/js/libs/vanta.net.min.js"></script>
<script src="/static/js/vanta-init.js"></script>

</body>
</html>

