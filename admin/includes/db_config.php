<?php
/**
 * Configuración de la base de datos para el panel de administración
 * 
 * Este archivo contiene las credenciales de conexión a la base de datos.
 * ¡Mantén este archivo seguro y no lo compartas!
 * 
 * @version 1.0
 * @author KD Tekh Team
 */

// Incluir el archivo de configuración principal
$rootPath = dirname(dirname(__DIR__));
require_once $rootPath . '/includes/db_config.php';

// Configuración específica para el panel de administración
define('ADMIN_EMAIL', 'admin@kdtekh.com');

// Configuración de la sesión del administrador
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));

// Establecer el tiempo de vida de la sesión (30 minutos)
$session_lifetime = 1800;
ini_set('session.gc_maxlifetime', $session_lifetime);
session_set_cookie_params($session_lifetime);

// Iniciar la sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Función para verificar si el usuario está autenticado
function isAuthenticated() {
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
        return false;
    }
    
    // Verificar tiempo de inactividad (30 minutos)
    $inactive = 1800; // 30 minutos en segundos
    $session_life = time() - $_SESSION['last_activity'];
    
    if ($session_life > $inactive) {
        session_unset();
        session_destroy();
        return false;
    }
    
    $_SESSION['last_activity'] = time();
    return true;
}

// Función para requerir autenticación
function requireAuth() {
    if (!isAuthenticated()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: /admin/login.php');
        exit;
    }
}

// Función para obtener la conexión a la base de datos (sobreescribe la función del archivo principal si es necesario)
function getAdminDBConnection() {
    global $pdo;
    
    if (!isset($pdo)) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => false
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

// Inicializar la conexión a la base de datos
$pdo = getAdminDBConnection();

// Función para registrar actividad en el log
function logActivity($action, $details = '') {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO admin_logs (user_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $_SESSION['user_id'] ?? null,
            $action,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log('Error al registrar actividad: ' . $e->getMessage());
        return false;
    }
}
?>
