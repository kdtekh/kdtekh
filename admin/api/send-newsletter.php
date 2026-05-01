<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config.php';

// Verificar si es una solicitud OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Solo permitir método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

// Obtener los datos del formulario
$input = json_decode(file_get_contents('php://input'), true);
$subject = filter_var($input['subject'] ?? '', FILTER_SANITIZE_STRING);
$content = $input['content'] ?? '';

// Validar los datos
if (empty($subject) || empty($content)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El asunto y el contenido son obligatorios']);
    exit();
}

try {
    // Obtener todos los suscriptores activos
    $stmt = $pdo->query("SELECT email FROM newsletter_subscribers WHERE is_active = 1");
    $subscribers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($subscribers)) {
        echo json_encode(['success' => false, 'message' => 'No hay suscriptores activos']);
        exit();
    }
    
    // Configurar los encabezados del correo
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=utf-8',
        'From: KDTekh <noreply@kdtekh.com>',
        'Reply-To: contacto@kdtekh.com',
        'X-Mailer: PHP/' . phpversion()
    ];
    
    $successCount = 0;
    $errorCount = 0;
    $errors = [];
    
    // Enviar el correo a cada suscriptor
    foreach ($subscribers as $email) {
        // Personalizar el contenido para cada suscriptor
        $personalizedContent = str_replace(
            ['{email}', '{fecha}'],
            [htmlspecialchars($email), date('d/m/Y')],
            $content
        );
        
        // Enviar el correo (en un entorno real, usaría una librería como PHPMailer)
        $sent = mail(
            $email,
            $subject,
            $personalizedContent,
            implode("\r\n", $headers)
        );
        
        if ($sent) {
            $successCount++;
        } else {
            $errorCount++;
            $errors[] = $email;
        }
    }
    
    // Registrar el envío en la base de datos
    $stmt = $pdo->prepare("INSERT INTO newsletter_campaigns (subject, content, sent_at, success_count, error_count) VALUES (?, ?, NOW(), ?, ?)");
    $stmt->execute([$subject, $content, $successCount, $errorCount]);
    
    // Preparar la respuesta
    $response = [
        'success' => true,
        'message' => 'Newsletter enviado correctamente',
        'stats' => [
            'total' => count($subscribers),
            'success' => $successCount,
            'errors' => $errorCount
        ]
    ];
    
    if (!empty($errors)) {
        $response['failed_emails'] = $errors;
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al enviar el newsletter: ' . $e->getMessage()
    ]);
}
?>
