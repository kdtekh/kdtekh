<?php
/**
 * Clase para manejar el registro de eventos en la aplicación
 */
class Logger {
    // Niveles de log
    const LEVEL_ERROR = 'ERROR';
    const LEVEL_WARNING = 'WARNING';
    const LEVEL_INFO = 'INFO';
    const LEVEL_DEBUG = 'DEBUG';
    
    // Ruta del archivo de log
    private static $logFile;
    
    // Nivel mínimo de log (por defecto: INFO)
    private static $minLevel = self::LEVEL_INFO;
    
    // Niveles de severidad
    private static $levels = [
        self::LEVEL_DEBUG => 0,
        self::LEVEL_INFO => 1,
        self::LEVEL_WARNING => 2,
        self::LEVEL_ERROR => 3
    ];
    
    /**
     * Inicializa el logger
     */
    public static function init($logFile = null) {
        if ($logFile === null) {
            $logDir = dirname(__DIR__) . '/logs';
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            $logFile = $logDir . '/app.log';
        }
        self::$logFile = $logFile;
    }
    
    /**
     * Establece el nivel mínimo de log
     */
    public static function setMinLevel($level) {
        if (isset(self::$levels[$level])) {
            self::$minLevel = $level;
        }
    }
    
    /**
     * Registra un mensaje de error
     */
    public static function error($message, array $context = []) {
        self::log(self::LEVEL_ERROR, $message, $context);
    }
    
    /**
     * Registra un mensaje de advertencia
     */
    public static function warning($message, array $context = []) {
        self::log(self::LEVEL_WARNING, $message, $context);
    }
    
    /**
     * Registra un mensaje informativo
     */
    public static function info($message, array $context = []) {
        self::log(self::LEVEL_INFO, $message, $context);
    }
    
    /**
     * Registra un mensaje de depuración
     */
    public static function debug($message, array $context = []) {
        self::log(self::LEVEL_DEBUG, $message, $context);
    }
    
    /**
     * Método principal de registro
     */
    private static function log($level, $message, array $context = []) {
        // Verificar si el nivel de log es suficiente
        if (self::$levels[$level] < self::$levels[self::$minLevel]) {
            return;
        }
        
        // Asegurarse de que el archivo de log esté inicializado
        if (self::$logFile === null) {
            self::init();
        }
        
        // Crear mensaje de log
        $timestamp = date('Y-m-d H:i:s');
        $ip = self::getClientIP();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? '';
        
        // Formato: [fecha] [nivel] [IP] [método URI] [user-agent] mensaje {contexto}
        $logMessage = sprintf(
            "[%s] [%s] [%s] [%s %s] %s",
            $timestamp,
            str_pad($level, 7), // Asegurar que el nivel tenga el mismo ancho
            str_pad($ip, 15),   // Asegurar que la IP tenga el mismo ancho
            str_pad($requestMethod, 7),
            $requestUri,
            $message
        );
        
        // Agregar contexto si existe
        if (!empty($context)) {
            $logMessage .= ' ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        
        // Agregar salto de línea
        $logMessage .= PHP_EOL;
        
        // Escribir en el archivo de log
        file_put_contents(self::$logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Obtiene la IP del cliente
     */
    private static function getClientIP() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        // Si hay un proxy, intentar obtener la IP real
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
        } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        
        // Validar que sea una IP válida
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'invalid-ip';
    }
}

// Inicializar el logger por defecto
Logger::init();
