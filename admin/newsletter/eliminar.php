<?php
// Habilitar visualización de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/newsletter_errors.log');

// Establecer el tipo de contenido como JSON
header('Content-Type: application/json');

// Iniciar la sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticación y permisos
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado']);
    exit();
}

// Verificar que se haya proporcionado un ID
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de suscriptor no válido']);
    exit();
}

// Incluir configuración de la base de datos
require_once __DIR__ . '/../config.php';

try {
    // Preparar y ejecutar la consulta de eliminación
    $stmt = $pdo->prepare("DELETE FROM newsletter WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $result = $stmt->execute();
    
    if ($result && $stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No se encontró el suscriptor o ya fue eliminado']);
    }
} catch (PDOException $e) {
    // Registrar el error
    $errorMsg = 'Error al eliminar suscriptor: ' . $e->getMessage();
    error_log($errorMsg);
    
    // Devolver error en formato JSON
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error en el servidor al intentar eliminar el suscriptor']);
}

exit();
