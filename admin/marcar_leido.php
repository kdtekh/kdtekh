<?php
// Iniciar sesión
session_start();

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

// Verificar que la solicitud sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit();
}

// Incluir el archivo de configuración de la base de datos
require_once 'config.php';

// Verificar que se proporcionó un ID de mensaje
$id = isset($_GET['id']) ? $_GET['id'] : (isset($_POST['id']) ? $_POST['id'] : null);

if (!is_numeric($id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de mensaje no válido']);
    exit();
}

$id = (int)$id;

try {
    // Marcar el mensaje como leído
    $stmt = $pdo->prepare("UPDATE mensajes_contacto SET leido = 1 WHERE id = ?");
    $stmt->execute([$id]);
    
    // Verificar si se actualizó correctamente
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Mensaje no encontrado']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al marcar el mensaje como leído: ' . $e->getMessage()]);
}
?>
