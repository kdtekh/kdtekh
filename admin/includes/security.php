<?php
// Prevenir acceso directo al archivo
if (!defined('SITE_PATH')) {
    die('Acceso denegado');
}

// Configuración de seguridad
define('ALLOWED_IPS', [
    '127.0.0.1',    // Localhost
    '::1'           // IPv6 localhost
    // Agrega aquí otras IPs permitidas
]);


// Función para verificar la IP del usuario
function isIpAllowed($ip) {
    return in_array($ip, ALLOWED_IPS);
}

// Función para prevenir inyección SQL
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Función para verificar si el usuario está autenticado
function isAuthenticated() {
    return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
}

// Función para forzar autenticación
function requireAuth() {
    if (!isAuthenticated()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header('Location: login.php');
        exit();
    }
}

// Función para verificar permisos de administrador
function isAdmin() {
    return isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin';
}

// Función para forzar permisos de administrador
function requireAdmin() {
    requireAuth();
    if (!isAdmin()) {
        header('HTTP/1.0 403 Forbidden');
        die('Acceso denegado: No tienes permisos de administrador');
    }
}

// Función para generar tokens CSRF
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Función para verificar tokens CSRF
function verifyCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    return true;
}

// Función para registrar actividad
function logActivity($usuario_id, $accion, $detalles = '') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO logs (usuario_id, accion, detalles, ip, user_agent) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $usuario_id,
            $accion,
            $detalles,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        ]);
        return true;
    } catch (PDOException $e) {
        error_log("Error al registrar actividad: " . $e->getMessage());
        return false;
    }
}

// Inicializar la sesión con parámetros seguros
function initSecureSession() {
    // Configurar parámetros de la cookie de sesión
    $session_name = 'secure_session';
    $secure = true; // Solo enviar cookies a través de HTTPS
    $httponly = true; // Evitar acceso a la cookie mediante JavaScript
    
    // Obligar a la sesión a solo usar cookies
    if (ini_set('session.use_only_cookies', 1) === false) {
        die("Error: No se pudo iniciar una sesión segura");
    }
    
    // Obtener parámetros de la cookie actual
    $cookieParams = session_get_cookie_params();
    
    // Configurar los parámetros de la cookie
    session_set_cookie_params([
        'lifetime' => $cookieParams["lifetime"],
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => $secure,
        'httponly' => $httponly,
        'samesite' => 'Strict'
    ]);
    
    // Configurar el nombre de la sesión
    session_name($session_name);
    
    // Iniciar la sesión
    session_start();
    
    // Regenerar el ID de sesión periódicamente para prevenir fijación de sesión
    if (!isset($_SESSION['last_regeneration'])) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    } else if (time() - $_SESSION['last_regeneration'] > 1800) { // 30 minutos
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

// Función para validar y limpiar datos de entrada
function sanitizeInput($data, $type = 'string') {
    switch ($type) {
        case 'email':
            $data = filter_var(trim($data), FILTER_SANITIZE_EMAIL);
            return filter_var($data, FILTER_VALIDATE_EMAIL) ? $data : false;
        case 'int':
            return filter_var($data, FILTER_VALIDATE_INT);
        case 'float':
            return filter_var($data, FILTER_VALIDATE_FLOAT);
        case 'url':
            return filter_var($data, FILTER_VALIDATE_URL);
        case 'string':
        default:
            $data = trim($data);
            $data = stripslashes($data);
            $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
            return $data;
    }
}

// Función para generar contraseñas seguras
function generateSecurePassword($length = 12) {
    $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $lowercase = 'abcdefghijklmnopqrstuvwxyz';
    $numbers = '0123456789';
    $special = '!@#$%^&*()_+-=[]{}|;:,.<>?';
    
    $all = $uppercase . $lowercase . $numbers . $special;
    $password = '';
    
    // Asegurar al menos un carácter de cada tipo
    $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
    $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
    $password .= $numbers[random_int(0, strlen($numbers) - 1)];
    $password .= $special[random_int(0, strlen($special) - 1)];
    
    // Completar la longitud restante
    for ($i = strlen($password); $i < $length; $i++) {
        $password .= $all[random_int(0, strlen($all) - 1)];
    }
    
    // Mezclar los caracteres
    return str_shuffle($password);
}

// Función para verificar la fortaleza de una contraseña
function isPasswordStrong($password) {
    // Mínimo 8 caracteres, al menos una letra mayúscula, una minúscula, un número y un carácter especial
    $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).{8,}$/';
    return preg_match($pattern, $password);
}

// Función para prevenir ataques de fuerza bruta
function checkBruteForce($usuario_id, $pdo) {
    // Intentos permitidos en los últimos 15 minutos
    $tiempo_limite = time() - (15 * 60);
    
    $stmt = $pdo->prepare("SELECT tiempo FROM intentos_login 
                          WHERE usuario_id = ? AND tiempo > ?");
    $stmt->execute([$usuario_id, $tiempo_limite]);
    
    // Si hay más de 5 intentos fallidos en 15 minutos
    if ($stmt->rowCount() > 5) {
        return true; // Demasiados intentos
    }
    
    return false;
}

// Función para registrar intentos fallidos
function recordFailedAttempt($usuario_id, $pdo) {
    $stmt = $pdo->prepare("INSERT INTO intentos_login (usuario_id, tiempo, ip) 
                          VALUES (?, ?, ?)");
    $stmt->execute([$usuario_id, time(), $_SERVER['REMOTE_ADDR']]);
}

// Función para limpiar intentos antiguos
function cleanOldAttempts($pdo) {
    $tiempo_limite = time() - (15 * 60);
    $pdo->prepare("DELETE FROM intentos_login WHERE tiempo < ?")->execute([$tiempo_limite]);
}

// Inicializar la sesión segura al incluir este archivo
initSecureSession();
