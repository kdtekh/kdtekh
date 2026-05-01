<?php
// Iniciar sesión
session_start();

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

// Incluir el archivo de configuración de la base de datos
require_once 'config.php';

// Verificar que se proporcionó un ID de mensaje
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de mensaje no válido']);
    exit();
}

$id = (int)$_GET['id'];

try {
    // Obtener el mensaje incluyendo created_at como fecha
    $stmt = $pdo->prepare("SELECT *, COALESCE(created_at, fecha) as fecha FROM mensajes_contacto WHERE id = ?");
    $stmt->execute([$id]);
    $mensaje = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Asegurarse de que la fecha esté en el formato correcto
    if (!empty($mensaje['fecha'])) {
        $fecha = new DateTime($mensaje['fecha']);
        $mensaje['fecha'] = $fecha->format('Y-m-d H:i:s');
    }
    
    if (!$mensaje) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Mensaje no encontrado']);
        exit();
    }
    
    // Devolver el mensaje como JSON
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'mensaje' => $mensaje]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al obtener el mensaje: ' . $e->getMessage()]);
}
?>
