<?php
// Configuración para el directorio de newsletter

// Habilitar visualización de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/newsletter_errors.log');

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
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'admin') {
    // Registrar intento de acceso no autorizado
    error_log("Intento de acceso no autorizado a la sección de newsletter. Usuario ID: " . 
             ($_SESSION['usuario_id'] ?? 'no definido') . 
             ", Rol: " . ($_SESSION['usuario_rol'] ?? 'no definido'));
    
    // Redirigir al inicio de sesión si no hay sesión activa
    if (!isset($_SESSION['usuario_id'])) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: /admin/login.php');
        exit();
    }
    
    // Mostrar error de permiso denegado
    header('HTTP/1.1 403 Forbidden');
    die("<h1>Acceso denegado</h1><p>No tienes permisos para acceder a esta sección. Por favor, inicia sesión como administrador.</p>");
}

// Configuración de paginación
$por_pagina = 20;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$inicio = ($pagina > 1) ? ($pagina * $por_pagina - $por_pagina) : 0;

// Búsqueda y filtros
$busqueda = $_GET['buscar'] ?? '';
$filtro_estado = $_GET['estado'] ?? 'todos';

// Construir consulta
$where = [];
$params = [];

if (!empty($busqueda)) {
    $where[] = "(email LIKE :busqueda OR nombre LIKE :busqueda2)";
    $params[':busqueda'] = "%$busqueda%";
    $params[':busqueda2'] = "%$busqueda%";
}

if ($filtro_estado !== 'todos') {
    $where[] = "activo = :activo";
    $params[':activo'] = ($filtro_estado === 'activos') ? 1 : 0;
}

$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Obtener total de suscriptores
$total = $pdo->prepare("SELECT COUNT(*) FROM newsletter $where_clause");
$total->execute($params);
$total_suscriptores = $total->fetchColumn();
$total_paginas = ceil($total_suscriptores / $por_pagina);

// Obtener suscriptores para la página actual
$query = "SELECT * FROM newsletter $where_clause ORDER BY fecha_registro DESC LIMIT :inicio, :por_pagina";
$stmt = $pdo->prepare($query);

// Vincular parámetros
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

// Vincular parámetros de paginación
$stmt->bindValue(':inicio', (int)$inicio, PDO::PARAM_INT);
$stmt->bindValue(':por_pagina', (int)$por_pagina, PDO::PARAM_INT);

$stmt->execute();
$suscriptores = $stmt->fetchAll(PDO::FETCH_ASSOC);
