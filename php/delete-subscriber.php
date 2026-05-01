<?php
header('Content-Type: application/json');

// Ruta al archivo JSON
$dataFile = __DIR__ . '/../data/newsletter.min.json';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    
    if ($id) {
        try {
            // Leer el archivo existente
            $data = json_decode(file_get_contents($dataFile), true);
            
            // Encontrar y eliminar el suscriptor
            $found = false;
            foreach ($data['subscribers'] as $key => $subscriber) {
                if ($subscriber['id'] == $id) {
                    unset($data['subscribers'][$key]);
                    $found = true;
                    break;
                }
            }
            
            if ($found) {
                // Reindexar el array
                $data['subscribers'] = array_values($data['subscribers']);
                
                // Guardar los cambios
                file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT));
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Suscriptor eliminado exitosamente'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Suscriptor no encontrado'
                ]);
            }
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error al eliminar el suscriptor'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'ID del suscriptor no proporcionado'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
}
