<?php
/**
 * Manejador de subida de archivos para el panel de administración
 */

// Incluir archivos necesarios
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

// Verificar si la solicitud es POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit();
}

// Verificar si el usuario está autenticado
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

// Configuración de subida
$uploadDir = UPLOADS_PATH . 'images/' . date('Y/m/');
$allowedTypes = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
    'image/webp' => 'webp',
    'application/pdf' => 'pdf',
    'application/msword' => 'doc',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    'application/vnd.ms-excel' => 'xls',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
    'application/vnd.ms-powerpoint' => 'ppt',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
    'text/plain' => 'txt',
    'text/csv' => 'csv',
    'application/zip' => 'zip',
    'application/x-rar-compressed' => 'rar',
    'application/x-7z-compressed' => '7z'
];
$maxFileSize = 10 * 1024 * 1024; // 10MB

// Crear directorio de subida si no existe
if (!file_exists($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        http_response_code(500);
        echo json_encode(['error' => 'No se pudo crear el directorio de subida']);
        exit();
    }
}

// Procesar archivos subidos
$response = [];
$uploadedFiles = [];

foreach ($_FILES as $key => $file) {
    if ($file['error'] === UPLOAD_ERR_OK) {
        $fileName = $file['name'];
        $fileTmpName = $file['tmp_name'];
        $fileSize = $file['size'];
        $fileType = $file['type'];
        
        // Validar tipo de archivo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedType = finfo_file($finfo, $fileTmpName);
        finfo_close($finfo);
        
        if (!in_array($detectedType, array_keys($allowedTypes))) {
            $response['errors'][$key] = 'Tipo de archivo no permitido: ' . $detectedType;
            continue;
        }
        
        // Validar tamaño de archivo
        if ($fileSize > $maxFileSize) {
            $response['errors'][$key] = 'El archivo excede el tamaño máximo permitido (' . formatBytes($maxFileSize) . ')';
            continue;
        }
        
        // Generar nombre único para el archivo
        $fileExt = $allowedTypes[$detectedType];
        $newFileName = uniqid() . '.' . $fileExt;
        $destination = $uploadDir . $newFileName;
        
        // Mover el archivo subido
        if (move_uploaded_file($fileTmpName, $destination)) {
            // Guardar información del archivo en la base de datos
            $relativePath = str_replace(UPLOADS_PATH, '', $destination);
            
            try {
                $table = DB::table('media');
                $sql = "INSERT INTO $table (user_id, file_name, file_path, file_type, file_size, mime_type, created_at) 
                        VALUES (:user_id, :file_name, :file_path, :file_type, :file_size, :mime_type, NOW())";
                
                $params = [
                    ':user_id' => $_SESSION['user_id'],
                    ':file_name' => $fileName,
                    ':file_path' => $relativePath,
                    ':file_type' => $fileExt,
                    ':file_size' => $fileSize,
                    ':mime_type' => $detectedType
                ];
                
                DB::query($sql, $params);
                $fileId = DB::lastInsertId();
                
                $uploadedFiles[] = [
                    'id' => $fileId,
                    'name' => $fileName,
                    'path' => $relativePath,
                    'url' => UPLOADS_URL . $relativePath,
                    'type' => $detectedType,
                    'size' => $fileSize,
                    'size_formatted' => formatBytes($fileSize)
                ];
                
            } catch (Exception $e) {
                // Si hay un error al guardar en la base de datos, eliminar el archivo subido
                if (file_exists($destination)) {
                    unlink($destination);
                }
                
                error_log("Error al guardar el archivo en la base de datos: " . $e->getMessage());
                $response['errors'][$key] = 'Error al procesar el archivo';
                continue;
            }
        } else {
            $response['errors'][$key] = 'Error al subir el archivo';
        }
    } else {
        // Manejar errores de subida
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido por el servidor.',
            UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo permitido.',
            UPLOAD_ERR_PARTIAL => 'El archivo fue subido solo parcialmente.',
            UPLOAD_ERR_NO_FILE => 'No se seleccionó ningún archivo.',
            UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal.',
            UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo en el disco.',
            UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la subida del archivo.'
        ];
        
        $response['errors'][$key] = $uploadErrors[$file['error']] ?? 'Error desconocido al subir el archivo';
    }
}

// Configurar respuesta
if (!empty($uploadedFiles)) {
    $response['success'] = true;
    $response['files'] = $uploadedFiles;
    
    if (count($uploadedFiles) === 1) {
        $response['file'] = $uploadedFiles[0];
    }
} else {
    $response['success'] = false;
    if (!isset($response['errors'])) {
        $response['error'] = 'No se subieron archivos';
    }
}

// Enviar respuesta
header('Content-Type: application/json');
echo json_encode($response);

exit();

/**
 * Formatea bytes a un formato legible
 * 
 * @param int $bytes Número de bytes
 * @param int $precision Precisión decimal
 * @return string Cadena formateada
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}
