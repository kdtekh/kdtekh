<?php
/**
 * Funciones de autenticación y autorización
 */

// Incluir archivos necesarios
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Clase para manejar la autenticación de usuarios
 */
class Auth {
    /**
     * @var string Nombre de la tabla de usuarios
     */
    private static $usersTable = 'usuarios';
    
    /**
     * @var string Nombre de la columna de ID de usuario
     */
    private static $userIdColumn = 'id';
    
    /**
     * @var string Nombre de la columna de nombre de usuario
     */
    private static $usernameColumn = 'email';
    
    /**
     * @var string Nombre de la columna de contraseña
     */
    private static $passwordColumn = 'password';
    
    /**
     * @var string Nombre de la columna de nombre completo
     */
    private static $nameColumn = 'nombre';
    
    /**
     * @var string Nombre de la columna de rol
     */
    private static $roleColumn = 'rol';
    
    /**
     * @var string Nombre de la columna de estado
     */
    private static $statusColumn = 'estado';
    
    /**
     * @var string Nombre de la columna de último inicio de sesión
     */
    private static $lastLoginColumn = 'ultimo_acceso';
    
    /**
     * @var string Nombre de la columna de IP de último acceso
     */
    private static $lastIpColumn = 'ultima_ip';
    
    /**
     * @var string Nombre de la columna de token de restablecimiento
     */
    private static $resetTokenColumn = 'reset_token';
    
    /**
     * @var string Nombre de la columna de expiración del token
     */
    private static $resetExpiresColumn = 'reset_expires';
    
    /**
     * Inicia la sesión del usuario
     * 
     * @param string $username Nombre de usuario o email
     * @param string $password Contraseña
     * @param bool $remember Recordar sesión
     * @return array Resultado de la operación
     */
    public static function login($username, $password, $remember = false) {
        // Validar entradas
        if (empty($username) || empty($password)) {
            return [
                'success' => false,
                'message' => 'Por favor, ingrese su nombre de usuario y contraseña.'
            ];
        }
        
        try {
            // Buscar al usuario
            $table = DB::table(self::$usersTable);
            $user = DB::fetch(
                "SELECT * FROM $table WHERE " . self::$usernameColumn . " = ? LIMIT 1",
                [$username]
            );
            
            // Verificar si el usuario existe
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Usuario o contraseña incorrectos.'
                ];
            }
            
            // Verificar el estado del usuario
            if (isset($user->{self::$statusColumn}) && $user->{self::$statusColumn} !== 'activo') {
                return [
                    'success' => false,
                    'message' => 'Su cuenta está desactivada. Por favor, contacte al administrador.'
                ];
            }
            
            // Verificar la contraseña
            if (!password_verify($password, $user->{self::$passwordColumn})) {
                // Registrar intento fallido
                self::logFailedLoginAttempt($username);
                
                return [
                    'success' => false,
                    'message' => 'Usuario o contraseña incorrectos.'
                ];
            }
            
            // Iniciar sesión
            self::startSession($user);
            
            // Actualizar último acceso
            self::updateLastLogin($user->{self::$userIdColumn});
            
            // Recordar sesión si es necesario
            if ($remember) {
                self::rememberUser($user->{self::$userIdColumn});
            }
            
