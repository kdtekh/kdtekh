<?php
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

// Configuración para Gmail
$config = [
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'username' => 'kdtech@gmail.com',  // Tu correo de Gmail
    'password' => 'AQUI_VA_TU_CONTRASENA_DE_APLICACION',  // La contraseña de aplicación que generaste
    'from_email' => 'kdtech@gmail.com',  // Debe ser el mismo que el username
    'from_name' => 'KDTekh Newsletter',
    'debug' => 2,  // Muestra mensajes de depuración
    'smtp_secure' => 'tls'  // Usar TLS
];

/* 
INSTRUCCIONES DE CONFIGURACIÓN:

1. Crea una contraseña de aplicación en Google:
   - Ve a https://myaccount.google.com/security
   - Activa la verificación en dos pasos si no está activada
   - Ve a "Contraseñas de aplicaciones"
   - Crea una nueva contraseña para "Newsletter KDTekh"
   - Copia la contraseña de 16 caracteres
   - Pégala reemplazando 'AQUI_VA_TU_CONTRASENA_DE_APLICACION' arriba

2. Asegúrate de que tu cuenta de Google permita el acceso de aplicaciones menos seguras:
   - Ve a https://myaccount.google.com/lesssecureapps
   - Activa la opción (si está disponible)

3. Si usas verificación en dos pasos, asegúrate de usar una contraseña de aplicación
   en lugar de tu contraseña normal de Google.

4. Si el envío falla, verifica la carpeta de spam o revisa los logs de error
   en tu servidor para más detalles.
*/

/* 
Configuración alternativa para otros proveedores SMTP (descomenta y configura según sea necesario):

// Para Hostinger/Namecheap/otros:
$config = [
    'host' => 'smtp.tudominio.com',
    'port' => 587,
    'username' => 'tu_correo@tudominio.com',
    'password' => 'tu_contraseña',
    'from_email' => 'tu_correo@tudominio.com',
    'from_name' => 'KDTekh Newsletter',
    'debug' => 2,
    'smtp_secure' => 'tls'
];
*/

// Ruta al archivo JSON
$dataFile = __DIR__ . '/../data/newsletter.min.json';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = $_POST['subject'] ?? '';
    $content = $_POST['content'] ?? '';
    
    if ($subject && $content) {
        try {
            // Leer el archivo existente
            $data = json_decode(file_get_contents($dataFile), true);
            
            // Obtener los emails de los suscriptores
            $subscribers = array_column($data['subscribers'], 'email');
            
            // Inicializar PHPMailer
            $mail = new PHPMailer(true);
            
            // Configurar SMTP
            $mail->isSMTP();
            $mail->Host = $config['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['username'];
            $mail->Password = $config['password'];
            $mail->SMTPSecure = $config['smtp_secure'] ?? PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $config['port'];
            $mail->SMTPDebug = $config['debug'] ?? 0;  // Nivel de depuración
            $mail->Debugoutput = function($str, $level) {
                error_log("SMTP Debug: $str");
            };
            
            // Configuración adicional para evitar problemas de certificado (solo para desarrollo)
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            
            // Configurar remitente
            $mail->setFrom($config['from_email'], $config['from_name']);
            $mail->Subject = $subject;
            $mail->isHTML(true);
            
            // Preparar el contenido HTML
            $htmlContent = '<!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #0a0e17; color: white; padding: 20px; text-align: center; }
                    .content { padding: 20px; }
                    .footer { text-align: center; color: #666; padding: 20px; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h1>KDTekh Newsletter</h1>
                    </div>
                    <div class="content">
                        ' . $content . '
                    </div>
                    <div class="footer">
                        <p>Este es un mensaje automático. Por favor, no respondas a este correo.</p>
                        <p>&copy; ' . date('Y') . ' KDTekh. Todos los derechos reservados.</p>
                    </div>
                </div>
            </body>
            </html>';
            
            // Enviar a cada suscriptor
            $successCount = 0;
            $errorCount = 0;
            
            foreach ($subscribers as $email) {
                try {
                    $mail->clearAddresses();
                    $mail->addAddress($email);
                    $mail->Body = $htmlContent;
                    
                    if ($mail->send()) {
                        $successCount++;
                    } else {
                        $errorCount++;
                    }
                } catch (Exception $e) {
                    $errorCount++;
                }
            }
            
            echo json_encode([
                'success' => $errorCount === 0,
                'message' => sprintf(
                    'Newsletter enviado exitosamente a %d suscriptores. %d errores encontrados.',
                    $successCount,
                    $errorCount
                )
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error al enviar el newsletter: ' . $e->getMessage()
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Por favor, completa todos los campos'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
}
