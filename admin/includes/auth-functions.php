<?php
/**
 * Funciones para la autenticación de usuarios
 */

// Incluir archivos necesarios
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/utility-functions.php';

/**
 * Inicia la sesión del usuario
 * 
 * @param array $user Datos del usuario
 * @param bool $remember Si es verdadero, establece una cookie de "recordar sesión"
 * @return void
 */
function loginUser($user, $remember = false) {
    // Iniciar sesión si no está iniciada
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Regenerar el ID de sesión para prevenir fijación de sesión
    session_regenerate_id(true);
    
    // Establecer variables de sesión
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['rol'];
    $_SESSION['user_authenticated'] = true;
    $_SESSION['last_activity'] = time();
    
    // Establecer cookie de "recordar sesión" si es necesario
    if ($remember) {
        $token = bin2hex(random_bytes(32));
        $expires = time() + (30 * 24 * 60 * 60); // 30 días
        
        // Guardar el token en la base de datos
        $table = DB::table('user_sessions');
        $sql = "INSERT INTO $table (user_id, token, expires_at, user_agent, ip_address) 
                VALUES (:user_id, :token, :expires_at, :user_agent, :ip_address)";
        
        $params = [
            ':user_id' => $user['id'],
            ':token' => password_hash($token, PASSWORD_DEFAULT),
            ':expires_at' => date('Y-m-d H:i:s', $expires),
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
        ];
        
        try {
            DB::query($sql, $params);
            
            // Establecer la cookie
            setcookie(
                'remember_token',
                $user['id'] . ':' . $token,
                [
                    'expires' => $expires,
                    'path' => '/',
                    'domain' => $_SERVER['HTTP_HOST'],
                    'secure' => isset($_SERVER['HTTPS']),
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]
            );
        } catch (Exception $e) {
            error_log("Error al guardar el token de sesión: " . $e->getMessage());
        }
    }
    
    // Registrar el inicio de sesión
    logUserActivity($user['id'], 'login', 'Inicio de sesión exitoso');
}

/**
 * Cierra la sesión del usuario actual
 * 
 * @return void
 */
function logoutUser() {
    // Iniciar sesión si no está iniciada
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Eliminar el token de "recordar sesión" si existe
    if (isset($_COOKIE['remember_token'])) {
        list($userId, $token) = explode(':', $_COOKIE['remember_token']);
        
        // Eliminar el token de la base de datos
        $table = DB::table('user_sessions');
        $sql = "DELETE FROM $table WHERE user_id = :user_id AND token LIKE :token";
        
        try {
            DB::query($sql, [
                ':user_id' => $userId,
                ':token' => '%' . substr($token, -32) // Usar solo los últimos 32 caracteres para la búsqueda
            ]);
        } catch (Exception $e) {
            error_log("Error al eliminar el token de sesión: " . $e->getMessage());
        }
        
        // Eliminar la cookie
        setcookie('remember_token', '', time() - 3600, '/', $_SERVER['HTTP_HOST'], isset($_SERVER['HTTPS']), true, ['samesite' => 'Lax']);
    }
    
    // Registrar el cierre de sesión si el usuario está autenticado
    if (isUserLoggedIn()) {
        logUserActivity($_SESSION['user_id'], 'logout', 'Cierre de sesión exitoso');
    }
    
    // Destruir la sesión
    $_SESSION = [];
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    
    session_destroy();
}

/**
 * Verifica si el usuario está autenticado
 * 
 * @return bool Verdadero si el usuario está autenticado, falso en caso contrario
 */
function isUserLoggedIn() {
    // Iniciar sesión si no está iniciada
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Verificar si la sesión está activa
    if (isset($_SESSION['user_authenticated']) && $_SESSION['user_authenticated'] === true) {
        // Verificar la inactividad de la sesión (30 minutos)
        $inactive = 1800; // 30 minutos en segundos
        
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $inactive) {
            // La sesión ha expirado por inactividad
            logoutUser();
            return false;
        }
        
        // Actualizar la marca de tiempo de la última actividad
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    // Verificar la cookie de "recordar sesión"
    if (isset($_COOKIE['remember_token'])) {
        list($userId, $token) = explode(':', $_COOKIE['remember_token']);
        
        // Buscar el token en la base de datos
        $table = DB::table('user_sessions');
        $sql = "SELECT * FROM $table 
                WHERE user_id = :user_id 
                AND expires_at > NOW()";
        
        try {
            $sessions = DB::fetchAll($sql, [':user_id' => $userId]);
            
            foreach ($sessions as $session) {
                if (password_verify($token, $session['token'])) {
                    // Token válido, iniciar sesión
                    $user = getUserById($userId);
                    
                    if ($user) {
                        // Eliminar el token usado
                        DB::query("DELETE FROM $table WHERE id = :id", [':id' => $session['id']]);
                        
                        // Iniciar sesión y crear un nuevo token
                        loginUser($user, true);
                        return true;
                    }
                }
            }
            
            // Si llegamos aquí, el token no es válido o ha expirado
            setcookie('remember_token', '', time() - 3600, '/', $_SERVER['HTTP_HOST'], isset($_SERVER['HTTPS']), true, ['samesite' => 'Lax']);
        } catch (Exception $e) {
            error_log("Error al verificar el token de sesión: " . $e->getMessage());
        }
    }
    
    return false;
}

