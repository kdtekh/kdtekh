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
$nombre = trim($_POST['nombre'] ?? '');
$apellidos = trim($_POST['apellidos'] ?? '');
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$rol = in_array($_POST['rol'] ?? '', ['administrador', 'editor', 'usuario', 'lector']) ? $_POST['rol'] : 'usuario';
$activo = isset($_POST['activo']) ? (int)$_POST['activo'] : 0;
$cambiar_password = isset($_POST['cambiar_password']) && !empty(trim($_POST['nueva_password']));
$nueva_password = $cambiar_password ? trim($_POST['nueva_password']) : null;

// Validaciones
$errores = [];

if ($id <= 0) {
    $errores[] = 'ID de usuario no válido';
}

if (empty($nombre)) {
    $errores[] = 'El nombre es obligatorio';
}

if (!$email) {
    $errores[] = 'El correo electrónico no es válido';
}

if ($cambiar_password && strlen($nueva_password) < 8) {
    $errores[] = 'La nueva contraseña debe tener al menos 8 caracteres';
}

if (!empty($errores)) {
    header('HTTP/1.1 400 Bad Request');
    exit(json_encode(['success' => false, 'message' => 'Error de validación', 'errors' => $errores]));
}

try {
    // Iniciar transacción
    $pdo->beginTransaction();
    
    // Verificar que el usuario existe
    $stmt = $pdo->prepare("SELECT id, email FROM usuarios_registrados WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Usuario no encontrado');
    }
    
    $usuario_actual = $stmt->fetch();
    
    // Verificar si el correo ya está en uso por otro usuario
    if ($email !== $usuario_actual['email']) {
        $stmt = $pdo->prepare("SELECT id FROM usuarios_registrados WHERE email = ? AND id != ?");
        $stmt->execute([$email, $id]);
        
        if ($stmt->rowCount() > 0) {
            throw new Exception('El correo electrónico ya está en uso por otro usuario');
        }
    }
    
    // Actualizar los datos del usuario
    $params = [
        'nombre' => $nombre,
        'apellidos' => $apellidos,
        'email' => $email,
        'rol' => $rol,
        'activo' => $activo,
        'id' => $id
    ];
    
    $sql = "UPDATE usuarios_registrados SET 
                nombre = :nombre,
                apellidos = :apellidos,
                email = :email,
                rol = :rol,
                activo = :activo,
                fecha_actualizacion = NOW()";
    
    // Si se está cambiando la contraseña
    if ($cambiar_password) {
        $password_hash = password_hash($nueva_password, PASSWORD_DEFAULT);
        $sql .= ", password = :password";
        $params['password'] = $password_hash;
    }
    
    $sql .= " WHERE id = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    // Registrar la acción
    logAccion('usuarios', 'actualizar', "Actualizó la información del usuario con ID $id");
    
    // Confirmar la transacción
    $pdo->commit();
    
    // Devolver respuesta exitosa
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Usuario actualizado correctamente'
    ]);
    
} catch (Exception $e) {
    // Revertir la transacción en caso de error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Registrar el error
    error_log("Error al actualizar usuario: " . $e->getMessage());
    
    // Devolver error
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => DEBUG_MODE ? $e->getTraceAsString() : null
    ]);
}
?>
