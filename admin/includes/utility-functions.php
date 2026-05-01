<?php
/**
 * Funciones de utilidad general
 */

// Incluir archivos necesarios
require_once __DIR__ . '/config.php';

/**
 * Establece un mensaje flash en la sesión
 * 
 * @param string $message Mensaje a mostrar
 * @param string $type Tipo de mensaje (success, error, warning, info)
 * @return void
 */
function setFlashMessage($message, $type = 'info') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION['flash_messages'][] = [
        'message' => $message,
        'type' => $type
    ];
}

/**
 * Obtiene y limpia los mensajes flash de la sesión
 * 
 * @return array Array de mensajes flash
 */
function getFlashMessages() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    
    return $messages;
}

/**
 * Muestra los mensajes flash en la interfaz
 * 
 * @return void
 */
function displayFlashMessages() {
    $messages = getFlashMessages();
    
    if (empty($messages)) {
        return;
    }
    
    echo '<div class="flash-messages">';
    
    foreach ($messages as $message) {
        $type = htmlspecialchars($message['type'], ENT_QUOTES, 'UTF-8');
        $text = htmlspecialchars($message['message'], ENT_QUOTES, 'UTF-8');
        
        echo '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">';
        echo $text;
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>';
        echo '</div>';
    }
    
    echo '</div>';
}

/**
 * Redirige a una URL
 * 
 * @param string $url URL a la que redirigir
 * @param int $statusCode Código de estado HTTP (por defecto: 302)
 * @return void
 */
function redirect($url, $statusCode = 302) {
    header('Location: ' . $url, true, $statusCode);
    exit();
}

/**
 * Verifica si la petición es AJAX
 * 
 * @return bool Verdadero si es una petición AJAX, falso en caso contrario
 */
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Devuelve una respuesta JSON
 * 
 * @param mixed $data Datos a devolver
 * @param int $statusCode Código de estado HTTP (por defecto: 200)
 * @return void
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

/**
 * Genera un token CSRF
 * 
 * @return string Token CSRF
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Verifica un token CSRF
 * 
 * @param string $token Token a verificar
 * @return bool Verdadero si el token es válido, falso en caso contrario
 */
