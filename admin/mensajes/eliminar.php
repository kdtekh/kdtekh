<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/config_common.php';

// Verificar si se proporcionó un ID
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    $_SESSION['error'] = 'ID de mensaje no válido';
    header('Location: index.php');
    exit;
}

try {
    // Verificar si el mensaje existe
    $stmt = $pdo->prepare("SELECT * FROM mensajes_contacto WHERE id = ?");
    $stmt->execute([$id]);
    $mensaje = $stmt->fetch();
    
    if (!$mensaje) {
        $_SESSION['error'] = 'El mensaje que intentas eliminar no existe';
        header('Location: index.php');
        exit;
    }
    
    // Eliminar el mensaje
    $stmt = $pdo->prepare("DELETE FROM mensajes_contacto WHERE id = ?");
    $stmt->execute([$id]);
    
    // Si hay un archivo adjunto, eliminarlo
    if (!empty($mensaje['adjunto']) && file_exists("../uploads/" . $mensaje['adjunto'])) {
        @unlink("../uploads/" . $mensaje['adjunto']);
    }
    
    $_SESSION['success'] = 'Mensaje eliminado correctamente';
    
} catch (PDOException $e) {
    error_log('Error al eliminar mensaje: ' . $e->getMessage());
    $_SESSION['error'] = 'Error al eliminar el mensaje';
}

// Redirigir de vuelta a la página anterior o al listado
$return_url = $_SERVER['HTTP_REFERER'] ?? 'index.php';
header("Location: $return_url");
exit;
?>
