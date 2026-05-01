<?php
/**
 * Utilidades para la protección CSRF
 */

// Asegurarse de que la sesión esté iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Genera un token CSRF si no existe o ha expirado
 * @param string $formName Nombre único del formulario
 * @param int $expireSeconds Tiempo de expiración en segundos (por defecto 1 hora)
 * @return string Token CSRF
 */
function generateCSRFToken($formName, $expireSeconds = 3600) {
    // Inicializar el array de tokens si no existe
    if (!isset($_SESSION['csrf_tokens'])) {
        $_SESSION['csrf_tokens'] = [];
    }
    
    // Generar un nuevo token si no existe o ha expirado
    if (!isset($_SESSION['csrf_tokens'][$formName]) || 
        (isset($_SESSION['csrf_tokens'][$formName]['expire']) && 
         $_SESSION['csrf_tokens'][$formName]['expire'] < time())) {
        $_SESSION['csrf_tokens'][$formName] = [
            'token' => bin2hex(random_bytes(32)),
            'expire' => time() + $expireSeconds
        ];
    }
    
    return $_SESSION['csrf_tokens'][$formName]['token'];
}

/**
 * Valida un token CSRF
 * @param string $formName Nombre del formulario
 * @param string $token Token a validar
 * @param bool $removeAfterValidation Si se debe eliminar el token después de validarlo (por defecto true)
 * @return bool True si el token es válido, false en caso contrario
 */
function validateCSRFToken($formName, $token, $removeAfterValidation = true) {
    // Si no hay tokens en la sesión
    if (!isset($_SESSION['csrf_tokens'][$formName])) {
        error_log("CSRF Error: No token found for form $formName");
        return false;
    }
    
    $storedToken = $_SESSION['csrf_tokens'][$formName];
    
    // Verificar si el token ha expirado
    if (isset($storedToken['expire']) && $storedToken['expire'] < time()) {
        error_log("CSRF Error: Token expired for form $formName");
        unset($_SESSION['csrf_tokens'][$formName]);
        return false;
    }
    
    // Verificar si el token coincide
    $isValid = hash_equals($storedToken['token'], $token);
    
    if (!$isValid) {
        error_log("CSRF Error: Invalid token for form $formName");
    } elseif ($removeAfterValidation) {
        // Eliminar el token después de usarlo (one-time token)
        unset($_SESSION['csrf_tokens'][$formName]);
    }
    
    return $isValid;
}

/**
 * Obtiene el campo de entrada CSRF como HTML
 * @param string $formName Nombre del formulario
 * @return string HTML del campo de entrada oculto
 */
function csrfInput($formName) {
    $token = generateCSRFToken($formName);
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Obtiene el token CSRF para usar con fetch/axios
 * @param string $formName Nombre del formulario
 * @return string Token CSRF
 */
function getCSRFToken($formName) {
    return generateCSRFToken($formName);
}

/**
 * Verifica el token CSRF desde una petición
 * @param string $formName Nombre del formulario
 * @return bool True si el token es válido
 */
function verifyCSRFFromRequest($formName) {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    
    if (!$token) {
        error_log('CSRF Error: No token provided in request');
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Token CSRF no proporcionado']);
        exit;
    }
    
    if (!validateCSRFToken($formName, $token)) {
        error_log('CSRF Error: Invalid token in request');
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Token CSRF inválido o expirado']);
        exit;
    }
    
    return true;
}
