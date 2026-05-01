<?php
/**
 * Punto de entrada principal de la aplicación
 * Maneja el enrutamiento básico y la autenticación
 */

// Iniciar la sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Obtener la URL solicitada
$request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
$request_path = parse_url($request_uri, PHP_URL_PATH);
$request_path = trim($request_path, '/');

// Si la ruta está vacía, cargar la página principal
if (empty($request_path)) {
    include __DIR__ . '/index.html';
    exit;
}

// Las rutas de autenticación han sido eliminadas.

// Si la ruta es un archivo o directorio existente, servirlo directamente
$file_path = __DIR__ . '/' . $request_path;

// Si la ruta termina con .html, verificar si el archivo existe
if (preg_match('/\.html$/', $request_path) && file_exists($file_path) && !is_dir($file_path)) {
    readfile($file_path);
    exit;
}

// Para otros tipos de archivos estáticos
if (file_exists($file_path) && !is_dir($file_path)) {
    $mime_types = [
        'css'  => 'text/css',
        'js'   => 'application/javascript',
        'json' => 'application/json',
        'xml'  => 'application/xml',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'gif'  => 'image/gif',
        'svg'  => 'image/svg+xml',
        'ico'  => 'image/x-icon',
        'woff' => 'application/font-woff',
        'woff2'=> 'application/font-woff2',
        'ttf'  => 'application/font-ttf',
        'eot'  => 'application/vnd.ms-fontobject',
        'otf'  => 'application/font-otf',
        'map'  => 'application/json'
    ];

    $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    if (isset($mime_types[$extension])) {
        header('Content-Type: ' . $mime_types[$extension]);
    } else {
        // Si no se reconoce el tipo MIME, establecer uno genérico
        header('Content-Type: application/octet-stream');
    }
    
    readfile($file_path);
    exit;
}

// Si la ruta no existe, mostrar error 404
header('HTTP/1.0 404 Not Found');
include __DIR__ . '/error.html';
?>
