<?php
/**
 * Configuración centralizada de CORS (Cross-Origin Resource Sharing)
 * 
 * Este archivo debe ser incluido al inicio de cualquier script PHP que maneje peticiones AJAX/API
 */

// Lista de orígenes permitidos
$allowedOrigins = [
    'https://kdtekh.com',
    'https://www.kdtekh.com'
];

// Obtener el origen de la petición
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

// Verificar si el origen está permitido
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    // Si el origen no está en la lista, usar el dominio principal
    header('Access-Control-Allow-Origin: https://kdtekh.com');
}

// Configuración estándar de CORS
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, Authorization');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400'); // 24 horas
header('Vary: Origin'); // Importante para el manejo de caché con CORS

// Manejar solicitud OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Configuración de seguridad adicional
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Si el script se incluye directamente, establecer el tipo de contenido
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'message' => 'CORS configuration loaded']);
    exit;
}
