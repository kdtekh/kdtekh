<?php
/**
 * Punto de entrada de configuración público
 * Este archivo carga la configuración segura
 */

// Ruta al directorio seguro (ajusta según tu estructura)
$secureConfigPath = dirname(dirname(__DIR__)) . '/kdtekh_secure/config/env.php';

// Verificar que el archivo de configuración seguro exista
if (!file_exists($secureConfigPath)) {
    // En producción, podrías querer registrar el error sin mostrar detalles
    error_log('Error crítico: No se encontró el archivo de configuración seguro');
    die('Error de configuración. Por favor, contacta al administrador.');
}

// Cargar la configuración segura
require_once $secureConfigPath;

// Configuración adicional específica del entorno público
ini_set('display_errors', DEBUG_MODE ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', dirname(dirname(__DIR__)) . '/kdtekh_secure/logs/php_errors.log');

// Configuración de zona horaria
date_default_timezone_set('Europe/Madrid');

// Configuración de manejo de errores personalizado
if (!DEBUG_MODE) {
    set_error_handler(function($errno, $errstr, $errfile, $errline) {
        error_log("Error [$errno] $errstr en $errfile en la línea $errline");
        if (!(error_reporting() & $errno)) {
            return false;
        }
        return true;
    });
    
    register_shutdown_function(function() {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            error_log("Error fatal: " . print_r($error, true));
            http_response_code(500);
            if (!headers_sent()) {
                header('Content-Type: text/plain');
            }
            echo 'Se ha producido un error inesperado. Por favor, inténtalo de nuevo más tarde.';
        }
    });
}

// Función para cargar clases automáticamente
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = dirname(dirname(__DIR__)) . '/kdtekh_secure/src/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Iniciar sesión segura
function startSecureSession() {
    $session_name = 'secure_session';
    $secure = true;
    $httponly = true;
    
    if (ini_set('session.use_only_cookies', 1) === false) {
        error_log("No se pudo iniciar una sesión segura");
        exit();
    }
    
    $cookieParams = session_get_cookie_params();
    session_set_cookie_params(
        $cookieParams["lifetime"],
        $cookieParams["path"], 
        $cookieParams["domain"],
        $secure,
        $httponly
    );
    
    session_name($session_name);
    session_start();
    session_regenerate_id(true);
}

// Incluir funciones de ayuda si existen
$helperFile = dirname(dirname(__DIR__)) . '/kdtekh_secure/includes/functions.php';
if (file_exists($helperFile)) {
    require_once $helperFile;
}
?>
