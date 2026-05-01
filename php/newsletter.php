<?php
/**
 * Newsletter API para KD Tekh
 * 
 * Este archivo maneja las suscripciones al boletín de noticias
 * 
 * @version 1.1
 * @author KD Tekh Team
 */

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuración de errores para producción/desarrollo
$isProduction = (strpos($_SERVER['HTTP_HOST'], 'kdtekh.com') !== false);

define('DEBUG', !$isProduction);

// Configuración de visualización de errores
error_reporting(E_ALL);
ini_set('display_errors', DEBUG ? '1' : '0');
ini_set('display_startup_errors', DEBUG ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', dirname(dirname(__DIR__)) . '/error_log/php_errors.log');

// Configuración de zona horaria
date_default_timezone_set('America/Mexico_City');

// Configurar el manejador de errores
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log(sprintf(
        "Error [%d] %s en %s línea %d",
        $errno,
        $errstr,
        $errfile,
        $errline
    ));
    
    if (DEBUG) {
        return false; // Permitir que el manejador interno de PHP maneje el error
    }
    
    // En producción, registrar el error pero no mostrarlo
    return true;
});

// Configurar el manejador de excepciones no capturadas
set_exception_handler(function($exception) {
    error_log("Excepción no capturada: " . $exception->getMessage());
    
    if (!headers_sent()) {
        header('Content-Type: application/json');
        http_response_code(500);
    }
    
    echo json_encode([
        'success' => false,
        'message' => DEBUG ? $exception->getMessage() : 'Ocurrió un error inesperado',
        'code' => $exception->getCode()
    ]);
    
    exit(1);
});

// Incluir configuración centralizada de CORS
require_once __DIR__ . '/../includes/cors.php';

// Incluir configuración de la base de datos
require_once __DIR__ . '/../includes/db_config.php';

try {
    global $conn;
    $db = $conn; // Usar la conexión existente de db_config.php
} catch (PDOException $e) {
    error_log('Error de conexión a la base de datos: ' . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'message' => 'Error al conectar con la base de datos'
    ], 500);
}

// Configurar el tipo de contenido para la respuesta
header('Content-Type: application/json');

// Incluir la clase Logger
require_once dirname(__DIR__) . '/includes/Logger.php';

// Incluir utilidades CSRF
require_once dirname(__DIR__) . '/includes/csrf_utils.php';

// Configurar el logger
Logger::setMinLevel(DEBUG ? Logger::LEVEL_DEBUG : Logger::LEVEL_INFO);

// Función para registrar intentos de suscripción
function logSubscriptionAttempt($email, $success, $message = '', $context = []) {
    $logContext = array_merge([
        'email' => $email,
        'success' => $success,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'referer' => $_SERVER['HTTP_REFERER'] ?? 'direct',
    ], $context);
    
    if ($success) {
        Logger::info("Suscripción exitosa", $logContext);
    } else {
        Logger::warning("Intento de suscripción fallido: $message", $logContext);
    }
}

