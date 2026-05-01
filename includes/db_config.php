<?php
/**
 * Configuración de la base de datos
 * 
 * Este archivo contiene las credenciales de conexión a la base de datos.
 * ¡Mantén este archivo seguro y no lo compartas!
 * 
 * @version 2.0
 * @author KD Tekh Team
 */

// Cargar configuración de Hostinger si existe
if (file_exists(__DIR__ . '/hosting-config.php')) {
    require_once __DIR__ . '/hosting-config.php';
}

// Detectar si estamos en producción (Hostinger)
$isProduction = (strpos($_SERVER['HTTP_HOST'], 'kdtekh.com') !== false);

// Configuración del sitio (puede ser sobrescrita por hosting-config.php)
if (!defined('SITE_URL')) {
    define('SITE_URL', 'https://kdtekh.com');
}

// Configuración de la base de datos (puede ser sobrescrita por hosting-config.php)
if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
}

// Configuración para producción (Hostinger)
if ($isProduction) {
    // Las credenciales de producción se cargan desde variables de entorno
    // o desde un archivo fuera del directorio público para mayor seguridad.
    // Configura estas variables en tu panel de Hostinger o en un archivo .env protegido.
    define('DB_USER', getenv('DB_USER') ?: '');
    define('DB_PASS', getenv('DB_PASS') ?: '');
    define('DB_NAME', getenv('DB_NAME') ?: 'u507128367_kdtekchin');
    
    // Configuración adicional para producción
    define('DB_CHARSET', 'utf8mb4');
    define('DB_COLLATE', 'utf8mb4_unicode_ci');
    
    // Configuración de zona horaria
    define('DEFAULT_TIMEZONE', 'Europe/Madrid');
    
    // Habilitar solo en producción
    if (!defined('MYSQL_CLIENT_FLAGS')) {
        // Configurar la zona horaria de PHP
        date_default_timezone_set(DEFAULT_TIMEZONE);
        
        define('MYSQL_CLIENT_FLAGS', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci, time_zone = '+02:00'",
            PDO::ATTR_PERSISTENT => false // Desactivar conexiones persistentes
        ]);
    }
} else {
    // Configuración local
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'kdtekchin');
    define('DB_CHARSET', 'utf8mb4');
    define('DB_COLLATE', 'utf8mb4_unicode_ci');
    
    // Configuración para desarrollo local
    if (!defined('MYSQL_CLIENT_FLAGS')) {
        define('MYSQL_CLIENT_FLAGS', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]);
    }
}

// Configuración de depuración (puede ser sobrescrita por hosting-config.php)
if (!defined('DEBUG_MODE')) define('DEBUG_MODE', true);

// Configuración de zona horaria (puede ser sobrescrita por hosting-config.php)
if (!defined('DEFAULT_TIMEZONE')) {
    date_default_timezone_set('Europe/Madrid');
} else {
    date_default_timezone_set(DEFAULT_TIMEZONE);
}

try {
    // Configurar la zona horaria de PHP
    date_default_timezone_set('Europe/Madrid');
    
    // Crear conexión PDO
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];
    
    $conn = new PDO($dsn, DB_USER, DB_PASS, $options);
    
    // Configurar la zona horaria de la conexión MySQL
    $conn->exec("SET time_zone = '+02:00'");  // Horario de verano de Madrid (UTC+2)
    
} catch (PDOException $e) {
    // Registrar el error
    error_log("Error de conexión a la base de datos: " . $e->getMessage());
    
    // Mostrar mensaje de error apropiado
    if (DEBUG_MODE) {
        // En modo desarrollo, mostrar información detallada
        die("<h1>Error de conexión a la base de datos</h1>" . 
            "<p><strong>Mensaje:</strong> " . $e->getMessage() . "</p>" .
            "<p><strong>Archivo:</strong> " . $e->getFile() . " (Línea: " . $e->getLine() . ")</p>" .
            "<p><strong>Código:</strong> " . $e->getCode() . "</p>" .
            "<p><strong>Trace:</strong><pre>" . $e->getTraceAsString() . "</pre></p>");
    } else {
        // En producción, mostrar un mensaje genérico
        die("<h1>Error de conexión</h1>" . 
            "<p>Lo sentimos, ha ocurrido un error al conectar con la base de datos. Por favor, inténtalo de nuevo más tarde.</p>");
    }
}

// Función para obtener la conexión a la base de datos
function getDBConnection() {
    global $conn;
    return $conn;
}

// Función para ejecutar consultas SQL seguras
function dbQuery($sql, $params = []) {
    global $conn;
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Error en la consulta SQL: " . $e->getMessage());
        throw $e;
    }
}
?>
