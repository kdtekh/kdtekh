<?php
// Incluir configuración de la base de datos
require_once __DIR__ . '/../config.php';

// Configuración de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/db_update.log');

// Verificar si el script se está ejecutando desde la línea de comandos
$isCli = php_sapi_name() === 'cli';

// Función para mostrar mensajes
function showMessage($message, $isError = false) {
    global $isCli;
    if ($isCli) {
        echo ($isError ? 'ERROR: ' : '') . $message . PHP_EOL;
    } else {
        echo '<p style="color: ' . ($isError ? 'red' : 'green') . '; font-family: monospace; font-size: 14px; margin: 5px 0; padding: 5px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px;">' . 
             htmlspecialchars($message) . "</p>\n";
    }
}

// Mostrar encabezado HTML si no es CLI
if (!$isCli) {
    echo '<!DOCTYPE html><html><head><title>Actualización de Base de Datos</title>';
    echo '<style>body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }';
    echo 'h1 { color: #333; }';
    echo '.success { color: #28a745; }';
    echo '.error { color: #dc3545; }';
    echo '.sql { background: #f8f9fa; padding: 10px; border-radius: 4px; font-family: monospace; white-space: pre-wrap; }';
    echo '</style></head><body>';
    echo '<h1>Actualización de Base de Datos</h1>';
}

try {
    // Leer el archivo SQL
    $sqlFile = __DIR__ . '/update_newsletter_table.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("No se encontró el archivo de actualización: " . basename($sqlFile));
    }
    
    $sql = file_get_contents($sqlFile);
    
    if (empty($sql)) {
        throw new Exception("El archivo SQL está vacío");
    }
    
    // Mostrar el SQL que se va a ejecutar
    showMessage("Ejecutando las siguientes consultas SQL:");
    echo '<div class="sql">' . nl2br(htmlspecialchars($sql)) . '</div>';
    
    // Obtener conexión a la base de datos
    $pdo = getDbConnection();
    
    // Ejecutar las consultas SQL
    $queries = array_filter(array_map('trim', explode(';', $sql)));
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($queries as $query) {
        if (empty($query)) continue;
        
        try {
            $stmt = $pdo->prepare($query);
            $result = $stmt->execute();
            $affectedRows = $stmt->rowCount();
            
            showMessage("✓ Consulta ejecutada correctamente. Filas afectadas: " . $affectedRows);
            $successCount++;
            
        } catch (PDOException $e) {
            showMessage("✗ Error al ejecutar consulta: " . $e->getMessage(), true);
            $errorCount++;
        }
    }
    
    // Mostrar resumen
    showMessage("\nResumen de la operación:");
    showMessage("- Consultas ejecutadas con éxito: {$successCount}");
    if ($errorCount > 0) {
        showMessage("- Consultas con errores: {$errorCount}", true);
    }
    
} catch (Exception $e) {
    showMessage("Error: " . $e->getMessage(), true);
}

// Cerrar HTML si no es CLI
if (!$isCli) {
    echo '</body></html>';
}
?>
