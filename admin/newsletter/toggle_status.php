<?php
// Habilitar visualización de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/newsletter_errors.log');

// Iniciar la sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticación y permisos
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    die("Acceso denegado");
}

// Verificar que se haya proporcionado un ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('HTTP/1.1 400 Bad Request');
    die("ID no válido");
}

$id = (int)$_GET['id'];

// Incluir configuración de la base de datos
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

try {
    // Obtener el estado actual
    $stmt = $pdo->prepare("SELECT activo FROM newsletter WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Suscriptor no encontrado']);
        exit();
    }
    
    $currentStatus = $stmt->fetch(PDO::FETCH_ASSOC)['activo'];
    $newStatus = $currentStatus ? 0 : 1; // Toggle between 0 and 1
    
    // Actualizar el estado
    $stmt = $pdo->prepare("UPDATE newsletter SET activo = :activo, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
    $stmt->bindValue(':activo', $newStatus, PDO::PARAM_INT);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $result = $stmt->execute();
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'estado' => $newStatus,
            'badgeClass' => $newStatus === 'activo' ? 'success' : 'secondary',
            'icon' => $newStatus === 'activo' ? 'check-circle' : 'x-circle'
        ]);
    } else {
        throw new Exception('No se pudo actualizar el estado');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
