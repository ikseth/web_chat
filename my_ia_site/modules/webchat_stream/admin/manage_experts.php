<?php
require_once __DIR__ . '/../../../inc/auth.php';
require_login();
if (auth_role() !== 'admin') { die("Acceso restringido."); }

$experts_file = __DIR__ . '/../config/experts.json';

// Leer lista de expertos
$experts = [];
if (file_exists($experts_file)) {
    $experts = json_decode(file_get_contents($experts_file), true);
}

// Gestión de acciones (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Editar existentes
    if (isset($_POST['experts'])) {
        foreach ($_POST['experts'] as $idx => $exp) {
            $experts[$idx]['id']     = trim($exp['id']);
            $experts[$idx]['nombre'] = trim($exp['nombre']);
            $experts[$idx]['prompt'] = trim($exp['prompt']);
        }
    }
    // Eliminar
    if (isset($_POST['delete']) && $_POST['delete'] !== '') {
        $del_id = $_POST['delete'];
        $experts = array_values(array_filter($experts, function($e) use ($del_id) {
            return $e['id'] !== $del_id;
        }));
    }
    // Añadir nuevo
    if (isset($_POST['new']) && $_POST['new']['id'] !== '') {
        $experts[] = [
            "id"     => trim($_POST['new']['id']),
            "nombre" => trim($_POST['new']['nombre']),
            "prompt" => trim($_POST['new']['prompt']),
        ];
    }
    // Guardar JSON
    file_put_contents($experts_file, json_encode($experts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    header("Location: manage_experts.php?saved=1");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Administrar Expertos</title>
    <link rel="stylesheet" href="/static/css/main.css">
    <link rel="stylesheet" href="/static/css/admin.css">
    <style>
        .admin-table th, .admin-table td { padding: 7px 8px; text-align: left; }
        .admin-table th { background: #f8fafc; }
        .admin-btn { padding: 5px 12px; border-radius: 6px; border: none; cursor: pointer; margin-right: 6px; }
        .admin-btn-save { background: #3498db; color: #fff; }
        .admin-btn-del { background: #f87b7b; color: #fff; }
        .admin-btn-add { background: #8ec67e; color: #fff; }
        .attr-prompt { width: 99%; font-size: 0.97em; border-radius: 6px; padding: 5px 6px; }
        .idfield { width: 90px; }
        .namefield { width: 160px; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../../../inc/sidebar.php'; ?>
<div class="main-content">
    <h2>Administrar Expertos</h2>
    <?php if (isset($_GET['saved'])): ?>
        <div class="alert">¡Cambios guardados!</div>
    <?php endif; ?>
    <form method="post" autocomplete="off">
    <table class="admin-table users-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Prompt</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($experts as $idx => $exp): ?>
            <tr>
                <td>
                    <input class="idfield" type="text" name="experts[<?= $idx ?>][id]" value="<?= htmlspecialchars($exp['id']) ?>" required>
                </td>
                <td>
                    <input class="namefield" type="text" name="experts[<?= $idx ?>][nombre]" value="<?= htmlspecialchars($exp['nombre']) ?>" required>
                </td>
                <td>
                    <input class="attr-prompt" type="text" name="experts[<?= $idx ?>][prompt]" value="<?= htmlspecialchars($exp['prompt']) ?>">
                </td>
                <td>
                    <?php if ($exp['id'] !== 'general'): ?>
                    <button class="admin-btn admin-btn-del" type="submit" name="delete" value="<?= htmlspecialchars($exp['id']) ?>" onclick="return confirm('¿Eliminar este experto?');">Eliminar</button>
                    <?php else: ?>
                        <span style="color:gray;">Protegido</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <!-- Nuevo experto -->
            <tr>
                <td><input class="idfield" type="text" name="new[id]" placeholder="id_nuevo"></td>
                <td><input class="namefield" type="text" name="new[nombre]" placeholder="Nombre"></td>
                <td><input class="attr-prompt" type="text" name="new[prompt]" placeholder="Prompt..."></td>
                <td><button class="admin-btn admin-btn-add" type="submit" name="add" value="1">Añadir</button></td>
            </tr>
        </tbody>
    </table>
    <button type="submit" name="save" value="1" class="admin-btn admin-btn-save">Guardar Cambios</button>
    </form>
</div>
</body>
</html>

