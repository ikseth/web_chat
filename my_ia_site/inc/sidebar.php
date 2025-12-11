<?php
require_once __DIR__ . '/auth.php';

// Determinar página activa para destacar el menú
$current_file = basename($_SERVER['PHP_SELF']);
function active($file) {
    global $current_file;
    return $current_file === $file ? 'active' : '';
}
?>

<nav class="sidebar">
    <h3>Menú</h3>
    <ul>
        <li><a class="<?=active('index.php')?>" href="/index.php">Inicio</a></li>
        <li><a class="<?=active('chat.php')?>" href="/modules/webchat_stream/restricted/chat.php">Webchat IA</a></li>

	<?php if (auth_role() === 'admin'): ?>
	    <?php
	    // Detectar si alguna de las subsecciones está activa
	    $admin_files = ['users.php', 'mpaa.php', 'manage_roles.php', 'manage_experts.php'];
	    $admin_active = in_array($current_file, $admin_files);

	    $tools_files = ['logs.php'];
	    $tools_active = in_array($current_file, $tools_files);
	    ?>
	    <li class="submenu-group<?= $admin_active ? ' active' : '' ?>">
		<div class="submenu-toggle">Administración ▾</div>
		<ul class="submenu">
		    <li><a class="<?=active('users.php')?>" href="/admin/users.php">Usuarios</a></li>
		    <li><a class="<?=active('mpaa.php')?>" href="/admin/mpaa.php">Control Parental</a></li>
		    <li><a class="<?=active('manage_roles.php')?>" href="/modules/webchat_stream/admin/manage_roles.php">Roles/Atributos</a></li>
		    <li><a class="<?=active('manage_experts.php')?>" href="/modules/webchat_stream/admin/manage_experts.php">Expertos</a></li>
		</ul>
	    </li>
	    <li class="submenu-group<?= $tools_active ? ' active' : '' ?>">
		<div class="submenu-toggle">Herramientas ▾</div>
		<ul class="submenu">
		    <li><a class="<?=active('logs.php')?>" href="/modules/webchat_stream/admin/logs.php">Análisis de logs</a></li>
		</ul>
	    </li>
	<?php endif; ?>

        <li><a href="/public/logout.php" onclick="sessionStorage.removeItem('webchat_history');">Salir</a></li>
    </ul>
</nav>

<script>
document.querySelectorAll('.submenu-toggle').forEach(toggle => {
  toggle.addEventListener('click', () => {
    const parent = toggle.closest('.submenu-group');
    parent.classList.toggle('active');
  });
});
</script>

