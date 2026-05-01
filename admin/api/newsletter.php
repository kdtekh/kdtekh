<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config.php';

$method = $_SERVER['REQUEST_METHOD'];

// Función para sincronizar suscriptores desde JSON a la base de datos
function syncSubscribersFromJson($pdo) {
    $jsonFile = dirname(dirname(__DIR__)) . '/data/newsletter.min.json';
    
    if (!file_exists($jsonFile)) {
        return ['success' => false, 'message' => 'El archivo newsletter.min.json no existe'];
    }
    
    $jsonContent = file_get_contents($jsonFile);
    $data = json_decode($jsonContent, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['success' => false, 'message' => 'Error al decodificar el archivo JSON: ' . json_last_error_msg()];
    }
    
    if (!isset($data['subscribers']) || !is_array($data['subscribers'])) {
        return ['success' => false, 'message' => 'El archivo JSON no contiene un array de suscriptores válido'];
    }
    
    try {
        $pdo->beginTransaction();
        $added = 0;
        $skipped = 0;
        
        foreach ($data['subscribers'] as $subscriber) {
            if (!isset($subscriber['email']) || !filter_var($subscriber['email'], FILTER_VALIDATE_EMAIL)) {
                $skipped++;
                continue;
            }
            
            $email = $subscriber['email'];
            $subscribedAt = $subscriber['subscribed_at'] ?? date('Y-m-d H:i:s');
            
            // Verificar si el suscriptor ya existe
            $stmt = $pdo->prepare("SELECT id FROM newsletter WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                $skipped++;
                continue;
            }
            
            // Insertar el nuevo suscriptor
            $stmt = $pdo->prepare("
                INSERT INTO newsletter (email, nombre, activo, confirmado, fecha_registro, created_at, updated_at)
                VALUES (?, ?, 1, 1, ?, NOW(), NOW())
            ");
            
            // Usar el email como nombre si no se proporciona uno
            $name = $subscriber['name'] ?? explode('@', $email)[0];
            
            $stmt->execute([$email, $name, $subscribedAt]);
            $added++;
        }
        
        $pdo->commit();
        return ['success' => true, 'added' => $added, 'skipped' => $skipped];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Error al sincronizar suscriptores: ' . $e->getMessage()];
    }
}

// Manejar la solicitud según el método HTTP
switch ($method) {
    case 'GET':
        // Ya no sincronizamos automáticamente desde el JSON
        // La base de datos es ahora la fuente de la verdad
        $syncResult = ['success' => true, 'message' => 'Sincronización desactivada'];
        error_log('Sincronización automática deshabilitada');
        
        // Verificar si hay un término de búsqueda
        $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
        
        // Obtener todos los suscriptores (activos e inactivos) de la tabla newsletter
        try {
            $query = "SELECT id, email, nombre, fecha_registro as subscribed_at, activo as is_active, confirmado as is_confirmed, created_at, updated_at 
                     FROM newsletter 
                     WHERE 1=1";
            
            $countQuery = "SELECT COUNT(*) as count FROM newsletter WHERE 1=1";
            $countActiveQuery = "SELECT COUNT(*) as count FROM newsletter WHERE activo = 1";
            
            // Agregar condición de búsqueda si existe
            if (!empty($searchTerm)) {
                $searchParam = "%$searchTerm%";
                $query .= " AND (email LIKE :search OR nombre LIKE :search)";
                $countQuery .= " AND (email LIKE :search OR nombre LIKE :search)";
                $countActiveQuery .= " AND (email LIKE :search OR nombre LIKE :search)";
            }
            
            $query .= " ORDER BY activo DESC, fecha_registro DESC";
            
            // Obtener el total de suscriptores
            $stmt = $pdo->prepare($countQuery);
            if (!empty($searchTerm)) {
                $stmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
            }
            $stmt->execute();
            $total = $stmt->fetch()['count'];
            
            // Obtener el total de suscriptores activos
            $stmt = $pdo->prepare($countActiveQuery);
            if (!empty($searchTerm)) {
                $stmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
            }
            $stmt->execute();
            $totalActive = $stmt->fetch()['count'];
            
            // Aplicar paginación
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $offset = ($page - 1) * $limit;
            
            // Agregar límite y desplazamiento a la consulta
            $query .= " LIMIT :limit OFFSET :offset";
            
            // Preparar y ejecutar la consulta principal
            $stmt = $pdo->prepare($query);
            if (!empty($searchTerm)) {
                $stmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
            }
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Depuración
            error_log("Consulta SQL: $query");
            error_log("Parámetros - limit: $limit, offset: $offset");
            error_log("Total de suscriptores encontrados: " . count($subscribers));
            
            // Obtener estadísticas (solo si no hay búsqueda activa)
            if (empty($searchTerm)) {
                $yesterday = $pdo->query("SELECT COUNT(*) as count FROM newsletter WHERE DATE(fecha_registro) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)")->fetch()['count'];
                $today = $pdo->query("SELECT COUNT(*) as count FROM newsletter WHERE DATE(fecha_registro) = CURDATE()")->fetch()['count'];
                $lastWeek = $pdo->query("SELECT COUNT(*) as count FROM newsletter WHERE fecha_registro >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch()['count'];
            } else {
                // Si hay una búsqueda activa, no mostramos las estadísticas de fechas
                $yesterday = 0;
                $today = 0;
                $lastWeek = 0;
            }
            
            echo json_encode([
                'success' => true,
                'data' => $subscribers,
                'total' => (int)$total,
                'totalActive' => (int)$totalActive,
                'stats' => [
                    'yesterday' => (int)$yesterday,
                    'today' => (int)$today,
                    'lastWeek' => (int)$lastWeek
                ]
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al obtener los suscriptores: ' . $e->getMessage()]);
        }
        break;
        
    case 'POST':
        // Añadir un nuevo suscriptor
        $data = json_decode(file_get_contents('php://input'), true);
        $email = filter_var($data['email'] ?? '', FILTER_SANITIZE_EMAIL);
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Por favor, introduce un correo electrónico válido.']);
            exit;
        }
        
        try {
            $token = bin2hex(random_bytes(32));
            $stmt = $pdo->prepare("INSERT INTO newsletter (email, token_confirmacion, activo, confirmado, fecha_registro) VALUES (?, ?, 1, 1, NOW()) ON DUPLICATE KEY UPDATE activo = 1, updated_at = NOW()");
            $stmt->execute([$email, $token]);
            
            echo json_encode([
                'success' => true,
                'message' => '¡Gracias por suscribirte!'
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al procesar la suscripción: ' . $e->getMessage()]);
        }
        break;
        
    case 'DELETE':
        // Habilitar el registro de errores
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        
        // Registrar información de la petición
        error_log('==================================================');
        error_log('NUEVA SOLICITUD DELETE');
        error_log('Hora: ' . date('Y-m-d H:i:s'));
        error_log('Headers: ' . print_r(getallheaders(), true));
        
        // Obtener los datos de la solicitud
        $requestBody = file_get_contents('php://input');
        error_log('Cuerpo de la solicitud: ' . $requestBody);
        
        $requestData = json_decode($requestBody, true);
        
        // Registrar la solicitud recibida
        error_log('Solicitud DELETE recibida: ' . print_r($requestData, true));
        
        // Verificar si el JSON se decodificó correctamente
        if (json_last_error() !== JSON_ERROR_NONE) {
            $error = 'Error al decodificar el JSON: ' . json_last_error_msg();
            error_log($error);
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $error]);
            exit;
        }
        
        // Deshabilitar la sincronización automática durante la eliminación
        $synchronize = false;
        
        $email = filter_var($requestData['email'] ?? '', FILTER_SANITIZE_EMAIL);
        error_log('Email después de filtrar: ' . $email);
        
        if (empty($email)) {
            $error = 'Se requiere un correo electrónico.';
            error_log('Error en la solicitud DELETE: ' . $error);
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $error]);
            exit;
        }
        
        try {
            // Registrar el intento de eliminación
            error_log("========================================");
            error_log("INICIO DE SOLICITUD DE ELIMINACIÓN");
            error_log("Hora: " . date('Y-m-d H:i:s'));
            error_log("Intentando eliminar el suscriptor: $email");
            
            // Verificar la conexión a la base de datos
            if (!$pdo) {
                error_log("ERROR: No hay conexión a la base de datos");
                throw new Exception('No hay conexión a la base de datos');
            }
            
            // Primero verificamos si el suscriptor existe
            error_log("Verificando si el suscriptor existe en la base de datos...");
            $checkStmt = $pdo->prepare("SELECT id, email, nombre FROM newsletter WHERE email = ?");
            $checkStmt->execute([$email]);
            
            if ($checkStmt->rowCount() > 0) {
                $suscriptor = $checkStmt->fetch(PDO::FETCH_ASSOC);
                error_log("Suscriptor encontrado en la base de datos:");
                error_log(print_r($suscriptor, true));
                error_log("Suscriptor encontrado: " . $suscriptor['email'] . " (ID: " . $suscriptor['id'] . ")");
                
                // Si existe, lo eliminamos completamente
                $deleteQuery = "DELETE FROM newsletter WHERE email = ?";
                error_log("Ejecutando consulta: $deleteQuery con email: $email");
                
                try {
                    // Iniciar una transacción para asegurar la integridad de los datos
                    if (!$pdo->beginTransaction()) {
                        throw new PDOException('No se pudo iniciar la transacción');
                    }
                    
                    error_log("Transacción iniciada");
                    
                    // Primero, obtener el ID del suscriptor para usarlo en los logs
                    $getStmt = $pdo->prepare("SELECT id FROM newsletter WHERE email = ?");
                    $getStmt->execute([$email]);
                    $subscriber = $getStmt->fetch(PDO::FETCH_ASSOC);
                    $subscriberId = $subscriber ? $subscriber['id'] : 'desconocido';
                    
                    error_log("Intentando eliminar suscriptor ID: $subscriberId, Email: $email");
                    
                    // Ejecutar la eliminación
                    $deleteStmt = $pdo->prepare($deleteQuery);
                    $deleteResult = $deleteStmt->execute([$email]);
                    $rowsAffected = $deleteStmt->rowCount();
                    
                    // Verificar si hubo algún error en la consulta
                    $errorInfo = $deleteStmt->errorInfo();
                    if ($errorInfo[0] !== '00000' && $errorInfo[0] !== '') {
                        throw new PDOException("Error al ejecutar la consulta: " . ($errorInfo[2] ?? 'Error desconocido'));
                    }
                    
                    error_log("Eliminación ejecutada - Filas afectadas: $rowsAffected");
                    
                    // Verificar manualmente si el registro fue eliminado
                    $verifyStmt = $pdo->prepare("SELECT COUNT(*) as count FROM newsletter WHERE email = ?");
                    $verifyStmt->execute([$email]);
                    $verifyResult = $verifyStmt->fetch(PDO::FETCH_ASSOC);
                    
                    error_log("Verificación después de eliminar - Registros con este email: " . $verifyResult['count']);
                    
                    if ($verifyResult['count'] > 0) {
                        // Intentar forzar la eliminación si aún existe
                        error_log("El registro aún existe, intentando eliminación forzada...");
                        $forceDeleteStmt = $pdo->prepare("DELETE FROM newsletter WHERE email = ? LIMIT 1");
                        $forceDeleteStmt->execute([$email]);
                        
                        // Verificar nuevamente
                        $verifyStmt->execute([$email]);
                        $verifyResult = $verifyStmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($verifyResult['count'] > 0) {
                            throw new Exception('No se pudo eliminar el registro de la base de datos después de múltiples intentos');
                        }
                    }
                    
                    // Si llegamos aquí, todo salió bien
                    $pdo->commit();
                    
                    // Obtener estadísticas actualizadas
                    $stats = [];
                    
                    // Obtener total de suscriptores
                    $totalStmt = $pdo->query("SELECT COUNT(*) as total FROM newsletter");
                    $totalResult = $totalStmt->fetch(PDO::FETCH_ASSOC);
                    $total = $totalResult['total'];
                    
                    // Obtener suscriptores de hoy
                    $todayStmt = $pdo->query("SELECT COUNT(*) as count FROM newsletter WHERE DATE(created_at) = CURDATE()");
                    $todayResult = $todayStmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Obtener suscriptores de ayer
                    $yesterdayStmt = $pdo->query("SELECT COUNT(*) as count FROM newsletter WHERE DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)");
                    $yesterdayResult = $yesterdayStmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Obtener suscriptores de la última semana
                    $lastWeekStmt = $pdo->query("SELECT COUNT(*) as count FROM newsletter WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
                    $lastWeekResult = $lastWeekStmt->fetch(PDO::FETCH_ASSOC);
                    
                    $response = [
                        'success' => true, 
                        'message' => 'Suscriptor eliminado correctamente.',
                        'total' => (int)$total,
                        'stats' => [
                            'today' => (int)$todayResult['count'],
                            'yesterday' => (int)$yesterdayResult['count'],
                            'lastWeek' => (int)$lastWeekResult['count']
                        ],
                        'debug' => [
                            'rows_affected' => $rowsAffected,
                            'still_exists' => $verifyResult['count'] > 0
                        ]
                    ];
                    
                    error_log("Eliminación exitosa. Respuesta: " . json_encode($response));
                    
                    // Establecer el tipo de contenido antes de enviar la respuesta
                    header('Content-Type: application/json');
                    echo json_encode($response);
                    
                    // Salir del script después de enviar la respuesta exitosa
                    exit();
                    
                } catch (Exception $e) {
                    // Hacer rollback en caso de error
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    
                    error_log("ERROR en la transacción: " . $e->getMessage());
                    error_log("Trace: " . $e->getTraceAsString());
                    
                    http_response_code(500);
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Error al eliminar el suscriptor: ' . $e->getMessage(),
                        'error' => [
                            'code' => $e->getCode(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine()
                        ]
                    ]);
                    exit();
                }
            } else {
                $error = 'No se encontró el suscriptor con el correo: ' . $email;
                error_log("Error: $error");
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => $error]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al eliminar el suscriptor: ' . $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        break;
}
?>
