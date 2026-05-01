<?php
/**
 * Funciones de utilidad para el panel de administración
 */

/**
 * Verifica si el usuario actual está autenticado
 * 
 * @return bool Verdadero si el usuario está autenticado, falso en caso contrario
 */
function isAuthenticated() {
    return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
}

/**
 * Verifica si el usuario actual tiene un rol específico
 * 
 * @param string|array $roles Rol o roles a verificar
 * @return bool Verdadero si el usuario tiene el rol, falso en caso contrario
 */
function hasRole($roles) {
    if (!isAuthenticated()) {
        return false;
    }
    
    if (is_string($roles)) {
        $roles = [$roles];
    }
    
    return in_array($_SESSION['usuario_rol'], $roles);
}

/**
 * Verifica si el usuario actual es administrador
 * 
 * @return bool Verdadero si el usuario es administrador, falso en caso contrario
 */
function isAdmin() {
    return isAuthenticated() && $_SESSION['usuario_rol'] === 'admin';
}

/**
 * Redirige al usuario a una URL específica
 * 
 * @param string $url URL a la que redirigir
 * @param int $statusCode Código de estado HTTP (por defecto: 302)
 */
function redirect($url, $statusCode = 302) {
    header('Location: ' . $url, true, $statusCode);
    exit();
}

/**
 * Establece un mensaje flash que se mostrará en la siguiente solicitud
 * 
 * @param string $message Mensaje a mostrar
 * @param string $type Tipo de mensaje (success, error, warning, info)
 */
function setFlashMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = [
        'message' => $message,
        'type' => $type
    ];
}

/**
 * Obtiene la URL base del sitio
 * 
 * @param string $path Ruta a concatenar a la URL base
 * @return string URL completa
 */
function baseUrl($path = '') {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $scriptName = dirname(dirname($_SERVER['SCRIPT_NAME']));
    
    // Asegurarse de que el scriptName no sea la raíz
    $scriptName = $scriptName === '\\' ? '' : $scriptName;
    
    return rtrim($protocol . $host . $scriptName . '/' . ltrim($path, '/'), '/');
}

/**
 * Sanitiza una cadena para evitar inyección XSS
 * 
 * @param string $string Cadena a sanear
 * @return string Cadena saneada
 */
function sanitize($string) {
    return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Formatea una fecha en un formato legible
 * 
 * @param string $date Fecha en formato válido para strtotime
 * @param string $format Formato de salida (por defecto: d/m/Y H:i)
 * @return string Fecha formateada
 */
function formatDate($date, $format = 'd/m/Y H:i') {
    if (empty($date) || $date === '0000-00-00 00:00:00') {
        return 'Nunca';
    }
    
    $timestamp = is_numeric($date) ? $date : strtotime($date);
    return date($format, $timestamp);
}

/**
 * Recorta un texto a una longitud máxima de caracteres
 * 
 * @param string $text Texto a recortar
 * @param int $length Longitud máxima
 * @param string $sufijo Sufijo a agregar si se recorta el texto (por defecto: ...)
 * @return string Texto recortado
 */
function truncate($text, $length = 100, $sufijo = '...') {
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    
    return mb_substr($text, 0, $length) . $sufijo;
}

/**
 * Sube un archivo al servidor
 * 
 * @param array $file Array $_FILES del archivo a subir
 * @param string $targetDir Directorio de destino
 * @param array $allowedTypes Tipos MIME permitidos (por defecto: imágenes)
 * @param int $maxSize Tamaño máximo en bytes (por defecto: 5MB)
 * @return array Array con información del resultado de la subida
 */
function uploadFile($file, $targetDir, $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], $maxSize = 5242880) {
    $result = [
        'success' => false,
        'message' => '',
        'file_path' => '',
        'file_name' => ''
    ];
    
    // Verificar si no hay errores
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido por el servidor.',
            UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo permitido.',
            UPLOAD_ERR_PARTIAL => 'El archivo fue subido solo parcialmente.',
            UPLOAD_ERR_NO_FILE => 'No se seleccionó ningún archivo.',
            UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal.',
            UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo en el disco.',
            UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la subida del archivo.'
        ];
        
        $result['message'] = $uploadErrors[$file['error']] ?? 'Error desconocido al subir el archivo.';
        return $result;
    }
    
    // Verificar tipo MIME
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime, $allowedTypes)) {
        $result['message'] = 'Tipo de archivo no permitido.';
        return $result;
    }
    
    // Verificar tamaño
    if ($file['size'] > $maxSize) {
        $result['message'] = 'El archivo es demasiado grande. Tamaño máximo: ' . formatBytes($maxSize);
        return $result;
    }
    
    // Crear directorio si no existe
    if (!file_exists($targetDir)) {
        if (!mkdir($targetDir, 0755, true)) {
            $result['message'] = 'No se pudo crear el directorio de destino.';
            return $result;
        }
    }
    
    // Generar nombre único
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = uniqid() . '.' . strtolower($extension);
    $targetPath = rtrim($targetDir, '/') . '/' . $fileName;
    
    // Mover archivo
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        $result['success'] = true;
        $result['message'] = 'Archivo subido correctamente.';
        $result['file_path'] = $targetPath;
        $result['file_name'] = $fileName;
    } else {
        $result['message'] = 'Error al mover el archivo subido.';
    }
    
    return $result;
}

