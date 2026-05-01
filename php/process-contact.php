<?php
/**
 * Procesamiento del formulario de contacto con validación de seguridad mejorada
 */

// Habilitar el reporte de errores para depuración
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Incluir configuración de la base de datos que contiene SITE_URL
require_once __DIR__ . '/../includes/db_config.php';

// Incluir funciones de seguridad
require_once __DIR__ . '/../includes/security.php';

// Función para enviar respuesta JSON
function sendJsonResponse($data, $statusCode = 200) {
    // Limpiar cualquier salida previa
    if (ob_get_length()) ob_clean();
    
    // Establecer el código de estado HTTP
    http_response_code((int)$statusCode);
    
    // Establecer las cabeceras
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    
    // Enviar la respuesta JSON
    echo json_encode($data);
    exit();
}

// Manejar errores inesperados
set_exception_handler(function($e) {
    error_log('Excepción no capturada: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
    
    $statusCode = is_numeric($e->getCode()) ? (int)$e->getCode() : 500;
    $statusCode = ($statusCode >= 400 && $statusCode < 600) ? $statusCode : 500;
    
    sendJsonResponse([
        'success' => false,
        'message' => 'Error interno del servidor. Por favor, inténtalo de nuevo más tarde.',
        'code' => 'internal_server_error',
        'debug' => DEBUG_MODE ? $e->getMessage() : null
    ], $statusCode);
});

// Manejar errores fatales
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        error_log('Error fatal: ' . print_r($error, true));
        
        if (!headers_sent()) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Error interno del servidor. Por favor, inténtalo de nuevo más tarde.',
                'code' => 'internal_server_error',
                'debug' => DEBUG_MODE ? $error['message'] . ' in ' . $error['file'] . ' on line ' . $error['line'] : null
            ], 500);
        }
    }
});

// Configuración de cabeceras para CORS
header("Access-Control-Allow-Origin: " . SITE_URL);
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Requested-With, X-CSRF-Token");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");

// Manejar solicitudes preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    sendJsonResponse(['success' => true], 200);
}

// Verificar si la petición es POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse([
        'success' => false, 
        'message' => 'Método no permitido',
        'code' => 'method_not_allowed'
    ], 405);
}

try {
    // Obtener y validar los datos del formulario
    $input = file_get_contents('php://input');
    
    if (empty($input)) {
        throw new Exception('No se recibieron datos del formulario', 400);
    }
    
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Formato de datos inválido: ' . json_last_error_msg(), 400);
    }
    
    // Validar campos requeridos
    $required_fields = ['name', 'email', 'message', 'subject', 'csrf_token'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        throw new Exception(
            'Faltan campos requeridos: ' . implode(', ', $missing_fields),
            400
        );
    }
    
    // Sanitizar entradas
    $name = trim($data['name']);
    $email = trim($data['email']);
    $subject = trim($data['subject']);
    $message = trim($data['message']);
    $csrf_token = $data['csrf_token'];
    $telefono = !empty($data['phone']) ? trim($data['phone']) : '';
    $suscripcion = !empty($data['newsletter']) ? 1 : 0;
    
    // Validar token CSRF
    if (!verify_csrf_token($csrf_token)) {
        throw new Exception('Token CSRF inválido o expirado', 403);
    }
    
    // Validar formato de email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('El formato del correo electrónico no es válido', 400);
    }
    
    // Validar longitud de los campos
    $validations = [
        'name' => [100, 'El nombre no puede tener más de 100 caracteres'],
        'email' => [100, 'El correo electrónico no puede tener más de 100 caracteres'],
        'subject' => [200, 'El asunto no puede tener más de 200 caracteres'],
        'message' => [2000, 'El mensaje no puede tener más de 2000 caracteres'],
        'phone' => [20, 'El teléfono no puede tener más de 20 caracteres']
    ];
    
    foreach ($validations as $field => $config) {
        if (${$field} && mb_strlen(${$field}) > $config[0]) {
            throw new Exception($config[1], 400);
        }
    }
    
    // Insertar el mensaje en la base de datos
    try {
        global $conn;
        
        // Usar la hora del servidor con un ajuste de +2 horas
        $stmt = $conn->prepare(
            "INSERT INTO mensajes_contacto 
             (nombre, email, telefono, asunto, mensaje, leido, suscripcion, ip, user_agent, fecha) 
             VALUES (:nombre, :email, :telefono, :asunto, :mensaje, 0, :suscripcion, :ip, :user_agent, DATE_ADD(NOW(), INTERVAL 2 HOUR))"
        );
        
        $stmt->execute([
            ':nombre' => $name,
            ':email' => $email,
            ':telefono' => $telefono,
            ':asunto' => $subject,
            ':mensaje' => $message,
            ':suscripcion' => $suscripcion,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
        // Registrar el evento (comentado temporalmente)
        // log_event("Nuevo mensaje de contacto de: $email - $subject", 'INFO');
        
        // Enviar respuesta de éxito
        sendJsonResponse([
            'success' => true, 
            'message' => 'Mensaje enviado con éxito. Nos pondremos en contacto contigo pronto.'
        ]);
        
    } catch (PDOException $e) {
        $errorInfo = $e->errorInfo;
        $errorMessage = sprintf(
            'Error de base de datos [%s]: %s\nQuery: %s\nDatos: %s',
            $e->getCode(),
            $e->getMessage(),
            $stmt->queryString ?? 'N/A',
            json_encode([
                'nombre' => $name,
                'email' => $email,
                'telefono' => $telefono,
                'asunto' => $subject,
                'mensaje' => substr($message, 0, 100) . (strlen($message) > 100 ? '...' : ''),
                'suscripcion' => $suscripcion,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ])
        );
        
        error_log($errorMessage);
        
        // Registrar información adicional del error PDO
        if (isset($errorInfo[2])) {
            error_log('Detalle del error PDO: ' . $errorInfo[2]);
        }
        
        throw new Exception('Error al guardar el mensaje. Por favor, inténtalo de nuevo más tarde. Código: ' . $e->getCode(), 500);
    }
    
} catch (Exception $e) {
    $statusCode = is_numeric($e->getCode()) ? (int)$e->getCode() : 500;
    $statusCode = ($statusCode >= 400 && $statusCode < 600) ? $statusCode : 500;
    
    $errorData = [
        'success' => false,
        'message' => $e->getMessage(),
        'code' => strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $e->getMessage()))
    ];
    
    // Solo incluir información de depuración en modo desarrollo
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        $errorData['debug'] = [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ];
    }
    
    // Registrar el error
    error_log(sprintf(
        'Error en process-contact [%d]: %s in %s on line %d',
        $statusCode,
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    ));
    
    // Enviar respuesta de error
    sendJsonResponse($errorData, $statusCode);
}
