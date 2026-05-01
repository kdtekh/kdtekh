<?php
/**
 * Funciones específicas para el panel de administración (Parte 3)
 */

// Incluir archivos necesarios
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

/**
 * Obtiene la URL de la imagen de perfil del usuario
 * 
 * @param string $avatar Nombre del archivo de avatar
 * @param string $size Tamaño de la imagen (thumb, small, medium, large)
 * @return string URL completa de la imagen
 */
function getAvatarUrl($avatar = null, $size = 'small') {
    if (empty($avatar)) {
        $avatar = 'default-avatar.png';
    }
    
    $sizes = [
        'thumb' => '50x50',
        'small' => '100x100',
        'medium' => '200x200',
        'large' => '500x500'
    ];
    
    $sizePath = isset($sizes[$size]) ? $sizes[$size] . '/' : '';
    
    return ADMIN_URL . '/assets/img/avatars/' . $sizePath . $avatar;
}

/**
 * Sube un archivo al servidor
 * 
 * @param array $file Array $_FILES del archivo
 * @param string $type Tipo de archivo (image, document, media)
 * @param array $options Opciones adicionales
 * @return array Resultado de la operación
 */
function uploadFile($file, $type = 'image', $options = []) {
    // Configuración por defecto
    $defaults = [
        'max_size' => 5 * 1024 * 1024, // 5MB
        'allowed_types' => [],
        'upload_path' => '',
        'filename' => '',
        'overwrite' => false,
        'create_thumb' => true,
        'resize' => []
    ];
    
    // Fusionar con opciones proporcionadas
    $options = array_merge($defaults, $options);
    
    // Validar que se haya subido un archivo
    if (!isset($file['error']) || is_array($file['error'])) {
        return [
            'success' => false,
            'message' => 'Parámetros de archivo no válidos.'
        ];
    }
    
    // Verificar errores de carga
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            return [
                'success' => false,
                'message' => 'No se ha subido ningún archivo.'
            ];
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return [
                'success' => false,
                'message' => 'El archivo excede el tamaño máximo permitido.'
            ];
        default:
            return [
                'success' => false,
                'message' => 'Error al subir el archivo.'
            ];
    }
    
    // Verificar tamaño del archivo
    if ($file['size'] > $options['max_size']) {
        return [
            'success' => false,
            'message' => 'El archivo es demasiado grande. Tamaño máximo: ' . 
                        formatBytes($options['max_size'])
        ];
    }
    
    // Obtener información del archivo
    $fileInfo = pathinfo($file['name']);
    $extension = strtolower($fileInfo['extension'] ?? '');
    $filename = !empty($options['filename']) ? 
                $options['filename'] . '.' . $extension : 
                $fileInfo['filename'];
    
    // Establecer tipos de archivo permitidos según el tipo
    if (empty($options['allowed_types'])) {
        switch ($type) {
            case 'image':
                $options['allowed_types'] = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                break;
            case 'document':
                $options['allowed_types'] = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv'];
                break;
            case 'media':
                $options['allowed_types'] = ['mp3', 'mp4', 'm4a', 'mov', 'wav', 'ogg', 'webm'];
                break;
            default:
                $options['allowed_types'] = [];
        }
    }
    
    // Verificar tipo de archivo
    if (!in_array($extension, $options['allowed_types'])) {
        return [
            'success' => false,
            'message' => 'Tipo de archivo no permitido. Tipos permitidos: ' . 
                        implode(', ', $options['allowed_types'])
        ];
    }
    
    // Establecer ruta de carga
    $uploadPath = !empty($options['upload_path']) ? 
                 rtrim($options['upload_path'], '/') . '/' : 
                 ADMIN_PATH . '/uploads/' . $type . '/' . date('Y/m/');
    
    // Crear directorio si no existe
    if (!is_dir($uploadPath)) {
        if (!mkdir($uploadPath, 0755, true)) {
            return [
                'success' => false,
                'message' => 'No se pudo crear el directorio de carga.'
            ];
        }
    }
    
    // Generar nombre de archivo único si es necesario
    $fullPath = $uploadPath . $filename . '.' . $extension;
    $counter = 1;
    
    while (file_exists($fullPath) && !$options['overwrite']) {
        $fullPath = $uploadPath . $filename . '_' . $counter . '.' . $extension;
        $counter++;
    }
    
    // Mover el archivo cargado
    if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
        return [
            'success' => false,
            'message' => 'Error al guardar el archivo.'
        ];
    }
    
    // Procesar la imagen si es necesario
    $result = [
        'success' => true,
        'message' => 'Archivo subido correctamente.',
        'file_name' => basename($fullPath),
        'file_path' => $fullPath,
        'file_url' => str_replace(ADMIN_PATH, ADMIN_URL, $fullPath),
        'file_type' => mime_content_type($fullPath),
        'file_size' => filesize($fullPath),
        'thumbnails' => []
    ];
    
    // Crear miniaturas para imágenes
    if ($type === 'image' && $options['create_thumb']) {
        $result['thumbnails'] = createImageThumbnails($fullPath, $options['resize']);
    }
    
    return $result;
}

