<?php
// Incluir archivos necesarios
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/auth.php';

// Verificar que la solicitud sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    header('Allow: POST');
    exit(json_encode(['success' => false, 'message' => 'Método no permitido']));
}

// Verificar autenticación y permisos
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'administrador') {
    header('HTTP/1.1 403 Forbidden');
    exit(json_encode(['success' => false, 'message' => 'No autorizado']));
}

// Obtener y validar los datos de entrada
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$activo = isset($_POST['activo']) ? (int)$_POST['activo'] : 0;

if ($id <= 0) {
    header('HTTP/1.1 400 Bad Request');
    exit(json_encode(['success' => false, 'message' => 'ID de usuario no válido']));
}

try {
    // Verificar que el usuario existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios_registrados WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() === 0) {
        header('HTTP/1.1 404 Not Found');
        exit(json_encode(['success' => false, 'message' => 'Usuario no encontrado']));
    }
    
    // Actualizar el estado del usuario
    $stmt = $pdo->prepare("UPDATE usuarios_registrados SET activo = ? WHERE id = ?");
    $stmt->execute([$activo, $id]);
    
    // Registrar la acción
    $accion = $activo ? 'activó' : 'desactivó';
    logAccion('usuarios', 'cambiar_estado', "Se $accion el usuario con ID $id");
    
    // Devolver respuesta exitosa
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => "Estado del usuario actualizado correctamente"
    ]);
    
} catch (PDOException $e) {
    // Registrar el error
    error_log("Error al cambiar el estado del usuario: " . $e->getMessage());
    
    // Devolver error
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'success' => false,
        'message' => 'Error al actualizar el estado del usuario',
        'debug' => DEBUG_MODE ? $e->getMessage() : null
    ]);
}
?>
