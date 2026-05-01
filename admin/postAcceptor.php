<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers for JSON response
header('Content-Type: application/json');

// Check if this is a valid request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'Method not allowed']));
}

// Check if file was uploaded without errors
if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['file'];
    
    // Validate file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowed_types)) {
        http_response_code(400);
        die(json_encode(['error' => 'Solo se permiten imágenes (JPEG, PNG, GIF, WebP)']));
    }
    
    // Create uploads directory if it doesn't exist
    $upload_dir = __DIR__ . '/../uploads/blog/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Generate a unique filename
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $file_extension;
    $destination = $upload_dir . $filename;
    
    // Move the uploaded file
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        // Return the URL of the uploaded file
        $file_url = 'https://' . $_SERVER['HTTP_HOST'] . '/uploads/blog/' . $filename;
        echo json_encode(['location' => $file_url]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al subir el archivo']);
    }
} else {
    $error = $_FILES['file']['error'] ?? 'No se subió ningún archivo';
    $error_messages = [
        UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido',
        UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo del formulario',
        UPLOAD_ERR_PARTIAL => 'El archivo solo se subió parcialmente',
        UPLOAD_ERR_NO_FILE => 'No se seleccionó ningún archivo',
        UPLOAD_ERR_NO_TMP_DIR => 'No se encontró el directorio temporal',
        UPLOAD_ERR_CANT_WRITE => 'Error al escribir el archivo en el disco',
        UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la carga del archivo',
    ];
    
    $error_message = $error_messages[$error] ?? 'Error desconocido al subir el archivo';
    http_response_code(400);
    echo json_encode(['error' => $error_message]);
}
?>
