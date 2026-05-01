<?php
// Archivo temporal para diagnóstico de autenticación
// ¡ADVERTENCIA! Este archivo es solo para diagnóstico y debe eliminarse después de su uso

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/auth_bypass.log');

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Forzar autenticación (solo para pruebas)
$_SESSION['usuario_id'] = 1;  // Usar un ID de usuario válido
$_SESSION['usuario_nombre'] = 'Admin';
$_SESSION['usuario_email'] = 'admin@kdtekh.com';

// Registrar la acción
error_log("Bypass de autenticación ejecutado para el usuario: " . $_SESSION['usuario_email']);

// Redirigir a la página de mensajes
header('Location: index.php');
exit();

// Nota: Este archivo debe ser eliminado después de su uso por razones de seguridad
?>