/**
 * Formatea bytes a un formato legible
 * 
 * @param int $bytes Número de bytes
 * @param int $precision Precisión decimal
 * @return string Cadena formateada
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Genera una contraseña segura
 * 
 * @param int $length Longitud de la contraseña (por defecto: 12)
 * @return string Contraseña generada
 */
function generatePassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+-=[]{}|;:,.<>?';
    $password = '';
    $charsLength = strlen($chars) - 1;
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, $charsLength)];
    }
    
    return $password;
}

/**
 * Valida una dirección de correo electrónico
 * 
 * @param string $email Correo electrónico a validar
 * @return bool Verdadero si el correo es válido, falso en caso contrario
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Obtiene la dirección IP del cliente
 * 
 * @return string Dirección IP
 */
function getClientIp() {
    $ip = '';
    
    if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    return $ip;
}

/**
 * Registra un mensaje en el archivo de registro
 * 
 * @param string $message Mensaje a registrar
 * @param string $level Nivel de registro (info, warning, error, etc.)
 * @param string $file Nombre del archivo de registro (sin extensión)
 * @return bool Verdadero si se escribió correctamente, falso en caso contrario
 */
function logMessage($message, $level = 'info', $file = 'application') {
    $logDir = __DIR__ . '/../logs';
    
    // Crear directorio de logs si no existe
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = rtrim($logDir, '/') . '/' . $file . '_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $message = "[$timestamp] [$level] $message" . PHP_EOL;
    
    return file_put_contents($logFile, $message, FILE_APPEND | LOCK_EX) !== false;
}

/**
 * Obtiene el valor de un array de forma segura
 * 
 * @param array $array Array del que obtener el valor
 * @param string $key Clave del valor a obtener
 * @param mixed $default Valor por defecto si la clave no existe
 * @return mixed Valor de la clave o valor por defecto
 */
function arrayGet($array, $key, $default = null) {
    if (!is_array($array)) {
        return $default;
    }
    
    return array_key_exists($key, $array) ? $array[$key] : $default;
}

/**
 * Obtiene el valor de $_POST de forma segura
 * 
 * @param string $key Clave del valor a obtener
 * @param mixed $default Valor por defecto si la clave no existe
 * @return mixed Valor de la clave o valor por defecto
 */
function post($key, $default = null) {
    return arrayGet($_POST, $key, $default);
}

/**
 * Obtiene el valor de $_GET de forma segura
 * 
 * @param string $key Clave del valor a obtener
 * @param mixed $default Valor por defecto si la clave no existe
 * @return mixed Valor de la clave o valor por defecto
 */
function get($key, $default = null) {
    return arrayGet($_GET, $key, $default);
}

/**
 * Obtiene el valor de $_SESSION de forma segura
 * 
 * @param string $key Clave del valor a obtener
 * @param mixed $default Valor por defecto si la clave no existe
 * @return mixed Valor de la clave o valor por defecto
 */
function session($key, $default = null) {
    return arrayGet($_SESSION, $key, $default);
}

/**
 * Obtiene el valor de $_COOKIE de forma segura
 * 
 * @param string $key Clave del valor a obtener
 * @param mixed $default Valor por defecto si la clave no existe
 * @return mixed Valor de la clave o valor por defecto
 */
