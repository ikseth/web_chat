<?php
require_once __DIR__ . '/../../../inc/auth.php';
require_login();

if (auth_role() !== 'admin') {
    die("Acceso restringido.");
}

$roles_file = __DIR__ . '/../../../config/roles.json';
$experts_file = __DIR__ . '/../config/experts.json';

// Leer datos actuales
$roles = json_decode(file_get_contents($roles_file), true);
$experts = json_decode(file_get_contents($experts_file), true);

$formats_full = ["html" => "HTML", "texto plano" => "Texto plano", "markdown" => "Markdown"];
$experts_dict = [];
foreach ($experts as $e) $experts_dict[$e['id']] = $e['nombre'];

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Administrar Roles y Atributos</title>
    <link rel="stylesheet" href="/static/css/main.css">
    <link rel="stylesheet" href="/static/css/admin.css">
</head>
<body>
<?php include __DIR__ . '/../../../inc/sidebar.php'; ?>
<div class="main-content">
    <h2>Administración de Roles y Atributos</h2>
    <form id="roles-form" method="post" action="save_roles.php">
    <table class="admin-table users-table">
        <thead>
            <tr>
                <th>Rol</th>
                <th>Nombre</th>
                <th>Expertos Permitidos</th>
                <th>Formatos Permitidos</th>
                <th>Prompt Base</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($roles as $idx => $rol): ?>
            <tr>
                <td>
                    <input type="text" name="roles[<?= $idx ?>][id]" value="<?= htmlspecialchars($rol['id']) ?>" required>
                </td>
                <td>
                    <input type="text" name="roles[<?= $idx ?>][nombre]" value="<?= htmlspecialchars($rol['nombre']) ?>" required>
                </td>
                <td>
                    <div class="attr-list">
                    <?php foreach ($experts as $ex): ?>
                        <label class="attr-chip">
                            <input type="checkbox" name="roles[<?= $idx ?>][expertos_permitidos][]"
                                value="<?= $ex['id'] ?>"
                                <?= in_array($ex['id'], $rol['expertos_permitidos']) ? 'checked' : '' ?>>
                            <?= htmlspecialchars($ex['nombre']) ?>
                        </label>
                    <?php endforeach; ?>
                    </div>
                </td>
                <td>
                    <div class="attr-list">
                    <?php foreach ($formats_full as $k => $v): ?>
                        <label class="attr-chip">
                            <input type="checkbox" name="roles[<?= $idx ?>][formatos_permitidos][]"
                                value="<?= $k ?>"
                                <?= in_array($k, $rol['formatos_permitidos']) ? 'checked' : '' ?>>
                            <?= htmlspecialchars($v) ?>
                        </label>
                    <?php endforeach; ?>
                    </div>
                </td>
                <td>
                    <input type="text" name="roles[<?= $idx ?>][prompt_base]" value="<?= htmlspecialchars($rol['prompt_base']) ?>">
                </td>
                <td>
                    <?php if ($rol['id'] !== 'admin'): ?>
                        <button type="submit" name="delete" value="<?= $rol['id'] ?>" class="admin-btn admin-btn-del" onclick="return confirm('¿Eliminar este rol?')">Eliminar</button>
                    <?php else: ?>
                        <span>Protegido</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <!-- Nueva entrada -->
        <tr>
            <td><input type="text" name="new[id]" placeholder="nuevo_rol"></td>
            <td><input type="text" name="new[nombre]" placeholder="Nombre"></td>
            <td>
                <div class="attr-list">
                <?php foreach ($experts as $ex): ?>
                    <label class="attr-chip">
                        <input type="checkbox" name="new[expertos_permitidos][]" value="<?= $ex['id'] ?>">
                        <?= htmlspecialchars($ex['nombre']) ?>
                    </label>
                <?php endforeach; ?>
                </div>
            </td>
            <td>
                <div class="attr-list">
                <?php foreach ($formats_full as $k => $v): ?>
                    <label class="attr-chip">
                        <input type="checkbox" name="new[formatos_permitidos][]" value="<?= $k ?>">
                        <?= htmlspecialchars($v) ?>
                    </label>
                <?php endforeach; ?>
                </div>
            </td>
            <td>
                <input type="text" name="new[prompt_base]" placeholder="Prompt base">
            </td>
            <td>
                <button type="submit" name="add" value="1" class="admin-btn admin-btn-add">Añadir</button>
            </td>
        </tr>
        </tbody>
    </table>
    <button type="submit" name="save" value="1" class="admin-btn admin-btn-save">Guardar Cambios</button>
    </form>
</div>
</body>
</html>