function verifyCsrfToken($token) {
    if (empty($_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
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
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    
    return $password;
}

/**
 * Valida una dirección de correo electrónico
 * 
 * @param string $email Dirección de correo electrónico a validar
 * @return bool Verdadero si el correo es válido, falso en caso contrario
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Sanitiza una cadena de texto
 * 
 * @param string $input Cadena a sanitizar
 * @param bool $stripTags Si es verdadero, elimina etiquetas HTML y PHP
 * @return string Cadena sanitizada
 */
function sanitizeInput($input, $stripTags = true) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    
    if ($stripTags) {
        $input = strip_tags($input);
    }
    
    return htmlspecialchars(trim($input), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Formatea una fecha en un formato legible
 * 
 * @param string $date Fecha en formato válido para strtotime
 * @param string $format Formato de salida (por defecto: 'd/m/Y H:i')
 * @return string Fecha formateada
 */
function formatDate($date, $format = 'd/m/Y H:i') {
    $timestamp = is_numeric($date) ? (int)$date : strtotime($date);
    
    if ($timestamp === false) {
        return '';
    }
    
    return date($format, $timestamp);
}

/**
 * Calcula la diferencia de tiempo entre dos fechas
 * 
 * @param string $start Fecha de inicio
 * @param string $end Fecha de fin (por defecto: ahora)
 * @return array Array con los componentes de tiempo
 */
function dateDiff($start, $end = null) {
    $start = is_numeric($start) ? (int)$start : strtotime($start);
    $end = $end ? (is_numeric($end) ? (int)$end : strtotime($end)) : time();
    
    if ($start === false || $end === false) {
        return [];
    }
    
    $diff = $end - $start;
    
    $units = [
        'year' => 31536000,  // 365 días
        'month' => 2592000,   // 30 días
        'week' => 604800,     // 7 días
        'day' => 86400,       // 24 horas
        'hour' => 3600,       // 60 minutos
        'minute' => 60,       // 60 segundos
        'second' => 1         // 1 segundo
    ];
    
    $result = [];
    
    foreach ($units as $unit => $seconds) {
        $value = floor($diff / $seconds);
        
        if ($value > 0) {
            $result[$unit] = $value;
            $diff -= $value * $seconds;
        }
    }
    
    return $result;
}

/**
 * Formatea una diferencia de tiempo en un formato legible
 * 
 * @param string $start Fecha de inicio
 * @param string $end Fecha de fin (por defecto: ahora)
 * @param int $parts Número de partes a mostrar (por defecto: 1)
 * @return string Diferencia formateada
 */
function timeAgo($start, $end = null, $parts = 1) {
    $diff = dateDiff($start, $end);
    
    if (empty($diff)) {
        return 'justo ahora';
    }
    
    $units = [
        'año' => 'años',
        'mes' => 'meses',
        'semana' => 'semanas',
        'día' => 'días',
        'hora' => 'horas',
        'minuto' => 'minutos',
        'segundo' => 'segundos'
    ];
    
    $result = [];
    $count = 0;
    
    foreach ($units as $unit => $plural) {
        if (isset($diff[$unit])) {
            $value = $diff[$unit];
            $unitText = $value === 1 ? $unit : $plural;
            $result[] = "$value $unitText";
            $count++;
            
            if ($count >= $parts) {
                break;
            }
        }
    }
    
    return implode(', ', $result) . ' atrás';
}

/**
 * Recorta un texto a un número máximo de caracteres
 * 
 * @param string $text Texto a recortar
 * @param int $length Longitud máxima
 * @param string $sufijo Sufijo a añadir si se recorta el texto (por defecto: '...')
 * @param bool $words Si es verdadero, recorta por palabras completas
 * @return string Texto recortado
 */
function truncate($text, $length = 100, $suffix = '...', $words = true) {
    $text = trim($text);
    $textLength = mb_strlen($text, 'UTF-8');
    
    if ($textLength <= $length) {
        return $text;
    }
    
    if ($words) {
        $text = mb_substr($text, 0, $length + 1, 'UTF-8');
        $spacePos = mb_strrpos($text, ' ', 0, 'UTF-8');
        
        if ($spacePos !== false) {
            return mb_substr($text, 0, $spacePos, 'UTF-8') . $suffix;
        }
    }
    
    return mb_substr($text, 0, $length, 'UTF-8') . $suffix;
}

/**
 * Genera una URL amigable a partir de un texto
 * 
 * @param string $text Texto a convertir en URL
 * @return string URL amigable
 */
function slugify($text) {
    // Reemplazar caracteres especiales
    $text = str_replace(
        ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ'],
        ['a', 'e', 'i', 'o', 'u', 'n', 'a', 'e', 'i', 'o', 'u', 'n'],
        $text
    );
    
    // Convertir a minúsculas
    $text = mb_strtolower($text, 'UTF-8');
    
    // Reemplazar caracteres no alfanuméricos por guiones
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    
    // Eliminar guiones al principio y al final
    $text = trim($text, '-');
    
    return $text;
}

/**
 * Verifica si una cadena comienza con un prefijo específico
 * 
 * @param string $haystack Cadena a verificar
 * @param string $needle Prefijo a buscar
 * @return bool Verdadero si la cadena comienza con el prefijo, falso en caso contrario
 */
function startsWith($haystack, $needle) {
    return substr_compare($haystack, $needle, 0, strlen($needle)) === 0;
}

/**
 * Verifica si una cadena termina con un sufijo específico
 * 
 * @param string $haystack Cadena a verificar
 * @param string $needle Sufijo a buscar
 * @return bool Verdadero si la cadena termina con el sufijo, falso en caso contrario
 */
function endsWith($haystack, $needle) {
    return $needle === '' || substr_compare($haystack, $needle, -strlen($needle)) === 0;
}

/**
 * Obtiene la URL base de la aplicación
 * 
 * @return string URL base
 */
function baseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $script = dirname($_SERVER['SCRIPT_NAME']);
    
    return rtrim($protocol . $host . $script, '/');
}

/**
 * Obtiene la URL actual
 * 
 * @param bool $full Si es verdadero, devuelve la URL completa con los parámetros
 * @return string URL actual
 */
function currentUrl($full = false) {
    if ($full) {
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }
    
    return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
}

/**
 * Obtiene la dirección IP del cliente
 * 
 * @return string Dirección IP
 */
function getClientIp() {
    $ip = '';
    
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    }
    
    // En caso de múltiples IPs (proxies), tomar la primera
    if (strpos($ip, ',') !== false) {
        $ip = trim(explode(',', $ip)[0]);
    }
    
    return $ip;
}

/**
 * Obtiene el agente de usuario del navegador
 * 
 * @return string Agente de usuario
 */
function getUserAgent() {
    return $_SERVER['HTTP_USER_AGENT'] ?? '';
}

/**
 * Verifica si el navegador es móvil
 * 
 * @return bool Verdadero si es un dispositivo móvil, falso en caso contrario
 */
function isMobile() {
    return preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $_SERVER['HTTP_USER_AGENT'] ?? '') || 
           preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\- mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\/|\-)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|vs|xi)|yo(ve|zz)|zeto|zte\-/i', substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 4));
}

