<?php
// Habilitar visualización de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/newsletter_export_errors.log');

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticación y permisos
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    die("<h1>Acceso denegado</h1><p>No tienes permisos para acceder a esta sección.</p>");
}

// Incluir configuración
require_once __DIR__ . '/_config.php';

try {
    // Verificar si hay una solicitud POST (protección CSRF)
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Esta acción requiere una solicitud POST.');
    }

    // Configurar cabeceras para la descarga del archivo CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=suscriptores_' . date('Y-m-d') . '.csv');

    // Configurar consulta con filtros
    $where = [];
    $params = [];

    // Aplicar filtros de búsqueda
    if (isset($_POST['busqueda']) && !empty(trim($_POST['busqueda']))) {
        $busqueda = trim($_POST['busqueda']);
        $where[] = "(email LIKE :busqueda OR nombre LIKE :busqueda_nombre OR apellido LIKE :busqueda_apellido)";
        $params[':busqueda'] = "%$busqueda%";
        $params[':busqueda_nombre'] = "%$busqueda%";
        $params[':busqueda_apellido'] = "%$busqueda%";
    }
    
    // Primero, obtener la estructura de la tabla para ver qué columnas existen
    $stmt = $pdo->query("SHOW COLUMNS FROM newsletter");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Mapear los nombres de las columnas a sus alias en español
    $column_map = [
        'id' => 'ID',
        'email' => 'Email',
        'nombre' => 'Nombre',
        'apellido' => 'Apellido',
        'fecha_registro' => 'Fecha de registro',
        'confirmado' => 'Verificado',
        'activo' => 'Activo',
        'token_confirmacion' => 'Token de verificación',
        'ip_registro' => 'IP de registro',
        'fecha_confirmacion' => 'Fecha de confirmación',
        'ip_confirmacion' => 'IP de confirmación',
        'fecha_baja' => 'Fecha de baja',
        'ip_baja' => 'IP de baja',
        'motivo_baja' => 'Motivo de baja',
        'notas' => 'Notas',
        'created_at' => 'Creado el',
        'updated_at' => 'Actualizado el',
        'ultima_actualizacion' => 'Última actualización'
    ];
    
    // Filtrar solo las columnas que existen en la tabla
    $selected_columns = array_intersect_key($column_map, array_flip($columns));
    
    if (empty($selected_columns)) {
        throw new Exception('No se encontraron columnas válidas en la tabla de newsletter.');
    }
    
    // Construir la consulta SQL dinámicamente
    $sql_columns = implode(', ', array_keys($selected_columns));
    $sql = "SELECT $sql_columns FROM newsletter";
    
    // Crear un puntero al flujo de salida
    $output = fopen('php://output', 'w');
    
    // Escribir la fila de encabezados usando los nombres de las columnas mapeados
    fputcsv($output, array_values($selected_columns));



    // Añadir condiciones WHERE si existen
    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    // Ordenar por fecha de registro descendente
    $sql .= " ORDER BY fecha_registro DESC";

    // Preparar y ejecutar la consulta
    $stmt = $pdo->prepare($sql);
    
    // Vincular parámetros
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    
    // Escribir los datos de los suscriptores
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Preparar los datos para CSV
        $csv_row = [];
        foreach (array_keys($selected_columns) as $column) {
            $value = $row[$column] ?? '';
            
            // Formatear valores especiales
            if (in_array($column, ['confirmado', 'activo'])) {
                $value = $value ? 'Sí' : 'No';
            } elseif (strpos($column, 'fecha_') === 0 && !empty($value)) {
                // Formatear fechas si es necesario
                $value = date('Y-m-d H:i:s', strtotime($value));
            }
            
            $csv_row[] = $value;
        }
        
        fputcsv($output, $csv_row);
    }
    
    // Cerrar el puntero al archivo
    fclose($output);
    exit;
    
} catch (Exception $e) {
    // Registrar el error
    error_log("Error al exportar suscriptores: " . $e->getMessage());
    
    // Mostrar mensaje de error
    header('Content-Type: text/html; charset=utf-8');
    die("<h1>Error al generar la exportación</h1><p>" . htmlspecialchars($e->getMessage()) . "</p>");
}
?>
