<?php
/**
 * Configuración específica para Hostinger
 * Este archivo sobrescribe configuraciones en db_config.php cuando se ejecuta en producción
 */

// Detectar si estamos en el entorno de producción de Hostinger
$isHostinger = (strpos($_SERVER['HTTP_HOST'], 'hostinger.') !== false || 
               strpos($_SERVER['HTTP_HOST'], 'kdtekh.com') !== false);

if ($isHostinger) {
    // Configuración de base de datos para producción en Hostinger
    define('DB_HOST', 'localhost');  // Hostinger usa 'localhost' para conexiones locales
    define('DB_USER', 'u507128367_thesupremeside');
    define('DB_PASS', 'I1iA£naiWN2@s,I\\Meo[@YEj,=G.5');
    define('DB_NAME', 'u507128367_kdtekchin');
    
    // URL del sitio
    define('SITE_URL', 'https://kdtekh.com');
    
    // Configuración de depuración - DESACTIVAR en producción
    define('DEBUG_MODE', false);
    
    // Configuración de errores para producción
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../error_log/php_errors.log');
    error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
    
    // Forzar HTTPS
    if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
        header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], true, 301);
        exit();
    }
    
    // Configuración de caché para producción
    if (!headers_sent()) {
        header('Cache-Control: public, max-age=31536000');
        header('Pragma: cache');
        
        // Configuración de seguridad adicional
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Configuración de CORS
        $allowed_origins = [
            'https://kdtekh.com',
            'https://www.kdtekh.com'
        ];
        
        $http_origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
        
        if (in_array($http_origin, $allowed_origins)) {
            header("Access-Control-Allow-Origin: $http_origin");
        } else {
            header('Access-Control-Allow-Origin: https://kdtekh.com');
        }
        
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, X-Requested-With');
        header('Access-Control-Allow-Credentials: true');
    }
}

// Incluir configuración principal
require_once __DIR__ . '/db_config.php';

// Configuración específica para Hostinger
if ($isHostinger) {
    // Ruta absoluta al directorio del sitio en Hostinger
    if (!defined('ABSPATH')) {
        define('ABSPATH', dirname(dirname(__FILE__)) . '/');
    }
    
    // Configuración de correo electrónico
    ini_set('sendmail_from', 'noreply@kdtekh.com');
    ini_set('smtp_port', '587');
    
    // Configuración de zona horaria
    date_default_timezone_set('Europe/Madrid');
    
    // Configuración de memoria
    @ini_set('memory_limit', '256M');
    @ini_set('post_max_size', '64M');
    @ini_set('upload_max_filesize', '64M');
    
    // Habilitar compresión de salida si está disponible
    if (extension_loaded('zlib') && !ini_get('zlib.output_compression')) {
        ob_start('ob_gzhandler');
    }
}

// Función para verificar si estamos en producción
function is_production() {
    global $isHostinger;
    return $isHostinger;
}