/**
 * Crea miniaturas de una imagen
 * 
 * @param string $sourcePath Ruta de la imagen original
 * @param array $sizes Array con los tamaños de las miniaturas
 * @return array Array con las rutas de las miniaturas creadas
 */
function createImageThumbnails($sourcePath, $sizes = []) {
    if (!extension_loaded('gd') || !function_exists('getimagesize')) {
        return [];
    }
    
    // Tamaños por defecto si no se especifican
    if (empty($sizes)) {
        $sizes = [
            'thumb' => [100, 100, true],
            'small' => [300, 300, false],
            'medium' => [600, 600, false],
            'large' => [1200, 1200, false]
        ];
    }
    
    $thumbnails = [];
    $fileInfo = pathinfo($sourcePath);
    $extension = strtolower($fileInfo['extension']);
    
    // Cargar la imagen original
    switch ($extension) {
        case 'jpg':
        case 'jpeg':
            $sourceImage = imagecreatefromjpeg($sourcePath);
            break;
        case 'png':
            $sourceImage = imagecreatefrompng($sourcePath);
            break;
        case 'gif':
            $sourceImage = imagecreatefromgif($sourcePath);
            break;
        case 'webp':
            $sourceImage = imagecreatefromwebp($sourcePath);
            break;
        default:
            return [];
    }
    
    if (!$sourceImage) {
        return [];
    }
    
    // Obtener dimensiones originales
    $originalWidth = imagesx($sourceImage);
    $originalHeight = imagesy($sourceImage);
    
    // Crear directorio de miniaturas si no existe
    $thumbDir = $fileInfo['dirname'] . '/thumbs/';
    if (!is_dir($thumbDir)) {
        mkdir($thumbDir, 0755, true);
    }
    
    // Procesar cada tamaño
    foreach ($sizes as $name => $size) {
        list($width, $height, $crop) = $size;
        
        // Calcular nuevas dimensiones manteniendo la relación de aspecto
        $ratio = $originalWidth / $originalHeight;
        
        if ($crop) {
            // Recortar la imagen para que encaje exactamente en las dimensiones
            if ($originalWidth > $originalHeight) {
                $newWidth = $originalWidth * ($height / $originalHeight);
                $newHeight = $height;
                $srcX = ($newWidth - $width) / 2;
                $srcY = 0;
            } else {
                $newWidth = $width;
                $newHeight = $originalHeight * ($width / $originalWidth);
                $srcX = 0;
                $srcY = ($newHeight - $height) / 2;
            }
            
            $thumbImage = imagecreatetruecolor($width, $height);
            
            // Mantener la transparencia para PNG y GIF
            if ($extension === 'png' || $extension === 'gif') {
                imagealphablending($thumbImage, false);
                imagesavealpha($thumbImage, true);
                $transparent = imagecolorallocatealpha($thumbImage, 255, 255, 255, 127);
                imagefilledrectangle($thumbImage, 0, 0, $width, $height, $transparent);
            }
            
            // Redimensionar y recortar
            imagecopyresampled(
                $thumbImage, $sourceImage, 
                0, 0, $srcX, $srcY, 
                $width, $height, 
                $width, $height
            );
            
        } else {
            // Redimensionar manteniendo la relación de aspecto
            if ($width / $height > $ratio) {
                $newWidth = $height * $ratio;
                $newHeight = $height;
            } else {
                $newWidth = $width;
                $newHeight = $width / $ratio;
            }
            
            $thumbImage = imagecreatetruecolor($newWidth, $newHeight);
            
            // Mantener la transparencia para PNG y GIF
            if ($extension === 'png' || $extension === 'gif') {
                imagealphablending($thumbImage, false);
                imagesavealpha($thumbImage, true);
                $transparent = imagecolorallocatealpha($thumbImage, 255, 255, 255, 127);
                imagefilledrectangle($thumbImage, 0, 0, $newWidth, $newHeight, $transparent);
            }
            
            // Redimensionar
            imagecopyresampled(
                $thumbImage, $sourceImage, 
                0, 0, 0, 0, 
                $newWidth, $newHeight, 
                $originalWidth, $originalHeight
            );
        }
        
        // Guardar la miniatura
        $thumbPath = $thumbDir . $fileInfo['filename'] . '_' . $name . '.' . $extension;
        
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($thumbImage, $thumbPath, 90);
                break;
            case 'png':
                imagepng($thumbImage, $thumbPath, 9);
                break;
            case 'gif':
                imagegif($thumbImage, $thumbPath);
                break;
            case 'webp':
                imagewebp($thumbImage, $thumbPath, 90);
                break;
        }
        
        // Liberar memoria
        imagedestroy($thumbImage);
        
        // Agregar a los resultados
        $thumbnails[$name] = [
            'path' => $thumbPath,
            'url' => str_replace(ADMIN_PATH, ADMIN_URL, $thumbPath),
            'width' => $crop ? $width : $newWidth,
            'height' => $crop ? $height : $newHeight
        ];
    }
    
    // Liberar memoria de la imagen original
    imagedestroy($sourceImage);
    
    return $thumbnails;
}

