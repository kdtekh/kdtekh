<?php
/**
 * Configuración principal del panel de administración
 */

// Configuración de errores AL INICIO del archivo
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/admin_errors.log');

// Definir entorno
define('ENVIRONMENT', 'development');

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'u507128367_kdtekchin');
define('DB_USER', 'u507128367_thesupremeside');
define('DB_PASS', 'I1iA£naiWN2@s,I\\Meo[@YEj,=G.5');
define('DB_CHARSET', 'utf8mb4');

// Configuración de la aplicación
define('IS_CLI', php_sapi_name() === 'cli');
define('SITE_URL', IS_CLI ? 'https://kdtekh.com/' : 'https://' . $_SERVER['HTTP_HOST'] . '/');
define('ADMIN_EMAIL', 'admin@kdtekh.com');
define('APP_NAME', 'KDTekh');
define('APP_VERSION', '1.0.0');

// Configuración de sesión - Solo si no hay sesión activa y no es CLI
if (session_status() === PHP_SESSION_NONE && !IS_CLI) {
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
    
    // Iniciar sesión
    session_start();
}

// Registrar la sesión actual para depuración
error_log('Sesión iniciada: ' . session_id());

// Función para obtener la conexión a la base de datos
function getDbConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            // Configurar la zona horaria
            $pdo->exec("SET time_zone = '+02:00';");
            
        } catch (PDOException $e) {
            error_log('Error de conexión a la base de datos: ' . $e->getMessage());
            die('Error de conexión a la base de datos. Por favor, inténtalo de nuevo más tarde.');
        }
    }
    
    return $pdo;
}

// Inicializar conexión a la base de datos
$pdo = getDbConnection();

// La función requireAuth() ahora está en auth.php
?>
