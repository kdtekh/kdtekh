<?php
/**
 * Funciones de base de datos
 */

// Incluir configuración principal
require_once __DIR__ . '/../config.php';

// Si la función getDbConnection no está definida, la definimos
if (!function_exists('getDbConnection')) {
    /**
     * Obtiene una conexión a la base de datos
     * 
     * @return PDO Instancia de PDO
     */
    function getDbConnection() {
        static $pdo = null;
        
        if ($pdo === null) {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ];
                
                $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                
                // Configurar la zona horaria
                $pdo->exec("SET time_zone = '+02:00';");
                
            } catch (PDOException $e) {
                error_log('Error de conexión a la base de datos: ' . $e->getMessage());
                throw new Exception('Error al conectar con la base de datos');
            }
        }
        
        return $pdo;
    }
}

/**
 * Ejecuta una consulta SQL con parámetros
 * 
 * @param string $sql Consulta SQL
 * @param array $params Parámetros para la consulta
 * @return PDOStatement
 */
function dbQuery($sql, $params = []) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * Obtiene una fila de la base de datos
 * 
 * @param string $sql Consulta SQL
 * @param array $params Parámetros para la consulta
 * @return array|false
 */
function dbFetch($sql, $params = []) {
    $stmt = dbQuery($sql, $params);
    return $stmt->fetch();
}

/**
 * Obtiene múltiples filas de la base de datos
 * 
 * @param string $sql Consulta SQL
 * @param array $params Parámetros para la consulta
 * @return array
 */
function dbFetchAll($sql, $params = []) {
    $stmt = dbQuery($sql, $params);
    return $stmt->fetchAll();
}

/**
 * Obtiene un valor único de la base de datos
 * 
 * @param string $sql Consulta SQL
 * @param array $params Parámetros para la consulta
 * @return mixed
 */
function dbFetchColumn($sql, $params = []) {
    $stmt = dbQuery($sql, $params);
    return $stmt->fetchColumn();
}

/**
 * Ejecuta una consulta de inserción
 * 
 * @param string $table Nombre de la tabla
 * @param array $data Datos a insertar
 * @return int ID del último registro insertado
 */
function dbInsert($table, $data) {
    $columns = array_keys($data);
    $placeholders = array_map(fn($col) => ":$col", $columns);
    
    $sql = "INSERT INTO $table (" . implode(', ', $columns) . 
           ") VALUES (" . implode(', ', $placeholders) . ")";
    
    dbQuery($sql, $data);
    return getDbConnection()->lastInsertId();
}

/**
 * Ejecuta una consulta de actualización
 * 
 * @param string $table Nombre de la tabla
 * @param array $data Datos a actualizar
 * @param string $where Condición WHERE
 * @param array $whereParams Parámetros para la condición WHERE
 * @return int Número de filas afectadas
 */
function dbUpdate($table, $data, $where, $whereParams = []) {
    $set = [];
    foreach (array_keys($data) as $column) {
        $set[] = "$column = :$column";
    }
    
    $sql = "UPDATE $table SET " . implode(', ', $set) . " WHERE $where";
    $stmt = dbQuery($sql, array_merge($data, $whereParams));
    
    return $stmt->rowCount();
}

/**
 * Escapa un valor para usarlo en una consulta SQL
 * 
 * @param mixed $value Valor a escapar
 * @return string Valor escapado
 */
function dbEscape($value) {
    if ($value === null) {
        return 'NULL';
    }
    
    if (is_bool($value)) {
        return $value ? '1' : '0';
    }
    
    if (is_numeric($value) && !is_string($value)) {
        return (string)$value;
    }
    
    return getDbConnection()->quote($value);
}
