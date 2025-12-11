<?php
require_once __DIR__ . '/../inc/auth.php';
require_role('admin');

$users_file = __DIR__ . '/../config/users.php';

// Cargar usuarios
function get_users() {
    return include __DIR__ . '/../config/users.php';
}

// Guardar usuarios
function save_users($users) {
    $out = "<?php\nreturn [\n";
    foreach ($users as $u => $data) {
        $passhash = addslashes($data['pass']);
        $role = addslashes($data['rol']);
        $fecha = addslashes($data['fecha_nacimiento'] ?? '');
        $out .= "    '$u' => ['pass' => '$passhash', 'rol' => '$role', 'fecha_nacimiento' => '$fecha'],\n";
    }
    $out .= "];\n";
    file_put_contents(__DIR__ . '/../config/users.php', $out);
}

// Alta usuario
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $uname = trim($_POST['user'] ?? '');
    $pwd = $_POST['pass'] ?? '';
    $role = $_POST['role'] ?? 'user';
    if ($uname && $pwd) {
        $users = get_users();
        if (!isset($users[$uname])) {
            $users[$uname] = [
                'pass' => password_hash($pwd, PASSWORD_DEFAULT),
                'rol' => $role,
                'fecha_nacimiento' => $_POST['fecha_nacimiento'] ?? ''
                ];
            save_users($users);
            $msg = "Usuario <b>$uname</b> creado correctamente.";
        } else {
            $msg = "El usuario <b>$uname</b> ya existe.";
        }
    } else {
        $msg = "Debe rellenar todos los campos.";
    }
}

// Baja usuario
if (isset($_GET['del'])) {
    $uname = $_GET['del'];
    $users = get_users();
    if (isset($users[$uname]) && $uname !== 'admin') {
        unset($users[$uname]);
        save_users($users);
        $msg = "Usuario <b>$uname</b> eliminado.";
    } else {
        $msg = "No se puede eliminar el usuario principal.";
    }
}

// Cambio de clave
if (isset($_POST['action']) && $_POST['action'] === 'changepass') {
    $uname = $_POST['user'] ?? '';
    $newpass = $_POST['newpass'] ?? '';
    if ($uname && $newpass) {
        $users = get_users();
        if (isset($users[$uname])) {
            $users[$uname]['pass'] = password_hash($newpass, PASSWORD_DEFAULT);
            save_users($users);
            $msg = "Contraseña actualizada para <b>$uname</b>.";
        } else {
            $msg = "Usuario no encontrado.";
        }
    } else {
        $msg = "Usuario o nueva clave no válidos.";
    }
}

// Editar datos usuario
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $uname = $_POST['user'];
    $role = $_POST['role'];
    $fecha_nacimiento = $_POST['fecha_nacimiento'] ?? '';
    $users = get_users();
    if (isset($users[$uname])) {
        $users[$uname]['rol'] = $role;
        $users[$uname]['fecha_nacimiento'] = $fecha_nacimiento;
        save_users($users);
        $msg = "Datos actualizados para <b>$uname</b>.";
    }
}

$users = get_users();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Administración de Usuarios</title>
    <link rel="stylesheet" href="/static/css/main.css">
    <link rel="stylesheet" href="/static/css/admin.css">
</head>
<body>
<div id="vanta-bg"></div>
<?php include __DIR__ . '/../inc/sidebar.php'; ?>
<div class="main-content">
    <h2>Administración de Usuarios</h2>
    <?php if (isset($msg)): ?>
        <div class="alert"><?= $msg ?></div>
    <?php endif; ?>

    <h3>Usuarios registrados</h3>
    <table class="users-table">
        <thead>
            <tr>
                <th>Usuario</th>
                <th>Rol</th>
                <th>Fecha de nacimiento</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u => $data): ?>
                <tr>
                    <td><?= htmlspecialchars($u) ?></td>
                    <td>
                        <form method="post" class="inline-form" style="margin:0;">
                            <input type="hidden" name="user" value="<?= htmlspecialchars($u) ?>">
                            <select name="role" class="user-role" <?= $u === 'admin' ? 'disabled' : '' ?>>
                                <option value="admin" <?= ($data['rol'] ?? '') === 'admin' ? 'selected' : '' ?>>admin</option>
                                <option value="user" <?= ($data['rol'] ?? '') === 'user' ? 'selected' : '' ?>>user</option>
                            </select>
                    </td>
                    <td>
                        <input type="date" name="fecha_nacimiento" value="<?= htmlspecialchars($data['fecha_nacimiento'] ?? '') ?>" class="user-input" required>
                    </td>
                    <td style="white-space:nowrap;">
                        <?php if ($u !== 'admin'): ?>
                            <button type="submit" name="action" value="edit" class="btn btn-secondary">Guardar</button>
                            <a class="btn btn-danger" href="?del=<?= urlencode($u) ?>" onclick="return confirm('¿Seguro que quieres eliminar este usuario?');">Eliminar</a>
                        <?php else: ?>
                            <span style="color:#aaa;">(principal)</span>
                        <?php endif; ?>
                        </form>
                        <form method="post" class="inline-form" style="display:inline;margin-left:6px;">
                            <input type="hidden" name="user" value="<?= htmlspecialchars($u) ?>">
                            <input type="hidden" name="action" value="changepass">
                            <input type="password" name="newpass" placeholder="Nueva clave" style="width:110px;" required>
                            <button type="submit" class="btn btn-warning" style="margin-left:3px;">Cambiar clave</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h3>Nuevo usuario</h3>
    <form method="post" class="user-form" autocomplete="off">
        <input type="hidden" name="action" value="add">
        <label>Usuario:</label>
        <input type="text" name="user" class="user-input" required>
        <label>Contraseña:</label>
        <input type="password" name="pass" class="user-input" required>
        <label>Rol:</label>
        <select name="role" class="user-role">
            <option value="user">user</option>
            <option value="admin">admin</option>
        </select>
        <label>Fecha de nacimiento:</label>
        <input type="date" name="fecha_nacimiento" class="user-input" required>
        <button type="submit" class="btn">Crear usuario</button>
    </form>
</div>
<script src="/static/js/vanta-init.js"></script>
</body>
</html>

