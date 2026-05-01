<?php
// Habilitar visualización de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/auth_check.log');

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    // Configuración de cookies de sesión seguras
    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    $httponly = true;
    
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => $secure,
        'httponly' => $httponly,
        'samesite' => 'Lax'
    ]);
    
    session_start();
}

// Para depuración - registrar la sesión actual
error_log('Auth Check - Session Data: ' . print_r($_SESSION, true));

// Verificar si el usuario está autenticado
if (empty($_SESSION['usuario_id'])) {
    error_log('Usuario no autenticado - Redirigiendo a login');
    // Guardar la URL actual para redirigir después del login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    
    // Redirigir al login
    header('Location: /admin/login.php');
    exit();
}

// Verificar el tiempo de inactividad (30 minutos)
$inactive = 1800; // 30 minutos en segundos
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $inactive)) {
    error_log('Sesión expirada por inactividad');
    // Destruir la sesión
    session_unset();
    session_destroy();
    
    // Redirigir al login con mensaje de tiempo de espera agotado
    header('Location: /admin/login.php?timeout=1');
    exit();
}

// Actualizar el tiempo de última actividad
$_SESSION['last_activity'] = time();

// Verificar si el usuario tiene el rol de administrador
if (empty($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'admin') {
    error_log('Intento de acceso no autorizado. Rol: ' . ($_SESSION['usuario_rol'] ?? 'No definido'));
    // Usuario no autorizado
    header('HTTP/1.0 403 Forbidden');
    die('Acceso denegado: No tienes permisos para acceder a esta sección.');
}

// Si llegamos hasta aquí, el acceso está permitido
error_log('Acceso concedido para el usuario ID: ' . $_SESSION['usuario_id']);
?>
