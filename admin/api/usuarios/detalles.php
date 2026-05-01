<?php
// Incluir archivos necesarios
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/auth.php';

// Verificar que la solicitud sea GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('HTTP/1.1 405 Method Not Allowed');
    header('Allow: GET');
    exit(json_encode(['success' => false, 'message' => 'Método no permitido']));
}

// Verificar autenticación y permisos
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'administrador') {
    header('HTTP/1.1 403 Forbidden');
    exit(json_encode(['success' => false, 'message' => 'No autorizado']));
}

// Obtener y validar el ID del usuario
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('HTTP/1.1 400 Bad Request');
    exit(json_encode(['success' => false, 'message' => 'ID de usuario no válido']));
}

try {
    // Obtener los datos del usuario
    $stmt = $pdo->prepare("
        SELECT 
            id, nombre, apellidos, email, rol, activo, fecha_registro, 
            fecha_verificacion, ultimo_acceso, intentos_fallidos, 
            ip_registro, user_agent_registro, eliminado, fecha_eliminacion
        FROM usuarios_registrados 
        WHERE id = ?
    ");
    
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() === 0) {
        header('HTTP/1.1 404 Not Found');
        exit(json_encode(['success' => false, 'message' => 'Usuario no encontrado']));
    }
    
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Ocultar información sensible
    unset($usuario['password']);
    unset($usuario['token_verificacion']);
    unset($usuario['token_recuperacion']);
    
    // Formatear fechas
    $formatDate = function($date) {
        return $date ? date('d/m/Y H:i:s', strtotime($date)) : null;
    };
    
    $usuario['fecha_registro_formateada'] = $formatDate($usuario['fecha_registro']);
    $usuario['fecha_verificacion_formateada'] = $formatDate($usuario['fecha_verificacion']);
    $usuario['ultimo_acceso_formateado'] = $formatDate($usuario['ultimo_acceso']);
    $usuario['fecha_eliminacion_formateada'] = $formatDate($usuario['fecha_eliminacion']);
    
    // Obtener estadísticas adicionales (ejemplo: número de inicios de sesión, etc.)
    // Esto es un ejemplo, puedes personalizarlo según tus necesidades
    
    // Registrar la acción
    logAccion('usuarios', 'ver_detalles', "Consultó los detalles del usuario con ID $id");
    
    // Devolver respuesta exitosa
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => $usuario
    ]);
    
} catch (Exception $e) {
    // Registrar el error
    error_log("Error al obtener detalles del usuario: " . $e->getMessage());
    
    // Devolver error
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener los detalles del usuario',
        'debug' => DEBUG_MODE ? $e->getMessage() : null
    ]);
}
?>