/**
 * Verifica si el usuario tiene un rol específico
 * 
 * @param string|array $roles Rol o array de roles a verificar
 * @return bool Verdadero si el usuario tiene el rol, falso en caso contrario
 */
function hasRole($roles) {
    if (!isUserLoggedIn()) {
        return false;
    }
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $userRole = $_SESSION['user_role'] ?? null;
    
    if (is_array($roles)) {
        return in_array($userRole, $roles);
    }
    
    return $userRole === $roles;
}

/**
 * Requiere que el usuario tenga un rol específico
 * 
 * @param string|array $roles Rol o array de roles requeridos
 * @return void
 * @throws Exception Si el usuario no tiene el rol requerido
 */
function requireRole($roles) {
    if (!isUserLoggedIn()) {
        // Guardar la URL actual para redirigir después del inicio de sesión
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        
        // Redirigir al formulario de inicio de sesión
        redirect(ADMIN_URL . '/login.php');
        exit();
    }
    
    if (!hasRole($roles)) {
        // Usuario no autorizado
        http_response_code(403);
        die('Acceso denegado. No tienes permiso para acceder a esta página.');
    }
}

/**
 * Registra una actividad del usuario
 * 
 * @param int $userId ID del usuario
 * @param string $action Acción realizada (ej: 'login', 'logout', 'create', 'update', 'delete')
 * @param string $description Descripción de la actividad
 * @param string $ipAddress Dirección IP del usuario (opcional)
 * @param string $userAgent Agente de usuario (opcional)
 * @return bool Verdadero si se registró la actividad, falso en caso contrario
 */
function logUserActivity($userId, $action, $description, $ipAddress = null, $userAgent = null) {
    $table = DB::table('user_activity_logs');
    
    $sql = "INSERT INTO $table (
                user_id, action, description, ip_address, user_agent, created_at
            ) VALUES (
                :user_id, :action, :description, :ip_address, :user_agent, NOW()
            )";
    
    $params = [
        ':user_id' => $userId,
        ':action' => $action,
        ':description' => $description,
        ':ip_address' => $ipAddress ?? ($_SERVER['REMOTE_ADDR'] ?? null),
        ':user_agent' => $userAgent ?? ($_SERVER['HTTP_USER_AGENT'] ?? null)
    ];
    
    try {
        DB::query($sql, $params);
        return true;
    } catch (Exception $e) {
        error_log("Error al registrar la actividad del usuario: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtiene el historial de actividades de un usuario
 * 
 * @param int $userId ID del usuario (opcional, si no se proporciona, se usa el usuario actual)
 * @param int $limit Límite de resultados (opcional)
 * @param int $offset Desplazamiento (opcional)
 * @return array Lista de actividades
 */
function getUserActivityLogs($userId = null, $limit = null, $offset = 0) {
    if ($userId === null && isUserLoggedIn()) {
        $userId = $_SESSION['user_id'];
    }
    
    if ($userId === null) {
        return [];
    }
    
    $table = DB::table('user_activity_logs');
    $sql = "SELECT * FROM $table WHERE user_id = :user_id ORDER BY created_at DESC";
    
    if ($limit !== null) {
        $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
    }
    
    return DB::fetchAll($sql, [':user_id' => $userId]);
}

/**
 * Genera un token de restablecimiento de contraseña
 * 
 * @param string $email Correo electrónico del usuario
 * @return string|false Token generado o false en caso de error
 */
function generatePasswordResetToken($email) {
    $user = getUserByEmail($email);
    
    if (!$user) {
        return false;
    }
    
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + 3600); // Expira en 1 hora
    
    $table = DB::table('password_resets');
    
    // Eliminar tokens existentes para este correo
    DB::query("DELETE FROM $table WHERE email = :email", [':email' => $email]);
    
    // Insertar el nuevo token
    $sql = "INSERT INTO $table (email, token, created_at) 
            VALUES (:email, :token, :created_at)";
    
    try {
        DB::query($sql, [
            ':email' => $email,
            ':token' => password_hash($token, PASSWORD_DEFAULT),
            ':created_at' => date('Y-m-d H:i:s')
        ]);
        
        return $token;
    } catch (Exception $e) {
        error_log("Error al generar el token de restablecimiento: " . $e->getMessage());
        return false;
    }
}

/**
 * Verifica un token de restablecimiento de contraseña
 * 
 * @param string $email Correo electrónico del usuario
 * @param string $token Token a verificar
 * @return bool Verdadero si el token es válido, falso en caso contrario
 */
function verifyPasswordResetToken($email, $token) {
    $table = DB::table('password_resets');
    
    $sql = "SELECT * FROM $table 
            WHERE email = :email 
            AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ORDER BY created_at DESC 
            LIMIT 1";
    
    try {
        $result = DB::fetch($sql, [':email' => $email]);
        
        if ($result && password_verify($token, $result['token'])) {
            return true;
        }
    } catch (Exception $e) {
        error_log("Error al verificar el token de restablecimiento: " . $e->getMessage());
    }
    
    return false;
}

/**
 * Actualiza la contraseña de un usuario
 * 
 * @param string $email Correo electrónico del usuario
 * @param string $password Nueva contraseña (sin encriptar)
 * @return bool Verdadero si se actualizó la contraseña, falso en caso contrario
 */
function updateUserPassword($email, $password) {
    $user = getUserByEmail($email);
    
    if (!$user) {
        return false;
    }
    
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        $table = DB::table('usuarios');
        $sql = "UPDATE $table SET password = :password WHERE email = :email";
        
        DB::query($sql, [
            ':password' => $hashedPassword,
            ':email' => $email
        ]);
        
        // Eliminar los tokens de restablecimiento
        $table = DB::table('password_resets');
        DB::query("DELETE FROM $table WHERE email = :email", [':email' => $email]);
        
        // Registrar la actividad
        logUserActivity($user['id'], 'password_reset', 'Contraseña restablecida con éxito');
        
        return true;
    } catch (Exception $e) {
        error_log("Error al actualizar la contraseña: " . $e->getMessage());
        return false;
    }
}

