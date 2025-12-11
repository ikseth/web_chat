<?php
require_once __DIR__ . '/../inc/auth.php';
require_login();

// Ruta al JSON
$JSON_FILE = __DIR__ . '/../config/mpaa_levels.json';

// Funciones inline
function load_mpaa() {
    global $JSON_FILE;
    return json_decode(file_get_contents($JSON_FILE), true) ?? [];
}
function save_mpaa($data) {
    global $JSON_FILE;
    file_put_contents($JSON_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
function get_prompt_base() {
    return "Este es un prompt base genérico para todas las categorías de control parental.";
}
function save_prompt_base($newBase) {
    // Si quieres guardar este valor en un archivo diferente, adapta aquí
}
function generar_prompt($cat, $base) {
    return $base . "\n\nDescripción: " . $cat['descripcion'] . "\n\nDirectrices:\n- " . implode("\n- ", $cat['directrices']);
}

// ---- Estado inicial
$levels = load_mpaa();
$prompt_base = get_prompt_base();
$msg = '';

// ---- Gestión POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['guardar_base'])) {
        $prompt_base = $_POST['prompt_base'] ?? $prompt_base;
        save_prompt_base($prompt_base);
        $msg = "Prompt base global actualizado.";
    }

    if (isset($_POST['id'])) {
        $cat_id = $_POST['id'];
        foreach ($levels as &$cat) {
            if ($cat['id'] === $cat_id) {
                $cat['edad_min'] = intval($_POST['edad_min'] ?? $cat['edad_min']);
                $cat['nombre'] = $_POST['nombre'] ?? $cat['nombre'];
                $cat['descripcion'] = $_POST['descripcion'] ?? $cat['descripcion'];
                $cat['directrices'] = array_filter(array_map('trim', explode("\n", $_POST['directrices'] ?? '')));

                if (isset($_POST['generar_prompt'])) {
                    $cat['prompt'] = generar_prompt($cat, $prompt_base);
                    $msg = "Prompt generado para la categoría {$cat['id']}.";
                }
                if (isset($_POST['guardar_prompt'])) {
                    $cat['prompt'] = $_POST['prompt'] ?? '';
                    $msg = "Cambios guardados en la categoría {$cat['id']}.";
                }
                break;
            }
        }
        unset($cat);
        save_mpaa($levels);
        $levels = load_mpaa(); // recargar
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Control Parental (MPAA) - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/static/css/main.css">
    <link rel="stylesheet" href="/static/css/admin.css">
</head>
<body>
<div id="vanta-bg"></div>
<?php include __DIR__ . '/../inc/sidebar.php'; ?>
<div class="main-content">
    <h2>Control Parental MPAA</h2>
    <?php if ($msg): ?><div class="alert"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

    <div class="tab-header-container">
        <?php foreach ($levels as $i => $cat): ?>
            <div class="tab-header<?= $i === 0 ? ' active' : '' ?>" data-tab="tab<?=$cat['id']?>">
                <?= htmlspecialchars($cat['nombre']) ?>
            </div>
        <?php endforeach; ?>
    </div>

    <?php foreach ($levels as $i => $cat): ?>
        <div class="tab-content<?= $i === 0 ? ' active' : '' ?>" id="tab<?=$cat['id']?>">
            <form method="post" class="user-form">
                <input type="hidden" name="id" value="<?= htmlspecialchars($cat['id']) ?>">
                <label>Nombre:</label>
                <input type="text" name="nombre" value="<?= htmlspecialchars($cat['nombre']) ?>">

                <label>Edad mínima:</label>
                <input type="number" name="edad_min" value="<?= intval($cat['edad_min']) ?>">

                <label>Descripción:</label>
                <input type="text" name="descripcion" value="<?= htmlspecialchars($cat['descripcion']) ?>">

                <label>Directrices:</label>
                <textarea name="directrices" rows="4"><?= htmlspecialchars(implode("\n", $cat['directrices'])) ?></textarea>

                <label>Prompt:</label>
                <textarea name="prompt" rows="4"><?= htmlspecialchars($cat['prompt']) ?></textarea>

                <button class="btn" type="submit" name="guardar_prompt">Guardar</button>
                <button class="btn-secondary" type="submit" name="generar_prompt">Regenerar Prompt</button>
            </form>
        </div>
    <?php endforeach; ?>
</div>

<script src="/static/js/mpaa-tabs.js"></script>
<script src="/static/js/libs/three.min.js"></script>
<script src="/static/js/libs/vanta.net.min.js"></script>
<script src="/static/js/vanta-init.js"></script>
</body>
</html>

