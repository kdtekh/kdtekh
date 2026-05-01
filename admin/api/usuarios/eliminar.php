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

// Obtener y validar el ID del usuario
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id <= 0) {
    header('HTTP/1.1 400 Bad Request');
    exit(json_encode(['success' => false, 'message' => 'ID de usuario no válido']));
}

try {
    // Iniciar transacción
    $pdo->beginTransaction();
    
    // Verificar que el usuario existe y no es el mismo que está realizando la acción
    $stmt = $pdo->prepare("SELECT id, email FROM usuarios_registrados WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Usuario no encontrado');
    }
    
    $usuario = $stmt->fetch();
    
    // No permitir eliminar el propio usuario
    if ($usuario['id'] == $_SESSION['usuario_id']) {
        throw new Exception('No puedes eliminar tu propia cuenta');
    }
    
    // Marcar el usuario como eliminado (borrado lógico)
    $stmt = $pdo->prepare("UPDATE usuarios_registrados SET activo = 0, eliminado = 1, fecha_eliminacion = NOW() WHERE id = ?");
    $stmt->execute([$id]);
    
    // Registrar la acción
    logAccion('usuarios', 'eliminar', "Se eliminó el usuario con ID $id (" . $usuario['email'] . ")");
    
    // Confirmar la transacción
    $pdo->commit();
    
    // Devolver respuesta exitosa
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Usuario eliminado correctamente'
    ]);
    
} catch (Exception $e) {
    // Revertir la transacción en caso de error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Registrar el error
    error_log("Error al eliminar usuario: " . $e->getMessage());
    
    // Devolver error
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => DEBUG_MODE ? $e->getTraceAsString() : null
    ]);
}
?>
