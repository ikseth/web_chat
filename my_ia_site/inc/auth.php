<?php
// Archivo: inc/auth.php
session_start();

function load_users() {
    return include __DIR__ . '/../config/users.php';
}

function auth_login($username, $password) {
    $users = load_users();
    // Cambia [0] por ['pass'] y [1] por ['rol']
    if (isset($users[$username]) && password_verify($password, $users[$username]['pass'])) {
        $_SESSION['user'] = $username;
        $_SESSION['role'] = $users[$username]['rol'];
        // Guarda también la fecha de nacimiento en sesión (opcional pero recomendado)
        $_SESSION['fecha_nacimiento'] = $users[$username]['fecha_nacimiento'] ?? null;
        return true;
    }
    return false;
}

function auth_logout() {
    session_unset();
    session_destroy();
}

function auth_user() {
    return $_SESSION['user'] ?? null;
}

function auth_role() {
    return $_SESSION['role'] ?? 'guest';
}

// NUEVA función: obtener la fecha de nacimiento del usuario autenticado
function auth_fecha_nacimiento() {
    return $_SESSION['fecha_nacimiento'] ?? null;
}

// NUEVA función: calcular la edad a partir de la fecha de nacimiento
function auth_edad() {
    $fn = auth_fecha_nacimiento();
    if (!$fn) return null;
    $dt = new DateTime($fn);
    $now = new DateTime();
    $edad = $now->diff($dt)->y;
    return $edad;
}

function require_login() {
    if (!auth_user()) {
        header('Location: /public/login.php?login_required=1');
        exit;
    }
}

function require_role($role) {
    if (auth_role() !== $role) {
        header('Location: /public/login.php?forbidden=1');
        exit;
    }
}

