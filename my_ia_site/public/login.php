<?php
require_once __DIR__ . '/../inc/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['user'] ?? '';
    $pass = $_POST['pass'] ?? '';
    if (auth_login($user, $pass)) {
        header('Location: /index.php');
        exit;
    } else {
        $error = "Login incorrecto";
    }
}

if (isset($_GET['login_required'])) echo "<div style='color:red'>Debes iniciar sesión</div>";
if (isset($_GET['forbidden'])) echo "<div style='color:red'>Acceso denegado</div>";
if (isset($error)) echo "<div>$error</div>";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Acceso - My IA Portal</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="/static/css/main.css">
</head>
<body>
    <div class="main-content login-container">
        <h2>Acceso al portal</h2>
        <?php if (!empty($error)): ?>
            <div class="alert"><?= $error ?></div>
        <?php endif; ?>
        <form method="post" autocomplete="off">
            <label for="user">Usuario:</label>
            <input id="user" class="user-input" type="text" name="user" required autofocus>
            <label for="pass">Contraseña:</label>
            <input id="pass" class="user-input" type="password" name="pass" required>
            <button type="submit" class="btn">Entrar</button>
        </form>
    </div>
</body>
</html>

