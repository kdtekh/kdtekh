<?php
// Incluir configuración común
require_once '../../includes/config.php';

header('Content-Type: application/json');

// Verificar autenticación
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

try {
    // Obtener estadísticas
    $stats = [];
    
    // Contar mensajes de contacto
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM mensajes_contacto");
    $stats['totalMensajes'] = (int)$stmt->fetch()['total'];
    
    // Contar mensajes no leídos
    $stmt = $pdo->query("SELECT COUNT(*) as noLeidos FROM mensajes_contacto WHERE leido = 0");
    $stats['mensajesNoLeidos'] = (int)$stmt->fetch()['noLeidos'];
    
    // Contar suscriptores
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM newsletter WHERE activo = 1");
    $stats['totalSuscriptores'] = (int)$stmt->fetch()['total'];
    
    // Contar nuevos suscriptores (últimos 7 días)
    $stmt = $pdo->query("SELECT COUNT(*) as nuevos FROM newsletter WHERE activo = 1 AND fecha_registro >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stats['nuevosSuscriptores'] = (int)$stmt->fetch()['nuevos'];
    
    // Obtener los últimos mensajes
    $stmt = $pdo->query("SELECT * FROM mensajes_contacto ORDER BY fecha DESC LIMIT 5");
    $stats['ultimosMensajes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener las estadísticas: ' . $e->getMessage()
    ]);
}
?>