/**
 * Envía un correo electrónico de restablecimiento de contraseña
 * 
 * @param string $email Correo electrónico del destinatario
 * @param string $token Token de restablecimiento
 * @return bool Verdadero si el correo se envió correctamente, falso en caso contrario
 */
function sendPasswordResetEmail($email, $token) {
    $resetLink = SITE_URL . '/admin/reset-password.php?email=' . urlencode($email) . '&token=' . $token;
    
    $subject = 'Restablecer tu contraseña';
    $message = "
        <h2>Restablecer contraseña</h2>
        <p>Hemos recibido una solicitud para restablecer la contraseña de tu cuenta.</p>
        <p>Para continuar, haz clic en el siguiente enlace:</p>
        <p><a href=\"$resetLink\">Restablecer contraseña</a></p>
        <p>Si no has solicitado este cambio, puedes ignorar este mensaje.</p>
        <p>Este enlace expirará en 1 hora.</p>
    ";
    
    $headers = [
        'From: ' . SITE_EMAIL,
        'Content-Type: text/html; charset=UTF-8'
    ];
    
    try {
        return mail($email, $subject, $message, implode("\r\n", $headers));
    } catch (Exception $e) {
        error_log("Error al enviar el correo de restablecimiento: " . $e->getMessage());
        return false;
    }
}

/**
 * Verifica si un usuario tiene permiso para realizar una acción específica
 * 
 * @param string $permission Permiso a verificar
 * @param int $userId ID del usuario (opcional, si no se proporciona, se usa el usuario actual)
 * @return bool Verdadero si el usuario tiene el permiso, falso en caso contrario
 */
function hasPermission($permission, $userId = null) {
    if ($userId === null) {
        if (!isUserLoggedIn()) {
            return false;
        }
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $userId = $_SESSION['user_id'];
        $userRole = $_SESSION['user_role'];
    } else {
        $user = getUserById($userId);
        
        if (!$user) {
            return false;
        }
        
        $userRole = $user['rol'];
    }
    
    // Si el usuario es administrador, tiene todos los permisos
    if ($userRole === 'admin') {
        return true;
    }
    
    // Obtener los permisos del rol del usuario
    $table = DB::table('role_permissions');
    $sql = "SELECT * FROM $table WHERE role = :role AND permission = :permission";
    
    try {
        $result = DB::fetch($sql, [
            ':role' => $userRole,
            ':permission' => $permission
        ]);
        
        return !empty($result);
    } catch (Exception $e) {
        error_log("Error al verificar el permiso: " . $e->getMessage());
        return false;
    }
}

/**
 * Requiere que el usuario tenga un permiso específico
 * 
 * @param string $permission Permiso requerido
 * @return void
 * @throws Exception Si el usuario no tiene el permiso requerido
 */
function requirePermission($permission) {
    if (!hasPermission($permission)) {
        http_response_code(403);
        die('Acceso denegado. No tienes permiso para realizar esta acción.');
    }
}
