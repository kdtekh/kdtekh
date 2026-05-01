<?php
// Script temporal para depuración de acceso

// Habilitar visualización de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/temp_access.log');

// Incluir configuración principal
require_once __DIR__ . '/../config.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Forzar datos de sesión para pruebas (SOLO PARA DEPURACIÓN)
$_SESSION['usuario_id'] = 1;
$_SESSION['usuario_nombre'] = 'Administrador';
$_SESSION['usuario_email'] = 'admin@kdt.com';
$_SESSION['usuario_rol'] = 'admin';

// Incluir el archivo original
require_once __DIR__ . '/index.php';
?>
