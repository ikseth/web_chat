<?php
require_once __DIR__ . '/../../../inc/auth.php';
require_login();
if (auth_role() !== 'admin') { die("Acceso restringido."); }

$log_dir = __DIR__ . '/../../../logs/';
$logs = [];
$logfiles = [];

// Listar logs disponibles
foreach (glob($log_dir . 'webchat_history-*.log') as $file) {
    $logfiles[] = basename($file);
}
sort($logfiles);
$current_log = isset($_GET['file']) && in_array($_GET['file'], $logfiles) ? $_GET['file'] : (end($logfiles) ?: null);

// Leer el log actual seleccionado
if ($current_log) {
    $fp = fopen($log_dir . $current_log, "r");
    while (($line = fgets($fp)) !== false) {
        $json = json_decode($line, true);
        if ($json) $logs[] = $json;
    }
    fclose($fp);
}

// Filtros
$filtro_usuario = isset($_GET['usuario']) ? trim($_GET['usuario']) : "";
$filtro_rol = isset($_GET['rol']) ? trim($_GET['rol']) : "";

// Para selects de filtro
$usuarios = array_unique(array_map(function($l){return $l['usuario'] ?? '';}, $logs));
$roles = array_unique(array_map(function($l){return $l['rol'] ?? '';}, $logs));
sort($usuarios); sort($roles);

function matches_filter($log, $filtro_usuario, $filtro_rol) {
    if ($filtro_usuario && (!isset($log['usuario']) || stripos($log['usuario'], $filtro_usuario) === false)) return false;
    if ($filtro_rol && (!isset($log['rol']) || stripos($log['rol'], $filtro_rol) === false)) return false;
    return true;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Histórico de logs - Webchat IA</title>
    <link rel="stylesheet" href="/static/css/main.css">
    <link rel="stylesheet" href="/static/css/admin.css">
</head>
<body>
<?php include __DIR__ . '/../../../inc/sidebar.php'; ?>
<div class="main-content">
    <h2>Histórico de logs Webchat IA</h2>
    <form method="get" class="logs-filtros">
        <label>Log:
            <select name="file" onchange="this.form.submit()">
                <?php foreach ($logfiles as $lf): ?>
                    <option value="<?= htmlspecialchars($lf) ?>" <?= $lf == $current_log ? 'selected' : '' ?>>
                        <?= htmlspecialchars($lf) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Usuario:
            <select name="usuario" onchange="this.form.submit()">
                <option value="">[Todos]</option>
                <?php foreach ($usuarios as $u): if (!$u) continue;?>
                    <option value="<?= htmlspecialchars($u) ?>" <?= $filtro_usuario === $u ? 'selected' : '' ?>>
                        <?= htmlspecialchars($u) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Rol:
            <select name="rol" onchange="this.form.submit()">
                <option value="">[Todos]</option>
                <?php foreach ($roles as $r): if (!$r) continue;?>
                    <option value="<?= htmlspecialchars($r) ?>" <?= $filtro_rol === $r ? 'selected' : '' ?>>
                        <?= htmlspecialchars($r) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <input type="submit" value="Limpiar filtros">
    </form>

    <table class="logs-table">
        <thead>
            <tr>
		<th>Fecha</th>
		<th>Usuario</th>
		<th>Rol</th>
		<th>MPAA</th>
		<th>Prompt</th>
		<th>Respuesta</th>
		<th>Filtros</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $MAX_ROWS = 200; // para no cargar logs inmanejables
        $count = 0;
        foreach (array_reverse($logs) as $log):
            if (!matches_filter($log, $filtro_usuario, $filtro_rol)) continue;
            $count++;
            if ($count > $MAX_ROWS) break;
        ?>
            <tr>
                <td><?= htmlspecialchars($log['fecha'] ?? '') ?></td>
                <td><span class="tag-user"><?= htmlspecialchars($log['usuario'] ?? '') ?></span></td>
                <td><span class="tag-role"><?= htmlspecialchars($log['rol'] ?? '') ?></span></td>
		<td>
		<?php
		    if (isset($log['mpaa'])) {
			$nivel = $log['mpaa']['nivel'] ?? '';
			$edad = $log['mpaa']['edad'] ?? '';
			$tooltip = implode("\n", $log['mpaa']['directrices'] ?? []);
			echo "<span class='tag-fil' title=\"" . htmlspecialchars($tooltip) . "\">{$nivel} ({$edad} años)</span>";
		    } else {
			echo "<span class='tag-fil'>N/A</span>";
		    }
		?>
		</td>
		<td class="log-prompt">
		    <?php
		    $prompt_raw = $log['prompt'] ?? '';
		    $tags = [];
		    $contenido = preg_replace_callback('/\[(\w+)=([^\]]+)\]/', function($m) use (&$tags) {
			$tags[] = ['clave' => $m[1], 'valor' => $m[2]];
			return '';
		    }, $prompt_raw);
		    $contenido = trim($contenido); // eliminar líneas vacías al inicio/final

		    foreach ($tags as $tag) {
			echo "<span class='tag-opcion'>" . htmlspecialchars($tag['clave']) . ": " . htmlspecialchars($tag['valor']) . "</span> ";
		    }
		    echo "<div>" . nl2br(htmlspecialchars($contenido)) . "</div>";
		    ?>
		</td>
                <td class="log-resp log-resp-collapsed" onclick="this.classList.toggle('log-resp-expanded'); this.classList.toggle('log-resp-collapsed');">
                    <?= nl2br(htmlspecialchars($log['respuesta'] ?? '')) ?>
                </td>
                <td>
                    <?php if (!empty($log['filtros'])):
                        foreach ((array)$log['filtros'] as $f):
                            if ($f) echo "<span class='tag-fil'>".htmlspecialchars($f)."</span> ";
                        endforeach;
                    endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if ($count === 0): ?>
            <tr><td colspan="6">No hay registros para los filtros seleccionados.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    <div>Mostrando máximo <?= $MAX_ROWS ?> registros recientes.</div>
</div>
<script>
    // Para expandir/colapsar respuestas largas
    document.querySelectorAll('.log-resp-collapsed').forEach(function(el) {
        el.addEventListener('click', function() {
            this.classList.toggle('log-resp-expanded');
            this.classList.toggle('log-resp-collapsed');
        });
    });
</script>
</body>
</html>

