<?php
require_once __DIR__ . '/../../../inc/auth.php';
require_login();

if (auth_role() !== 'admin') {
    die("Acceso restringido.");
}

$roles_file = __DIR__ . '/../../../config/roles.json';

if (!is_writable($roles_file)) {
    die("El fichero de roles no es escribible.");
}

$roles = json_decode(file_get_contents($roles_file), true);

// Guardar/eliminar existentes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Eliminar rol
    if (isset($_POST['delete']) && $_POST['delete']) {
        $to_delete = $_POST['delete'];
        $roles = array_values(array_filter($roles, function($r) use ($to_delete) {
            return $r['id'] !== $to_delete;
        }));
    }
    // Guardar cambios
    if (isset($_POST['roles'])) {
        foreach ($_POST['roles'] as $idx => $r) {
            $roles[$idx]['id'] = $r['id'];
            $roles[$idx]['nombre'] = $r['nombre'];
            $roles[$idx]['expertos_permitidos'] = isset($r['expertos_permitidos']) ? $r['expertos_permitidos'] : [];
            $roles[$idx]['formatos_permitidos'] = isset($r['formatos_permitidos']) ? $r['formatos_permitidos'] : [];
            $roles[$idx]['prompt_base'] = $r['prompt_base'];
        }
    }
    // Añadir nuevo rol
    if (isset($_POST['add']) && isset($_POST['new']['id']) && $_POST['new']['id']) {
        $roles[] = [
            'id' => $_POST['new']['id'],
            'nombre' => $_POST['new']['nombre'],
            'expertos_permitidos' => isset($_POST['new']['expertos_permitidos']) ? $_POST['new']['expertos_permitidos'] : [],
            'formatos_permitidos' => isset($_POST['new']['formatos_permitidos']) ? $_POST['new']['formatos_permitidos'] : [],
            'prompt_base' => $_POST['new']['prompt_base'],
        ];
    }
    // Guardar el JSON
    file_put_contents($roles_file, json_encode($roles, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    header('Location: manage_roles.php?saved=1');
    exit;
}
header('Location: manage_roles.php');

