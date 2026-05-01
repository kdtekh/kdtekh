<?php
// Configuración para el directorio de mensajes

// Habilitar visualización de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/mensajes_errors.log');

// Incluir archivos necesarios
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Obtener conexión a la base de datos
try {
    $pdo = getDbConnection();
} catch (PDOException $e) {
    error_log("Error de conexión a la base de datos: " . $e->getMessage());
    die("<h1>Error de conexión</h1><p>No se pudo conectar a la base de datos. Por favor, inténtalo de nuevo más tarde.</p>");
}

// Verificar si el usuario tiene permisos
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    die("<h1>Acceso denegado</h1><p>No tienes permisos para acceder a esta sección.</p>");
}

// Configuración de paginación
$por_pagina = 15;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$inicio = ($pagina > 1) ? ($pagina - 1) * $por_pagina : 0;

// Configuración de búsqueda y filtros
$busqueda = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : 'todos';

// Construir la consulta
$where_clause = '';
$params = [];

if (!empty($busqueda)) {
    $where_clause = "WHERE (nombre LIKE :buscar OR email LIKE :buscar OR asunto LIKE :buscar OR mensaje LIKE :buscar)";
    $params[':buscar'] = "%$busqueda%";
}

if ($filtro_estado !== 'todos') {
    $where_clause .= empty($where_clause) ? 'WHERE ' : ' AND ';
    $where_clause .= "leido = :leido";
    $params[':leido'] = ($filtro_estado === 'leidos') ? 1 : 0;
}

// Obtener el total de mensajes
try {
    $pdo = getDBConnection();
    
    $total = $pdo->prepare("SELECT COUNT(*) FROM mensajes_contacto $where_clause");
    $total->execute($params);
    $total_mensajes = $total->fetchColumn();
    $total_paginas = ceil($total_mensajes / $por_pagina);
    
    // Obtener mensajes
    $query = "SELECT * FROM mensajes_contacto $where_clause ORDER BY fecha DESC LIMIT $inicio, $por_pagina";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $mensajes = $stmt->fetchAll();
    
} catch (PDOException $e) {
    die("<h1>Error de base de datos</h1><p>" . htmlspecialchars($e->getMessage()) . "</p>");
}
?>
