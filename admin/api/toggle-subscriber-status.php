<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config.php';

// Verificar si la solicitud es POST
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

// Obtener y validar los datos de la solicitud
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['email']) || !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email no válido']);
    exit();
}

$email = $input['email'];
$status = isset($input['status']) ? (int)$input['status'] : 0;

try {
    // Verificar si el suscriptor existe
    $stmt = $pdo->prepare("SELECT id FROM newsletter WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Suscriptor no encontrado']);
        exit();
    }
    
    // Actualizar el estado del suscriptor
    $stmt = $pdo->prepare("UPDATE newsletter SET activo = ?, updated_at = NOW() WHERE email = ?");
    $result = $stmt->execute([$status, $email]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Estado actualizado correctamente',
            'status' => $status
        ]);
    } else {
        throw new Exception('Error al actualizar el estado del suscriptor');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error en el servidor: ' . $e->getMessage()
    ]);
}
?>