/**
 * Verifica si el navegador es un robot
 * 
 * @return bool Verdadero si es un robot, falso en caso contrario
 */
function isBot() {
    return isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/bot|crawl|slurp|spider|mediapartners/i', $_SERVER['HTTP_USER_AGENT']);
}

/**
 * Obtiene el idioma preferido del navegador
 * 
 * @return string Código de idioma (ej: 'es', 'en', 'fr', etc.)
 */
function getBrowserLanguage() {
    $lang = 'es'; // Idioma por defecto
    
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $languages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
        
        if (!empty($languages[0])) {
            $lang = substr(trim($languages[0]), 0, 2);
        }
    }
    
    return $lang;
}

/**
 * Genera un hash seguro para contraseñas
 * 
 * @param string $password Contraseña a hashear
 * @return string Hash de la contraseña
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verifica si una contraseña coincide con un hash
 * 
 * @param string $password Contraseña a verificar
 * @param string $hash Hash contra el que verificar
 * @return bool Verdadero si coinciden, falso en caso contrario
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Genera un token único
 * 
 * @param int $length Longitud del token (por defecto: 32)
 * @return string Token generado
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Limpia el búfer de salida
 * 
 * @return void
 */
function cleanOutputBuffers() {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
}

/**
 * Establece las cabeceras HTTP para forzar la descarga de un archivo
 * 
 * @param string $filename Nombre del archivo
 * @param string $content Contenido del archivo
 * @param string $contentType Tipo MIME del archivo
 * @return void
 */
function forceDownload($filename, $content, $contentType = 'application/octet-stream') {
    cleanOutputBuffers();
    
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $contentType);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . strlen($content));
    
    echo $content;
    exit();
}

/**
 * Establece las cabeceras HTTP para forzar la descarga de un archivo desde una ruta
 * 
 * @param string $filepath Ruta al archivo
 * @param string $filename Nombre del archivo para la descarga (opcional)
 * @return void
 */
function forceDownloadFile($filepath, $filename = null) {
    if (!file_exists($filepath) || !is_readable($filepath)) {
        http_response_code(404);
        return;
    }
    
    if ($filename === null) {
        $filename = basename($filepath);
    }
    
    $mimeType = mime_content_type($filepath);
    
    if ($mimeType === false) {
        $mimeType = 'application/octet-stream';
    }
    
    cleanOutputBuffers();
    
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . filesize($filepath));
    
    readfile($filepath);
    exit();
}

/**
 * Comprime una cadena usando gzip
 * 
 * @param string $data Datos a comprimir
 * @param int $level Nivel de compresión (0-9)
 * @return string|false Datos comprimidos o false en caso de error
 */
function gzCompress($data, $level = 9) {
    if (!function_exists('gzencode')) {
        return false;
    }
    
    return gzencode($data, $level);
}

/**
 * Descomprime una cadena comprimida con gzip
 * 
 * @param string $data Datos comprimidos
 * @return string|false Datos descomprimidos o false en caso de error
 */
