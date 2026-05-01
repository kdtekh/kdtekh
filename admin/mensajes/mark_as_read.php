<?php
// Habilitar visualización de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/mark_as_read.log');

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticación
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    http_response_code(403);
    die(json_encode(['success' => false, 'error' => 'Acceso no autorizado']));
}

// Verificar que la solicitud sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'error' => 'Método no permitido']));
}

// Obtener el ID del mensaje
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'ID de mensaje no válido']));
}

// Incluir configuración de base de datos
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/database.php';

try {
    $db = getDbConnection();
    
    // Actualizar el estado de leído
    $stmt = $db->prepare("UPDATE mensajes_contacto SET leido = 1, updated_at = NOW() WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Mensaje no encontrado']);
    }
    
} catch (PDOException $e) {
    error_log('Error al marcar mensaje como leído: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al actualizar el mensaje']);
}
?>