function cookie($key, $default = null) {
    return arrayGet($_COOKIE, $key, $default);
}

/**
 * Obtiene el valor de $_SERVER de forma segura
 * 
 * @param string $key Clave del valor a obtener
 * @param mixed $default Valor por defecto si la clave no existe
 * @return mixed Valor de la clave o valor por defecto
 */
function server($key, $default = null) {
    return arrayGet($_SERVER, $key, $default);
}

/**
 * Obtiene el valor de $_FILES de forma segura
 * 
 * @param string $key Clave del valor a obtener
 * @param mixed $default Valor por defecto si la clave no existe
 * @return mixed Valor de la clave o valor por defecto
 */
function files($key, $default = null) {
    return arrayGet($_FILES, $key, $default);
}

/**
 * Obtiene el valor de $_REQUEST de forma segura
 * 
 * @param string $key Clave del valor a obtener
 * @param mixed $default Valor por defecto si la clave no existe
 * @return mixed Valor de la clave o valor por defecto
 */
function request($key, $default = null) {
    return arrayGet($_REQUEST, $key, $default);
}

/**
 * Obtiene el valor de un array anidado usando notación de puntos
 * 
 * @param array $array Array del que obtener el valor
 * @param string $key Clave en notación de puntos (ej: 'user.profile.name')
 * @param mixed $default Valor por defecto si la clave no existe
 * @return mixed Valor de la clave o valor por defecto
 */
function arrayGetNested($array, $key, $default = null) {
    if (!is_array($array)) {
        return $default;
    }
    
    if (array_key_exists($key, $array)) {
        return $array[$key];
    }
    
    if (strpos($key, '.') === false) {
        return $default;
    }
    
    foreach (explode('.', $key) as $segment) {
        if (!is_array($array) || !array_key_exists($segment, $array)) {
            return $default;
        }
        
        $array = $array[$segment];
    }
    
    return $array;
}

/**
 * Establece un valor en un array anidado usando notación de puntos
 * 
 * @param array $array Array en el que establecer el valor
 * @param string $key Clave en notación de puntos (ej: 'user.profile.name')
 * @param mixed $value Valor a establecer
 * @return array Array modificado
 */
function arraySetNested(&$array, $key, $value) {
    if (!is_array($array)) {
        $array = [];
    }
    
    $keys = explode('.', $key);
    
    while (count($keys) > 1) {
        $key = array_shift($keys);
        
        if (!isset($array[$key]) || !is_array($array[$key])) {
            $array[$key] = [];
        }
        
        $array = &$array[$key];
    }
    
    $array[array_shift($keys)] = $value;
    
    return $array;
}

/**
 * Verifica si una clave existe en un array anidado usando notación de puntos
 * 
 * @param array $array Array en el que buscar
 * @param string $key Clave en notación de puntos (ej: 'user.profile.name')
 * @return bool Verdadero si la clave existe, falso en caso contrario
 */
function arrayHasNested($array, $key) {
    if (!is_array($array)) {
        return false;
    }
    
    if (array_key_exists($key, $array)) {
        return true;
    }
    
    if (strpos($key, '.') === false) {
        return false;
    }
    
    foreach (explode('.', $key) as $segment) {
        if (!is_array($array) || !array_key_exists($segment, $array)) {
            return false;
        }
        
        $array = $array[$segment];
    }
    
    return true;
}

/**
 * Elimina una clave de un array anidado usando notación de puntos
 * 
 * @param array $array Array del que eliminar la clave
 * @param string $key Clave en notación de puntos (ej: 'user.profile.name')
 * @return bool Verdadero si la clave existía y fue eliminada, falso en caso contrario
 */
function arrayForgetNested(&$array, $key) {
    if (!is_array($array)) {
        return false;
    }
    
    $keys = explode('.', $key);
    $lastKey = array_pop($keys);
    $current = &$array;
    
    foreach ($keys as $key) {
        if (!isset($current[$key]) || !is_array($current[$key])) {
            return false;
        }
        
        $current = &$current[$key];
    }
    
    if (array_key_exists($lastKey, $current)) {
        unset($current[$lastKey]);
        return true;
    }
    
    return false;
}
?>