/**
 * Formatea un tamaño en bytes a un formato legible
 * 
 * @param int $bytes Tamaño en bytes
 * @param int $precision Número de decimales
 * @return string Tamaño formateado
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= (1 << (10 * $pow));
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Obtiene la URL de un archivo de medios
 * 
 * @param string $filename Nombre del archivo
 * @param string $type Tipo de archivo (images, documents, media)
 * @param string $size Tamaño de la imagen (solo para imágenes)
 * @return string URL completa del archivo
 */
function getMediaUrl($filename, $type = 'images', $size = '') {
    if (empty($filename)) {
        return '';
    }
    
    $baseUrl = ADMIN_URL . '/uploads/' . $type . '/';
    
    // Si es una imagen y se solicita un tamaño específico
    if ($type === 'images' && !empty($size)) {
        $pathInfo = pathinfo($filename);
        $thumbPath = $pathInfo['dirname'] . '/thumbs/' . $pathInfo['filename'] . '_' . $size . '.' . $pathInfo['extension'];
        
        // Verificar si existe la miniatura
        $fullPath = str_replace(ADMIN_URL . '/', ADMIN_PATH . '/', $thumbPath);
        if (file_exists($fullPath)) {
            return $thumbPath;
        }
    }
    
    return $baseUrl . $filename;
}

/**
 * Obtiene la lista de archivos de un directorio
 * 
 * @param string $directory Directorio a escanear
 * @param array $extensions Extensiones permitidas (opcional)
 * @return array Lista de archivos
 */
function getFilesList($directory, $extensions = []) {
    $files = [];
    
    // Asegurarse de que el directorio existe
    if (!is_dir($directory)) {
        return $files;
    }
    
    // Escanear el directorio
    $items = scandir($directory);
    
    foreach ($items as $item) {
        // Saltar directorios especiales
        if ($item === '.' || $item === '..') {
            continue;
        }
        
        $path = $directory . '/' . $item;
        
        // Si es un directorio, escanear recursivamente
        if (is_dir($path)) {
            $files = array_merge($files, getFilesList($path, $extensions));
            continue;
        }
        
        // Si se especificaron extensiones, verificar que el archivo tenga una extensión permitida
        if (!empty($extensions)) {
            $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
            if (!in_array($ext, $extensions)) {
                continue;
            }
        }
        
        // Agregar el archivo a la lista
        $files[] = [
            'name' => $item,
            'path' => $path,
            'url' => str_replace(ADMIN_PATH, ADMIN_URL, $path),
            'size' => filesize($path),
            'modified' => filemtime($path),
            'type' => mime_content_type($path)
        ];
    }
    
    return $files;
}

/**
 * Elimina un archivo o directorio de forma recursiva
 * 
 * @param string $path Ruta al archivo o directorio
 * @return bool Verdadero si se eliminó correctamente, falso en caso contrario
 */
function deleteFile($path) {
    // Verificar si el archivo o directorio existe
    if (!file_exists($path)) {
        return false;
    }
    
    // Si es un directorio, eliminar su contenido primero
    if (is_dir($path)) {
        $items = array_diff(scandir($path), ['.', '..']);
        
        foreach ($items as $item) {
            deleteFile($path . '/' . $item);
        }
        
        // Eliminar el directorio vacío
        return rmdir($path);
    }
    
    // Si es un archivo, eliminarlo
    return unlink($path);
}
