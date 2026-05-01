<?php
// Configuración común de autenticación para todo el panel de administración

// Incluir configuración principal
require_once __DIR__ . '/../config.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    // Configuración de cookies seguras
    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    $httponly = true;
    $samesite = 'Strict';
    
    // Configurar parámetros de la cookie de sesión
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => $secure,
        'httponly' => $httponly,
        'samesite' => $samesite
    ]);
    
    session_start();
}

// Verificar autenticación (solo definir si no existe)
if (!function_exists('requireAuth')) {
    function requireAuth() {
        if (empty($_SESSION['usuario_id'])) {
            // Guardar la URL actual para redirigir después del login
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            
            // Redirigir al login
            header('Location: /admin/login.php');
            exit;
        }
        
        // Verificar el tiempo de inactividad (30 minutos)
        $inactive = 1800; // 30 minutos en segundos
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $inactive)) {
            session_unset();
            session_destroy();
            header('Location: /admin/login.php?timeout=1');
            exit;
        }
        
        // Actualizar el tiempo de última actividad
        $_SESSION['last_activity'] = time();
    }
}

// Función para verificar si el usuario es administrador (solo definir si no existe)
if (!function_exists('requireAdmin')) {
    function requireAdmin() {
        // Si el usuario no tiene rol definido pero está autenticado, asumir que es administrador
        if (empty($_SESSION['usuario_rol']) && !empty($_SESSION['usuario_id'])) {
            $_SESSION['usuario_rol'] = 'admin';
            return true;
        }
        
        // Verificar si el rol es 'admin' (case-insensitive)
        if (!empty($_SESSION['usuario_rol']) && strtolower($_SESSION['usuario_rol']) === 'admin') {
            return true;
        }
        
        // Si no es administrador, mostrar error
        header('HTTP/1.0 403 Forbidden');
        die('Acceso denegado. Se requieren privilegios de administrador.');
    }
}
?>