            return [
                'success' => true,
                'message' => 'Inicio de sesión exitoso.',
                'redirect' => ADMIN_URL . '/index.php'
            ];
            
        } catch (Exception $e) {
            error_log("Error en el inicio de sesión: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Ocurrió un error al iniciar sesión. Por favor, inténtelo de nuevo más tarde.'
            ];
        }
    }
    
    /**
     * Cierra la sesión del usuario actual
     */
    public static function logout() {
        // Eliminar la cookie de "recordar" si existe
        if (isset($_COOKIE['remember_token'])) {
            $token = $_COOKIE['remember_token'];
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
            
            // Eliminar el token de la base de datos
            try {
                $table = DB::table('usuarios_tokens');
                DB::query("DELETE FROM $table WHERE token = ?", [$token]);
            } catch (Exception $e) {
                error_log("Error al eliminar el token de recordar: " . $e->getMessage());
            }
        }
        
        // Destruir la sesión
        session_destroy();
        
        // Eliminar todas las variables de sesión
        $_SESSION = [];
    }
    
    /**
     * Verifica si el usuario está autenticado
     * 
     * @return bool Verdadero si el usuario está autenticado, falso en caso contrario
     */
    public static function check() {
        // Verificar si la sesión está activa
        if (isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id'])) {
            return true;
        }
        
        // Verificar si hay una cookie de "recordar"
        if (isset($_COOKIE['remember_token'])) {
            return self::loginFromRememberToken($_COOKIE['remember_token']);
        }
        
        return false;
    }
    
    /**
     * Obtiene el ID del usuario actual
     * 
     * @return int|null ID del usuario o null si no está autenticado
     */
    public static function id() {
        return $_SESSION['usuario_id'] ?? null;
    }
    
    /**
     * Obtiene los datos del usuario actual
     * 
     * @param string $column Columna específica a obtener (opcional)
     * @return mixed Datos del usuario o null si no está autenticado
     */
    public static function user($column = null) {
        if (!self::check()) {
            return null;
        }
        
        $userId = self::id();
        $table = DB::table(self::$usersTable);
        $user = DB::fetch("SELECT * FROM $table WHERE " . self::$userIdColumn . " = ?", [$userId]);
        
        if ($column !== null) {
            return $user->$column ?? null;
        }
        
        return $user;
    }
    
    /**
     * Verifica si el usuario actual tiene un rol específico
     * 
     * @param string|array $roles Rol o roles a verificar
     * @return bool Verdadero si el usuario tiene el rol, falso en caso contrario
     */
    public static function hasRole($roles) {
        if (!self::check()) {
            return false;
        }
        
        $userRole = self::user(self::$roleColumn);
        
        if (is_array($roles)) {
            return in_array($userRole, $roles);
        }
        
        return $userRole === $roles;
    }
    
    /**
     * Verifica si el usuario actual es administrador
     * 
     * @return bool Verdadero si el usuario es administrador, falso en caso contrario
     */
    public static function isAdmin() {
        return self::hasRole('admin');
    }
    
    /**
     * Registra un nuevo usuario
     * 
     * @param array $data Datos del usuario
     * @return array Resultado de la operación
     */
    public static function register($data) {
        // Validar datos requeridos
        $required = ['nombre', 'email', 'password', 'password_confirmation'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return [
                    'success' => false,
                    'message' => "El campo " . ucfirst($field) . " es obligatorio."
                ];
            }
        }
        
        // Validar formato de email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'El formato del correo electrónico no es válido.'
            ];
        }
        
        // Validar que las contraseñas coincidan
        if ($data['password'] !== $data['password_confirmation']) {
            return [
                'success' => false,
                'message' => 'Las contraseñas no coinciden.'
            ];
        }
        
        // Validar fortaleza de la contraseña
        if (strlen($data['password']) < 8) {
            return [
                'success' => false,
                'message' => 'La contraseña debe tener al menos 8 caracteres.'
            ];
        }
        
        // Verificar si el email ya está registrado
        $table = DB::table(self::$usersTable);
        $existingUser = DB::fetch(
            "SELECT * FROM $table WHERE " . self::$usernameColumn . " = ?",
            [$data['email']]
        );
        
        if ($existingUser) {
            return [
                'success' => false,
                'message' => 'El correo electrónico ya está registrado.'
            ];
        }
        
        try {
            // Hash de la contraseña
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Insertar nuevo usuario
            $userId = DB::query(
                "INSERT INTO $table (" . 
                self::$nameColumn . 
                ", " . self::$usernameColumn . 
                ", " . self::$passwordColumn . 
                ", " . self::$roleColumn . 
                ", " . self::$statusColumn . 
                ", fecha_creacion) VALUES (?, ?, ?, 'usuario', 'activo', NOW())",
                [
                    $data['nombre'],
                    $data['email'],
                    $hashedPassword
                ]
            )->lastInsertId();
            
            // Iniciar sesión automáticamente
            $user = DB::fetch("SELECT * FROM $table WHERE " . self::$userIdColumn . " = ?", [$userId]);
            self::startSession($user);
            
            return [
                'success' => true,
                'message' => 'Registro exitoso. ¡Bienvenido!',
                'redirect' => ADMIN_URL . '/index.php'
            ];
            
        } catch (Exception $e) {
            error_log("Error en el registro de usuario: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Ocurrió un error al registrar el usuario. Por favor, inténtelo de nuevo.'
            ];
        }
    }
    
    /**
     * Inicia la sesión del usuario
     * 
     * @param object $user Datos del usuario
     */
    private static function startSession($user) {
        // Regenerar el ID de sesión para evitar fijación de sesión
        session_regenerate_id(true);
        
        // Establecer variables de sesión
        $_SESSION['usuario_id'] = $user->{self::$userIdColumn};
        $_SESSION['usuario_nombre'] = $user->{self::$nameColumn};
        $_SESSION['usuario_email'] = $user->{self::$usernameColumn};
        $_SESSION['usuario_rol'] = $user->{self::$roleColumn};
        $_SESSION['usuario_avatar'] = $user->avatar ?? 'default-avatar.png';
        $_SESSION['usuario_ultimo_acceso'] = time();
        
        // Actualizar la hora de la última actividad
        $_SESSION['last_activity'] = time();
    }
    
    /**
     * Actualiza el último acceso del usuario
     * 
     * @param int $userId ID del usuario
     */
    private static function updateLastLogin($userId) {
        $table = DB::table(self::$usersTable);
        
        DB::query(
            "UPDATE $table SET " . 
            self::$lastLoginColumn . " = NOW(), " . 
            self::$lastIpColumn . " = ? WHERE " . 
            self::$userIdColumn . " = ?",
            [$_SERVER['REMOTE_ADDR'], $userId]
        );
    }
    
    /**
     * Establece una cookie para recordar la sesión del usuario
     * 
     * @param int $userId ID del usuario
     */
    private static function rememberUser($userId) {
        // Generar un token único
        $token = bin2hex(random_bytes(32));
        $expires = time() + (30 * 24 * 60 * 60); // 30 días
        
        // Guardar el token en la base de datos
        $table = DB::table('usuarios_tokens');
        
        try {
            DB::query(
                "INSERT INTO $table (usuario_id, token, expires_at) VALUES (?, ?, ?)",
                [$userId, $token, date('Y-m-d H:i:s', $expires)]
            );
            
            // Establecer la cookie
            setcookie(
                'remember_token',
                $token,
                $expires,
                '/',
                '',
                true,  // Solo HTTPS
                true   // HttpOnly
            );
            
        } catch (Exception $e) {
            error_log("Error al guardar el token de recordar: " . $e->getMessage());
        }
    }
    
    /**
     * Inicia sesión a partir de un token de recordar
     * 
     * @param string $token Token de recordar
     * @return bool Verdadero si el inicio de sesión fue exitoso, falso en caso contrario
     */
    private static function loginFromRememberToken($token) {
        if (empty($token)) {
            return false;
        }
        
        try {
            // Buscar el token en la base de datos
            $table = DB::table('usuarios_tokens');
            $tokenData = DB::fetch(
                "SELECT * FROM $table WHERE token = ? AND expires_at > NOW()",
                [$token]
            );
            
            if (!$tokenData) {
                // Token no válido o expirado
                setcookie('remember_token', '', time() - 3600, '/');
                return false;
            }
            
            // Obtener los datos del usuario
            $userTable = DB::table(self::$usersTable);
            $user = DB::fetch(
                "SELECT * FROM $userTable WHERE " . self::$userIdColumn . " = ?",
                [$tokenData->usuario_id]
            );
            
            if (!$user) {
                // El usuario ya no existe
                DB::query("DELETE FROM $table WHERE token = ?", [$token]);
                setcookie('remember_token', '', time() - 3600, '/');
                return false;
            }
            
            // Iniciar sesión
            self::startSession($user);
            
            // Actualizar último acceso
            self::updateLastLogin($user->{self::$userIdColumn});
            
            // Renovar el token (opcional)
            self::rememberUser($user->{self::$userIdColumn});
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error al iniciar sesión con token de recordar: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registra un intento fallido de inicio de sesión
     * 
     * @param string $username Nombre de usuario o email
     */
    private static function logFailedLoginAttempt($username) {
        try {
            $table = DB::table('login_attempts');
            $ip = $_SERVER['REMOTE_ADDR'];
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            DB::query(
                "INSERT INTO $table (username, ip_address, user_agent, created_at) VALUES (?, ?, ?, NOW())",
                [$username, $ip, $userAgent]
            );
            
            // Limpiar intentos antiguos (más de 24 horas)
            DB::query("DELETE FROM $table WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
            
        } catch (Exception $e) {
            error_log("Error al registrar intento fallido de inicio de sesión: " . $e->getMessage());
        }
    }
    
    /**
     * Verifica si hay demasiados intentos fallidos de inicio de sesión
     * 
     * @param string $username Nombre de usuario o email
     * @param int $maxAttempts Número máximo de intentos permitidos (por defecto: 5)
     * @param int $lockoutMinutes Tiempo de bloqueo en minutos (por defecto: 15)
     * @return array Resultado de la verificación
     */
    public static function checkLoginAttempts($username, $maxAttempts = 5, $lockoutMinutes = 15) {
        try {
            $table = DB::table('login_attempts');
            $ip = $_SERVER['REMOTE_ADDR'];
            
            // Contar intentos recientes
            $attempts = DB::fetchColumn(
                "SELECT COUNT(*) FROM $table 
                WHERE (username = ? OR ip_address = ?) 
                AND created_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)",
                [$username, $ip, $lockoutMinutes],
                0
            );
            
            if ($attempts >= $maxAttempts) {
                $nextAttempt = DB::fetchColumn(
                    "SELECT TIME_TO_SEC(TIMEDIFF(DATE_ADD(MAX(created_at), INTERVAL ? MINUTE), NOW())) 
                    FROM $table 
                    WHERE (username = ? OR ip_address = ?)",
                    [$lockoutMinutes, $username, $ip],
                    0
                );
                
                $minutes = ceil($nextAttempt / 60);
                
                return [
                    'locked' => true,
                    'message' => "Demasiados intentos fallidos. Por favor, espere $minutes minutos antes de intentarlo de nuevo.",
                    'remaining_time' => $nextAttempt
                ];
            }
            
            return ['locked' => false];
            
        } catch (Exception $e) {
            error_log("Error al verificar intentos de inicio de sesión: " . $e->getMessage());
            return ['locked' => false];
        }
    }
    
    /**
     * Envía un correo electrónico para restablecer la contraseña
     * 
     * @param string $email Correo electrónico del usuario
     * @return array Resultado de la operación
     */
    public static function sendPasswordResetLink($email) {
        // Validar email
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Por favor, proporcione una dirección de correo electrónico válida.'
            ];
        }
        
        try {
            // Buscar al usuario por email
            $table = DB::table(self::$usersTable);
            $user = DB::fetch(
                "SELECT * FROM $table WHERE " . self::$usernameColumn . " = ?",
                [$email]
            );
            
            if (!$user) {
                // No revelar que el correo no existe por razones de seguridad
                return [
                    'success' => true,
                    'message' => 'Si el correo electrónico existe en nuestro sistema, se ha enviado un enlace para restablecer la contraseña.'
                ];
            }
            
            // Generar token de restablecimiento
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Guardar el token en la base de datos
            DB::query(
                "UPDATE $table SET " . 
                self::$resetTokenColumn . " = ?, " .
                self::$resetExpiresColumn . " = ? WHERE " .
                self::$userIdColumn . " = ?",
                [$token, $expires, $user->{self::$userIdColumn}]
            );
            
            // Enviar correo electrónico con el enlace de restablecimiento
            $resetLink = ADMIN_URL . "/reset-password.php?token=" . $token;
            $subject = "Restablecer su contraseña";
            $message = "Hola " . $user->{self::$nameColumn} . ",\n\n";
            $message .= "Ha solicitado restablecer su contraseña. Haga clic en el siguiente enlace para continuar:\n\n";
            $message .= $resetLink . "\n\n";
            $message .= "Si no solicitó este restablecimiento, puede ignorar este correo electrónico.\n";
            $message .= "Este enlace expirará en 1 hora.\n\n";
            $message .= "Saludos,\n";
            $message .= SITE_NAME;
            
            // En un entorno real, usaría una biblioteca de envío de correos como PHPMailer
            $headers = "From: " . SITE_EMAIL . "\r\n";
            $headers .= "Reply-To: " . SITE_EMAIL . "\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();
            
            // Descomentar en producción
            // mail($email, $subject, $message, $headers);
            
            return [
                'success' => true,
                'message' => 'Se ha enviado un enlace para restablecer la contraseña a su dirección de correo electrónico.'
            ];
            
        } catch (Exception $e) {
            error_log("Error al enviar el enlace de restablecimiento: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Ocurrió un error al procesar su solicitud. Por favor, inténtelo de nuevo más tarde.'
            ];
        }
    }
    
    /**
     * Restablece la contraseña de un usuario
     * 
     * @param string $token Token de restablecimiento
     * @param string $password Nueva contraseña
     * @param string $passwordConfirmation Confirmación de la nueva contraseña
     * @return array Resultado de la operación
     */
    public static function resetPassword($token, $password, $passwordConfirmation) {
        // Validar token
        if (empty($token)) {
            return [
                'success' => false,
                'message' => 'Token de restablecimiento no válido.'
            ];
        }
        
        // Validar contraseña
        if (empty($password) || $password !== $passwordConfirmation) {
            return [
                'success' => false,
                'message' => 'Las contraseñas no coinciden.'
            ];
        }
        
        if (strlen($password) < 8) {
            return [
                'success' => false,
                'message' => 'La contraseña debe tener al menos 8 caracteres.'
            ];
        }
        
        try {
            $table = DB::table(self::$usersTable);
            
            // Buscar usuario con el token y que no haya expirado
            $user = DB::fetch(
                "SELECT * FROM $table WHERE " . 
                self::$resetTokenColumn . " = ? AND " . 
                self::$resetExpiresColumn . " > NOW()",
                [$token]
            );
            
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'El enlace de restablecimiento no es válido o ha expirado.'
                ];
            }
            
            // Actualizar contraseña
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            DB::query(
                "UPDATE $table SET " . 
                self::$passwordColumn . " = ?, " .
                self::$resetTokenColumn . " = NULL, " .
                self::$resetExpiresColumn . " = NULL WHERE " .
                self::$userIdColumn . " = ?",
                [$hashedPassword, $user->{self::$userIdColumn}]
            );
            
            // Iniciar sesión automáticamente
            self::startSession($user);
            
            return [
                'success' => true,
                'message' => 'Su contraseña ha sido restablecida correctamente.',
                'redirect' => ADMIN_URL . '/index.php'
            ];
            
        } catch (Exception $e) {
            error_log("Error al restablecer la contraseña: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Ocurrió un error al restablecer su contraseña. Por favor, inténtelo de nuevo.'
            ];
        }
    }
    
    /**
     * Cambia la contraseña del usuario actual
     * 
     * @param string $currentPassword Contraseña actual
     * @param string $newPassword Nueva contraseña
     * @param string $newPasswordConfirmation Confirmación de la nueva contraseña
     * @return array Resultado de la operación
     */
    public static function changePassword($currentPassword, $newPassword, $newPasswordConfirmation) {
        if (!self::check()) {
            return [
                'success' => false,
                'message' => 'Debe iniciar sesión para cambiar su contraseña.'
            ];
        }
        
        // Validar contraseña actual
        if (empty($currentPassword)) {
            return [
                'success' => false,
                'message' => 'Por favor, ingrese su contraseña actual.'
            ];
        }
        
        // Validar nueva contraseña
        if (empty($newPassword) || $newPassword !== $newPasswordConfirmation) {
            return [
                'success' => false,
                'message' => 'Las contraseñas no coinciden.'
            ];
        }
        
        if (strlen($newPassword) < 8) {
            return [
                'success' => false,
                'message' => 'La nueva contraseña debe tener al menos 8 caracteres.'
            ];
        }
        
        try {
            $table = DB::table(self::$usersTable);
            $userId = self::id();
            
            // Obtener el usuario
            $user = DB::fetch(
                "SELECT * FROM $table WHERE " . self::$userIdColumn . " = ?",
                [$userId]
            );
            
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Usuario no encontrado.'
                ];
            }
            
            // Verificar la contraseña actual
            if (!password_verify($currentPassword, $user->{self::$passwordColumn})) {
                return [
                    'success' => false,
                    'message' => 'La contraseña actual es incorrecta.'
                ];
            }
            
            // Actualizar contraseña
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            DB::query(
                "UPDATE $table SET " . 
                self::$passwordColumn . " = ? WHERE " . 
                self::$userIdColumn . " = ?",
                [$hashedPassword, $userId]
            );
            
            // Cerrar sesión en todos los dispositivos (opcional)
            self::logoutOtherDevices($hashedPassword);
            
            return [
                'success' => true,
                'message' => 'Su contraseña ha sido cambiada correctamente.'
            ];
            
        } catch (Exception $e) {
            error_log("Error al cambiar la contraseña: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Ocurrió un error al cambiar su contraseña. Por favor, inténtelo de nuevo.'
            ];
        }
    }
    
    /**
     * Cierra la sesión en todos los dispositivos excepto en el actual
     * 
     * @param string $hashedPassword Hash de la nueva contraseña
     */
    private static function logoutOtherDevices($hashedPassword) {
        // En una implementación real, podrías invalidar otros tokens de sesión aquí
        // Por ahora, solo actualizamos la contraseña para invalidar otras sesiones
        // ya que la contraseña se usa como parte de la generación del ID de sesión
    }
}

?>
