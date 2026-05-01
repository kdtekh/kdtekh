<?php
/**
 * Generador de tokens CSRF
 * 
 * Este archivo genera y devuelve tokens CSRF para formularios
 * 
 * @version 1.2
 * @author KD Tekh Team
 */

// Habilitar reporte de errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Incluir configuración de la base de datos que contiene SITE_URL
require_once __DIR__ . '/../includes/db_config.php';

// Configurar zona horaria
date_default_timezone_set('America/Mexico_City');

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    // Configurar parámetros de la cookie de sesión
    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    $httponly = true;
    $samesite = 'Lax';
    
    // PHP 7.3+ soporta el atributo SameSite
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => $httponly,
            'samesite' => $samesite
        ]);
    }
    
    // Iniciar la sesión
    session_start();
    
    // Regenerar el ID de sesión para mayor seguridad
    if (empty($_SESSION['last_regeneration']) || (time() - $_SESSION['last_regeneration'] > 1800)) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

// Obtener el origen de la solicitud
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
$allowedOrigins = [
    'https://kdtekh.com',
    'https://www.kdtekh.com',
    'http://localhost',
    'http://127.0.0.1'
];

// Verificar si el origen está permitido
$allowedOrigin = in_array($origin, $allowedOrigins) ? $origin : 'https://kdtekh.com';

// Configurar cabeceras CORS
header("Access-Control-Allow-Origin: $allowedOrigin");
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Vary: Origin');

// Manejar solicitudes preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Función para registrar errores
function logError($message, $context = []) {
    $logMessage = '[' . date('Y-m-d H:i:s') . '] ' . $message;
    if (!empty($context)) {
        $logMessage .= ' ' . json_encode($context, JSON_PRETTY_PRINT);
    }
    $logMessage .= "\n";
    
    $logFile = dirname(__DIR__) . '/logs/csrf_errors.log';
    if (!file_exists(dirname($logFile))) {
        @mkdir(dirname($logFile), 0755, true);
    }
    
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

// Generar token CSRF si no existe o está vacío
if (empty($_SESSION['csrf_token'])) {
    try {
        if (function_exists('random_bytes')) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        } else if (function_exists('openssl_random_pseudo_bytes')) {
            $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
        } else {
            // Método alternativo si no hay soporte para las funciones anteriores
            $_SESSION['csrf_token'] = md5(uniqid(mt_rand(), true));
        }
    } catch (Exception $e) {
        error_log('Error generando token CSRF: ' . $e->getMessage());
        $_SESSION['csrf_token'] = md5(uniqid(mt_rand(), true));
    }
}

// Obtener el token CSRF
$token = $_SESSION['csrf_token'];
$formName = isset($_GET['form']) ? htmlspecialchars($_GET['form'], ENT_QUOTES, 'UTF-8') : 'default_form';

// Registrar el intento de obtención de token
if (file_exists(dirname(__DIR__) . '/includes/Logger.php')) {
    require_once dirname(__DIR__) . '/includes/Logger.php';
    if (class_exists('Logger')) {
        Logger::debug('Token CSRF generado', [
            'form' => $formName,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}

// Devolver el token como JSON
$response = [
    'success' => true,
    'token' => $token,
    'form' => $formName,
    'timestamp' => time(),
    'session_id' => session_id()
];

// Agregar encabezados adicionales para depuración
if (defined('DEBUG') && DEBUG) {
    $response['debug'] = [
        'session_status' => session_status(),
        'session_id' => session_id(),
        'session_cookie_params' => session_get_cookie_params(),
        'headers_sent' => headers_sent(),
        'request_headers' => getallheaders()
    ];
}

echo json_encode($response);

exit();