// Función para verificar límite de intentos
function checkRateLimit($email, $ip, $maxAttempts = 5, $timeWindow = 3600) {
    global $db;
    
    try {
        // Crear la tabla de intentos si no existe
        $db->exec("CREATE TABLE IF NOT EXISTS subscription_attempts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            ip VARCHAR(45) NOT NULL,
            attempt_time INT NOT NULL,
            INDEX (email, ip, attempt_time)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        $now = time();
        $windowStart = $now - $timeWindow;
        
        // Eliminar intentos antiguos
        $stmt = $db->prepare("DELETE FROM subscription_attempts WHERE attempt_time < ?");
        $stmt->execute([$windowStart]);
        
        // Contar intentos recientes para este email o IP
        $stmt = $db->prepare("
            SELECT COUNT(*) as attempts 
            FROM subscription_attempts 
            WHERE (email = ? OR ip = ?) 
            AND attempt_time >= ?
        ");
        
        $stmt->execute([$email, $ip, $windowStart]);
        $result = $stmt->fetch();
        $attempts = (int)$result['attempts'];
        
        // Registrar este intento
        $stmt = $db->prepare("INSERT INTO subscription_attempts (email, ip, attempt_time) VALUES (?, ?, ?)");
        $stmt->execute([$email, $ip, $now]);
        
        return $attempts < $maxAttempts;
        
    } catch (PDOException $e) {
        error_log('Error en checkRateLimit: ' . $e->getMessage());
        // En caso de error, permitir la operación (fail open)
        return true;
    }
}

// Definir constantes de ruta
define('BASE_PATH', dirname(__FILE__) . '/../');

// Verificar si ya tenemos una conexión a la base de datos
if (!isset($db) || !($db instanceof PDO)) {
    try {
        // Intentar usar la conexión existente de db_config.php
        if (isset($conn) && $conn instanceof PDO) {
            $db = $conn;
        } else {
            // Si no hay conexión, intentar crear una nueva
            require_once __DIR__ . '/../includes/db_config.php';
            $db = $conn;
        }
        
        // Verificar si la conexión es válida
        $db->query('SELECT 1');
        
    } catch (Exception $e) {
        error_log('Error de conexión a la base de datos: ' . $e->getMessage());
        error_log('Trace: ' . $e->getTraceAsString());
        
        // Intentar conectar directamente con las credenciales de producción
        try {
            $db = new PDO(
                'mysql:host=localhost;dbname=u507128367_kdtekchin;charset=utf8mb4',
                'u507128367_thesupremeside',
                'I1iA£naiWN2@s,I\\Meo[@YEj,=G.5',
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ]
            );
        } catch (Exception $e2) {
            error_log('Error de conexión directa a la base de datos: ' . $e2->getMessage());
            sendJsonResponse([
                'success' => false,
                'message' => 'Error al conectar con la base de datos',
                'debug' => DEBUG ? $e2->getMessage() : null
            ], 500);
        }
    }
}

// No es necesario verificar el directorio ya que ahora usamos la base de datos

// Establecer el tipo de contenido como JSON
header('Content-Type: application/json; charset=utf-8');

// Función para enviar respuesta JSON y terminar la ejecución
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Ya no se necesita inicializar el archivo JSON ya que ahora usamos la base de datos

try {

    // Manejar diferentes métodos HTTP
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            try {
                // Obtener lista de suscriptores desde la base de datos
                $stmt = $db->query("SELECT * FROM newsletter ORDER BY fecha_registro DESC");
                $suscriptores = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                sendJsonResponse([
                    'success' => true,
                    'data' => $suscriptores
                ]);
                
            } catch (Exception $e) {
                error_log('Error al obtener suscriptores: ' . $e->getMessage());
                sendJsonResponse([
                    'success' => false,
                    'message' => 'Error al obtener los suscriptores: ' . $e->getMessage()
                ], 500);
            }
            break;

        case 'POST':
            try {
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
                    
                    session_start();
                }
                
                // Validar token CSRF
                if (!isset($_POST['csrf_token'])) {
                    $error = [
                        'success' => false,
                        'message' => 'Token de seguridad no proporcionado',
                        'error_code' => 'csrf_missing',
                        'debug' => [
                            'session_id' => session_id(),
                            'session_status' => session_status(),
                            'has_session_token' => !empty($_SESSION['csrf_token']),
                            'post_data' => $_POST
                        ]
                    ];
                    
                    if (class_exists('Logger')) {
                        Logger::error('Intento de suscripción sin token CSRF', $error['debug']);
                    }
                    
                    sendJsonResponse($error, 403);
                }
                
                // Verificar el token CSRF directamente de la sesión
                if (empty($_SESSION['csrf_token'])) {
                    $error = [
                        'success' => false,
                        'message' => 'Sesión no válida. Por favor, recarga la página e intenta de nuevo.',
                        'error_code' => 'session_invalid',
                        'debug' => [
                            'session_id' => session_id(),
                            'session_status' => session_status(),
                            'session_data' => $_SESSION
                        ]
                    ];
                    
                    if (class_exists('Logger')) {
                        Logger::error('Sesión sin token CSRF', $error['debug']);
                    }
                    
                    sendJsonResponse($error, 403);
                }
                
                if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
                    $error = [
                        'success' => false,
                        'message' => 'Token de seguridad inválido o expirado. Por favor, recarga la página e intenta de nuevo.',
                        'error_code' => 'csrf_invalid',
                        'debug' => [
                            'session_id' => session_id(),
                            'session_token' => substr($_SESSION['csrf_token'] ?? '', 0, 10) . '...',
                            'received_token' => substr($_POST['csrf_token'] ?? '', 0, 10) . '...',
                            'session_status' => session_status()
                        ]
                    ];
                    
                    if (class_exists('Logger')) {
                        Logger::error('Token CSRF inválido o expirado', $error['debug']);
                    }
                    
                    sendJsonResponse($error, 403);
                }
                
                // Obtener y validar el correo electrónico
                $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
                $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                
                // Validar que el correo no esté vacío
                if (empty($email)) {
                    logSubscriptionAttempt($email, false, 'Correo electrónico vacío', [
                        'validation_error' => 'empty_email'
                    ]);
                    
                    sendJsonResponse([
                        'success' => false,
                        'message' => 'El correo electrónico es requerido'
                    ], 400);
                }
                
                // Validar formato del correo
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    logSubscriptionAttempt($email, false, 'Formato de correo electrónico inválido', [
                        'validation_error' => 'invalid_email_format',
                        'email_attempt' => $email
                    ]);
                    
                    sendJsonResponse([
                        'success' => false,
                        'message' => 'Por favor, ingresa un correo electrónico válido'
                    ], 400);
                }
                
                // Verificar límite de intentos
                if (!checkRateLimit($email, $ip, 5, 3600)) { // 5 intentos por hora
                    logSubscriptionAttempt($email, false, 'Límite de intentos excedido', [
                        'rate_limit' => 'exceeded',
                        'ip' => $ip
                    ]);
                    
                    sendJsonResponse([
                        'success' => false,
                        'message' => 'Demasiados intentos. Por favor, inténtalo de nuevo más tarde.'
                    ], 429); // 429 Too Many Requests
                }

                // Verificar si el email ya existe en la base de datos
                $stmt = $db->prepare("SELECT COUNT(*) as count FROM newsletter WHERE email = ?");
                $stmt->execute([$email]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result['count'] > 0) {
                    // Obtener los datos del suscriptor existente
                    $stmt = $db->prepare("SELECT id, email, nombre, fecha_registro FROM newsletter WHERE email = ?");
                    $stmt->execute([$email]);
                    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Registrar el intento
                    logSubscriptionAttempt($email, true, 'Correo electrónico ya suscrito', [
                        'subscription_data' => [
                            'id' => $existing['id'],
                            'email' => $email,
                            'nombre' => $existing['nombre'] ?? explode('@', $email)[0],
                            'fecha_registro' => $existing['fecha_registro']
                        ]
                    ]);
                    
                    sendJsonResponse([
                        'success' => true,
                        'message' => '¡Ya estás suscrito a nuestro boletín!',
                        'subscriber' => [
                            'id' => $existing['id'],
                            'email' => $email,
                            'nombre' => $existing['nombre'] ?? explode('@', $email)[0],
                            'fecha_registro' => $existing['fecha_registro']
                        ]
                    ]);
                } else {
                    // Insertar nuevo suscriptor en la base de datos
                    $token = bin2hex(random_bytes(16));
                    $stmt = $db->prepare(
                        "INSERT INTO newsletter (email, nombre, token_confirmacion) 
                        VALUES (:email, :nombre, :token)"
                    );
                    
                    $stmt->execute([
                        ':email' => $email,
                        ':nombre' => explode('@', $email)[0],
                        ':token' => $token
                    ]);
                    
                    // Obtener el ID del nuevo suscriptor
                    $subscriberId = $db->lastInsertId();
                    
                    // Crear objeto con los datos del suscriptor
                    $newSubscriber = [
                        'id' => $subscriberId,
                        'email' => $email,
                        'nombre' => explode('@', $email)[0],
                        'fecha_registro' => date('Y-m-d H:i:s')
                    ];

                    // Registrar la suscripción exitosa
                    logSubscriptionAttempt($email, true, 'Suscripción exitosa', [
                        'subscription_data' => $newSubscriber
                    ]);
                    
                    // Enviar respuesta de éxito
                    sendJsonResponse([
                        'success' => true,
                        'message' => '¡Gracias por suscribirte!',
                        'subscriber' => $newSubscriber
                    ]);
                }
                
            } catch (Exception $e) {
                error_log('Error en POST: ' . $e->getMessage());
                sendJsonResponse([
                    'success' => false,
                    'message' => 'Error al procesar la suscripción: ' . $e->getMessage()
                ], 500);
            }
            break;

        case 'DELETE':
            try {
                // Obtener datos de la solicitud
                $input = file_get_contents('php://input');
                if ($input === false) {
                    throw new Exception('No se pudieron leer los datos de la solicitud');
                }
                
                $data = json_decode($input, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception('Formato de solicitud no válido');
                }
                
                $email = isset($data['email']) ? trim($data['email']) : '';

                if (empty($email)) {
                    sendJsonResponse([
                        'success' => false,
                        'message' => 'El correo electrónico es requerido'
                    ], 400);
                }

                // Verificar si el suscriptor existe
                $stmt = $db->prepare("SELECT id FROM newsletter WHERE email = :email");
                $stmt->execute([':email' => $email]);
                $subscriber = $stmt->fetch();
                
                if (!$subscriber) {
                    sendJsonResponse([
                        'success' => false,
                        'message' => 'No se encontró el suscriptor con ese correo electrónico'
                    ], 404);
                }
                
                // Eliminar el suscriptor
                $stmt = $db->prepare("DELETE FROM newsletter WHERE email = :email");
                $result = $stmt->execute([':email' => $email]);
                
                if ($result === false) {
                    $errorInfo = $stmt->errorInfo();
                    throw new Exception('Error al eliminar el suscriptor: ' . ($errorInfo[2] ?? 'Error desconocido'));
                }

                // Registrar la eliminación
                error_log("Suscriptor eliminado: $email - " . date('Y-m-d H:i:s'));
                
                sendJsonResponse([
                    'success' => true,
                    'message' => 'Suscriptor eliminado exitosamente',
                    'deleted' => $email
                ]);
                
            } catch (Exception $e) {
                error_log('Error en DELETE: ' . $e->getMessage());
                sendJsonResponse([
                    'success' => false,
                    'message' => 'Error al eliminar el suscriptor: ' . $e->getMessage()
                ], 500);
            }
            break;

        default:
            sendJsonResponse([
                'success' => false,
                'message' => 'Método no permitido'
            ], 405);
            break;
    }
} catch (Exception $e) {
    sendJsonResponse([
        'success' => false,
        'message' => 'Error al procesar la solicitud: ' . $e->getMessage()
    ], 500);
}
