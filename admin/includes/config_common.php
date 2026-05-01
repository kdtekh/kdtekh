<?php
// Habilitar visualización de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../error_log.txt');

// Incluir la configuración principal
require_once __DIR__ . '/../config.php';

// Incluir funciones de base de datos
require_once __DIR__ . '/database.php';

// La autenticación ahora se maneja en auth_common.php

// Inicializar variable de mensajes no leídos
$mensajesNoLeidos = 0;

// Solo intentar contar mensajes si la función getDbConnection existe
if (function_exists('getDbConnection')) {
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM mensajes_contacto WHERE leido = 0");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $mensajesNoLeidos = (int)$result['count'];
    } catch (PDOException $e) {
        // En caso de error, simplemente no mostramos el contador
        error_log("Error al contar mensajes no leídos: " . $e->getMessage());
        $mensajesNoLeidos = 0;
    }
}

// Configuración común para todas las páginas
if (!defined('SITE_URL')) {
    define('SITE_URL', 'https://' . $_SERVER['HTTP_HOST'] . '/admin/');
}
?>
