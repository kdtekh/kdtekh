<?php
/**
 * Manejador de peticiones AJAX para el panel de administración
 */

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir archivos necesarios
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth-functions.php';

// Verificar si es una petición AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die(json_encode(['error' => 'Acceso denegado']));
}

// Verificar si el usuario está autenticado
if (!isUserLoggedIn()) {
    http_response_code(401);
    die(json_encode(['error' => 'No autorizado']));
}

// Obtener la acción solicitada
$action = isset($_POST['action']) ? trim($_POST['action']) : '';

// Validar el token CSRF
if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    http_response_code(403);
    die(json_encode(['error' => 'Token CSRF no válido']));
}

// Array para la respuesta
$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

try {
    // Ejecutar la acción solicitada
    switch ($action) {
        case 'delete_item':
            // Eliminar un elemento
            if (!isset($_POST['table']) || !isset($_POST['id'])) {
                throw new Exception('Parámetros incompletos');
            }
            
            $table = $_POST['table'];
            $id = (int)$_POST['id'];
            
            // Verificar permisos
            if (!hasPermission('delete_' . $table)) {
                throw new Exception('No tienes permiso para realizar esta acción');
            }
            
            // Eliminar el elemento
            $sql = "DELETE FROM $table WHERE id = :id";
            $result = DB::query($sql, [':id' => $id]);
            
            if ($result->rowCount() > 0) {
                $response['success'] = true;
                $response['message'] = 'Elemento eliminado correctamente';
                
                // Registrar la actividad
                logUserActivity($_SESSION['user_id'], 'delete', "Eliminado elemento con ID $id de la tabla $table");
            } else {
                throw new Exception('No se pudo eliminar el elemento');
            }
            break;
            
        case 'update_status':
            // Actualizar el estado de un elemento
            if (!isset($_POST['table']) || !isset($_POST['id']) || !isset($_POST['field']) || !isset($_POST['value'])) {
                throw new Exception('Parámetros incompletos');
            }
            
            $table = $_POST['table'];
            $id = (int)$_POST['id'];
            $field = $_POST['field'];
            $value = $_POST['value'];
            
            // Verificar permisos
            if (!hasPermission('edit_' . $table)) {
                throw new Exception('No tienes permiso para realizar esta acción');
            }
            
            // Actualizar el estado
            $sql = "UPDATE $table SET $field = :value, updated_at = NOW() WHERE id = :id";
            $result = DB::query($sql, [':value' => $value, ':id' => $id]);
            
            if ($result->rowCount() > 0) {
                $response['success'] = true;
                $response['message'] = 'Estado actualizado correctamente';
                
                // Registrar la actividad
                logUserActivity($_SESSION['user_id'], 'update', "Actualizado campo $field a '$value' en la tabla $table para el ID $id");
            } else {
                throw new Exception('No se pudo actualizar el estado');
            }
            break;
            
        case 'get_item':
            // Obtener un elemento por ID
            if (!isset($_POST['table']) || !isset($_POST['id'])) {
                throw new Exception('Parámetros incompletos');
            }
            
            $table = $_POST['table'];
            $id = (int)$_POST['id'];
            
            // Verificar permisos
            if (!hasPermission('view_' . $table)) {
                throw new Exception('No tienes permiso para ver este elemento');
            }
            
            // Obtener el elemento
            $sql = "SELECT * FROM $table WHERE id = :id";
            $item = DB::fetch($sql, [':id' => $id]);
            
            if ($item) {
                $response['success'] = true;
                $response['data'] = $item;
                
                // Registrar la actividad
                logUserActivity($_SESSION['user_id'], 'view', "Visto elemento con ID $id de la tabla $table");
            } else {
                throw new Exception('Elemento no encontrado');
            }
            break;
            
        case 'search':
            // Buscar elementos
            if (!isset($_POST['table']) || !isset($_POST['query'])) {
                throw new Exception('Parámetros incompletos');
            }
            
            $table = $_POST['table'];
            $query = '%' . $_POST['query'] . '%';
            $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 10;
            $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
            $offset = ($page - 1) * $limit;
            
            // Verificar permisos
            if (!hasPermission('view_' . $table)) {
                throw new Exception('No tienes permiso para realizar búsquedas');
            }
            
            // Obtener columnas de la tabla
            $columns = [];
            $result = DB::query("SHOW COLUMNS FROM $table");
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $columns[] = $row['Field'];
            }
            
            // Construir la consulta de búsqueda
            $conditions = [];
            $params = [':query' => $query];
            
            foreach ($columns as $i => $column) {
                $param = ":query$i";
                $conditions[] = "$column LIKE $param";
                $params[$param] = $query;
            }
            
            $where = implode(' OR ', $conditions);
            
            // Contar resultados totales
            $countSql = "SELECT COUNT(*) as total FROM $table WHERE $where";
            $total = DB::fetchColumn($countSql, $params);
            
            // Obtener resultados
            $sql = "SELECT * FROM $table WHERE $where LIMIT :offset, :limit";
            $params[':offset'] = $offset;
            $params[':limit'] = $limit;
            
            $items = DB::fetchAll($sql, $params);
            
            $response['success'] = true;
            $response['data'] = [
                'items' => $items,
                'total' => $total,
                'pages' => ceil($total / $limit),
                'current_page' => $page
            ];
            
            // Registrar la actividad
            logUserActivity($_SESSION['user_id'], 'search', "Búsqueda en la tabla $table: " . $_POST['query']);
            break;
            
        case 'reorder_items':
            // Reordenar elementos
            if (!isset($_POST['table']) || !isset($_POST['items'])) {
                throw new Exception('Parámetros incompletos');
            }
            
            $table = $_POST['table'];
            $items = json_decode($_POST['items'], true);
            
            if (!is_array($items)) {
                throw new Exception('Formato de datos inválido');
            }
            
            // Verificar permisos
            if (!hasPermission('edit_' . $table)) {
                throw new Exception('No tienes permiso para reordenar elementos');
            }
            
            // Iniciar transacción
            DB::beginTransaction();
            
            try {
                foreach ($items as $position => $itemId) {
                    $sql = "UPDATE $table SET position = :position, updated_at = NOW() WHERE id = :id";
                    DB::query($sql, [':position' => $position, ':id' => $itemId]);
                }
                
                DB::commit();
                
                $response['success'] = true;
                $response['message'] = 'Elementos reordenados correctamente';
                
                // Registrar la actividad
                logUserActivity($_SESSION['user_id'], 'reorder', "Reordenados elementos en la tabla $table");
                
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
            break;
            
        case 'upload_file':
            // Subir un archivo
            if (empty($_FILES)) {
                throw new Exception('No se ha subido ningún archivo');
            }
            
            // Incluir el manejador de subidas
            require_once __DIR__ . '/upload-handler.php';
            exit(); // El manejador de subidas ya envía la respuesta
            
        case 'get_notifications':
            // Obtener notificaciones
            $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 10;
            
            $sql = "SELECT * FROM notifications 
                    WHERE (user_id = :user_id OR user_id IS NULL) 
                    AND (read_at IS NULL OR read_at = '') 
                    ORDER BY created_at DESC 
                    LIMIT :limit";
            
            $notifications = DB::fetchAll($sql, [':user_id' => $_SESSION['user_id'], ':limit' => $limit]);
            
            // Contar notificaciones no leídas
            $unreadCount = DB::fetchColumn(
                "SELECT COUNT(*) FROM notifications 
                 WHERE (user_id = :user_id OR user_id IS NULL) 
                 AND (read_at IS NULL OR read_at = '')",
                [':user_id' => $_SESSION['user_id']]
            );
            
            $response['success'] = true;
            $response['data'] = [
                'notifications' => $notifications,
                'unread_count' => (int)$unreadCount
            ];
            break;
            
        case 'mark_notification_read':
            // Marcar notificación como leída
            if (!isset($_POST['notification_id'])) {
                throw new Exception('ID de notificación no especificado');
            }
            
            $notificationId = (int)$_POST['notification_id'];
            
            $sql = "UPDATE notifications SET read_at = NOW() WHERE id = :id AND (user_id = :user_id OR user_id IS NULL)";
            $result = DB::query($sql, [':id' => $notificationId, ':user_id' => $_SESSION['user_id']]);
            
            $response['success'] = $result->rowCount() > 0;
            $response['message'] = $response['success'] ? 'Notificación marcada como leída' : 'No se pudo actualizar la notificación';
            break;
            
        case 'mark_all_notifications_read':
            // Marcar todas las notificaciones como leídas
            $sql = "UPDATE notifications SET read_at = NOW() 
                    WHERE (user_id = :user_id OR user_id IS NULL) 
                    AND (read_at IS NULL OR read_at = '')";
            
            $result = DB::query($sql, [':user_id' => $_SESSION['user_id']]);
            
            $response['success'] = true;
            $response['message'] = 'Todas las notificaciones marcadas como leídas';
            break;
            
        case 'get_user_profile':
            // Obtener perfil de usuario
            $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : $_SESSION['user_id'];
            
            // Verificar permisos (solo el propio usuario o un administrador pueden ver el perfil)
            if ($userId !== $_SESSION['user_id'] && !hasRole(['admin', 'superadmin'])) {
                throw new Exception('No tienes permiso para ver este perfil');
            }
            
            $user = DB::fetch("SELECT * FROM users WHERE id = :id", [':id' => $userId]);
            
            if (!$user) {
                throw new Exception('Usuario no encontrado');
            }
            
            // Ocultar información sensible
            unset($user['password'], $user['remember_token'], $user['reset_token']);
            
            $response['success'] = true;
            $response['data'] = $user;
            break;
            
        case 'update_user_profile':
            // Actualizar perfil de usuario
            $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : $_SESSION['user_id'];
            
            // Verificar permisos (solo el propio usuario o un administrador pueden actualizar el perfil)
            if ($userId !== $_SESSION['user_id'] && !hasRole(['admin', 'superadmin'])) {
                throw new Exception('No tienes permiso para actualizar este perfil');
            }
            
            // Validar campos requeridos
            $requiredFields = ['name', 'email'];
            foreach ($requiredFields as $field) {
                if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
                    throw new Exception("El campo $field es requerido");
                }
            }
            
            // Validar correo electrónico
            $email = trim($_POST['email']);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('El correo electrónico no es válido');
            }
            
            // Verificar si el correo ya existe
            $existingUser = DB::fetch("SELECT id FROM users WHERE email = :email AND id != :id", 
                [':email' => $email, ':id' => $userId]);
                
            if ($existingUser) {
                throw new Exception('El correo electrónico ya está en uso');
            }
            
            // Actualizar datos del usuario
            $updateData = [
                'name' => trim($_POST['name']),
                'email' => $email,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Campos opcionales
            $optionalFields = ['phone', 'bio', 'website', 'facebook', 'twitter', 'instagram', 'linkedin'];
            foreach ($optionalFields as $field) {
                if (isset($_POST[$field])) {
                    $updateData[$field] = trim($_POST[$field]);
                }
            }
            
            // Actualizar contraseña si se proporcionó
            if (!empty($_POST['password'])) {
                if (strlen($_POST['password']) < 8) {
                    throw new Exception('La contraseña debe tener al menos 8 caracteres');
                }
                $updateData['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
            }
            
            // Construir la consulta de actualización
            $setClauses = [];
            $params = [':id' => $userId];
            
            foreach ($updateData as $field => $value) {
                $param = ":$field";
                $setClauses[] = "$field = $param";
                $params[$param] = $value;
            }
            
            $sql = "UPDATE users SET " . implode(', ', $setClauses) . " WHERE id = :id";
            $result = DB::query($sql, $params);
            
            if ($result->rowCount() > 0) {
                $response['success'] = true;
                $response['message'] = 'Perfil actualizado correctamente';
                
                // Actualizar datos de sesión si es el usuario actual
                if ($userId === $_SESSION['user_id']) {
                    $_SESSION['user_name'] = $updateData['name'];
                    $_SESSION['user_email'] = $updateData['email'];
                    
                    // Actualizar otros campos de sesión si es necesario
                    foreach (['phone', 'avatar'] as $field) {
                        if (isset($updateData[$field])) {
                            $_SESSION["user_$field"] = $updateData[$field];
                        }
                    }
                }
                
                // Registrar la actividad
                logUserActivity($_SESSION['user_id'], 'update', 'Perfil de usuario actualizado');
                
            } else {
                throw new Exception('No se pudo actualizar el perfil');
            }
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    $response['error'] = $e->getMessage();
    
    // Registrar el error
    if (isset($_SESSION['user_id'])) {
        logUserActivity($_SESSION['user_id'], 'error', 'Error en AJAX: ' . $e->getMessage());
    }
}

// Enviar respuesta
header('Content-Type: application/json');
echo json_encode($response);

exit();
