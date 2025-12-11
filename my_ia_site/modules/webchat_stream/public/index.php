<?php
// Interfaz pública webchat: redirige a login si no autenticado
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: /public/login.php');
    exit;
}
echo "<h3>Webchat IA - Home</h3>";
echo "<a href='../restricted/chat.php'>Ir al chat</a>";