function gzUncompress($data) {
    if (!function_exists('gzdecode')) {
        return false;
    }
    
    return gzdecode($data);
}

/**
 * Envía un correo electrónico
 * 
 * @param string $to Dirección de correo del destinatario
 * @param string $subject Asunto del correo
 * @param string $message Cuerpo del mensaje (puede ser HTML)
 * @param string $from Dirección de correo del remitente
 * @param string $fromName Nombre del remitente
 * @param array $attachments Array de archivos adjuntos (opcional)
 * @return bool Verdadero si el correo se envió correctamente, falso en caso contrario
 */
function sendEmail($to, $subject, $message, $from = null, $fromName = null, $attachments = []) {
    if ($from === null) {
        $from = SITE_EMAIL;
    }
    
    if ($fromName === null) {
        $fromName = SITE_NAME;
    }
    
    $eol = PHP_EOL;
    $boundary = md5(uniqid(time()));
    $headers = [];
    
    // Cabeceras básicas
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'From: ' . $fromName . ' <' . $from . '>';
    $headers[] = 'Reply-To: ' . $from;
    $headers[] = 'X-Mailer: PHP/' . phpversion();
    $headers[] = 'X-Priority: 3';
    $headers[] = 'X-MSMail-Priority: Normal';
    $headers[] = 'X-Auto-Response-Suppress: OOF, AutoReply';
    
    // Si hay archivos adjuntos, usar formato multipart/mixed
    if (!empty($attachments)) {
        $headers[] = 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';
        
        $body = '--' . $boundary . $eol;
        $body .= 'Content-Type: text/html; charset=UTF-8' . $eol;
        $body .= 'Content-Transfer-Encoding: base64' . $eol . $eol;
        $body .= chunk_split(base64_encode($message)) . $eol;
        
        // Adjuntar archivos
        foreach ($attachments as $attachment) {
            if (file_exists($attachment)) {
                $filename = basename($attachment);
                $fileContent = file_get_contents($attachment);
                $fileContent = chunk_split(base64_encode($fileContent));
                
                $body .= '--' . $boundary . $eol;
                $body .= 'Content-Type: application/octet-stream; name="' . $filename . '"' . $eol;
                $body .= 'Content-Transfer-Encoding: base64' . $eol;
                $body .= 'Content-Disposition: attachment; filename="' . $filename . '"' . $eol . $eol;
                $body .= $fileContent . $eol;
            }
        }
        
        $body .= '--' . $boundary . '--' . $eol;
    } else {
        // Sin archivos adjuntos, enviar como HTML simple
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $body = $message;
    }
    
    // Enviar el correo
    return mail($to, $subject, $body, implode($eol, $headers));
}

/**
 * Registra un mensaje en el archivo de registro
 * 
 * @param string $message Mensaje a registrar
 * @param string $level Nivel de registro (info, warning, error, debug)
 * @param string $file Nombre del archivo de registro (opcional)
 * @return bool Verdadero si se registró correctamente, falso en caso contrario
 */
function logMessage($message, $level = 'info', $file = null) {
    if ($file === null) {
        $file = 'application.log';
    }
    
    $logDir = ADMIN_PATH . '/logs';
    $logFile = $logDir . '/' . $file;
    
    // Crear directorio de logs si no existe
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    // Formato: [fecha] [nivel] mensaje
    $logMessage = '[' . date('Y-m-d H:i:s') . '] [' . strtoupper($level) . '] ' . $message . PHP_EOL;
    
    // Escribir en el archivo de registro
    return file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX) !== false;
}

/**
 * Obtiene el valor de una variable de entorno
 * 
 * @param string $key Clave de la variable de entorno
 * @param mixed $default Valor por defecto si la variable no existe
 * @return mixed Valor de la variable de entorno o valor por defecto
 */
function env($key, $default = null) {
    static $env = null;
    
    if ($env === null) {
        $envFile = dirname(__DIR__) . '/.env';
        
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }
                
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value, " \t\n\r\0\x0B\"'");
                
                if ($name !== '') {
                    $env[$name] = $value;
                }
            }
        }
    }
    
    return $env[$key] ?? $default;
}
