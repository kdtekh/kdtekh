<?php
// Configuración de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Obtener el código de error de la URL
$errorCode = isset($_GET['code']) ? (int)$_GET['code'] : 500;
$errorMessages = [
    400 => 'Solicitud incorrecta',
    401 => 'No autorizado',
    403 => 'Acceso prohibido',
    404 => 'Página no encontrada',
    500 => 'Error interno del servidor'
];

$errorMessage = $errorMessages[$errorCode] ?? 'Ha ocurrido un error';

// Establecer el código de estado HTTP
http_response_code($errorCode);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error <?php echo $errorCode; ?> - <?php echo htmlspecialchars($errorMessage); ?></title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            text-align: center;
        }
        .error-container {
            margin-top: 50px;
            padding: 30px;
            background-color: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .error-code {
            font-size: 72px;
            font-weight: bold;
            color: #dc3545;
            margin: 0;
        }
        .error-message {
            font-size: 24px;
            margin: 20px 0;
            color: #495057;
        }
        .error-description {
            color: #6c757d;
            margin-bottom: 30px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .error-details {
            margin-top: 30px;
            padding: 15px;
            background-color: #f1f1f1;
            border-radius: 4px;
            text-align: left;
            font-family: monospace;
            font-size: 14px;
            display: none;
        }
        .toggle-details {
            margin-top: 15px;
            color: #6c757d;
            cursor: pointer;
            text-decoration: underline;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1 class="error-code"><?php echo $errorCode; ?></h1>
        <h2 class="error-message"><?php echo htmlspecialchars($errorMessage); ?></h2>
        <p class="error-description">
            <?php if ($errorCode === 404): ?>
                Lo sentimos, la página que estás buscando no pudo ser encontrada.
            <?php elseif ($errorCode === 403): ?>
                No tienes permiso para acceder a este recurso.
            <?php elseif ($errorCode === 500): ?>
                Ocurrió un error interno en el servidor. Por favor, inténtalo de nuevo más tarde.
            <?php else: ?>
                Ha ocurrido un error inesperado.
            <?php endif; ?>
        </p>
        <a href="/" class="btn">Volver al Inicio</a>
        
        <div class="toggle-details" onclick="toggleErrorDetails()">Mostrar detalles del error</div>
        
        <div class="error-details" id="errorDetails">
            <p><strong>Error:</strong> <?php echo $errorCode . ' - ' . $errorMessage; ?></p>
            <p><strong>URL:</strong> <?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? ''); ?></p>
            <p><strong>Método:</strong> <?php echo $_SERVER['REQUEST_METHOD'] ?? ''; ?></p>
            <p><strong>Fecha y hora:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
            <p><strong>IP:</strong> <?php echo $_SERVER['REMOTE_ADDR'] ?? ''; ?></p>
            
            <?php if (isset($_SERVER['HTTP_REFERER'])): ?>
            <p><strong>Referente:</strong> <?php echo htmlspecialchars($_SERVER['HTTP_REFERER']); ?></p>
            <?php endif; ?>
            
            <?php if (isset($_SERVER['HTTP_USER_AGENT'])): ?>
            <p><strong>Navegador:</strong> <?php echo htmlspecialchars($_SERVER['HTTP_USER_AGENT']); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleErrorDetails() {
            const details = document.getElementById('errorDetails');
            const toggle = document.querySelector('.toggle-details');
            
            if (details.style.display === 'block') {
                details.style.display = 'none';
                toggle.textContent = 'Mostrar detalles del error';
            } else {
                details.style.display = 'block';
                toggle.textContent = 'Ocultar detalles del error';
            }
        }
    </script>
</body>
</html>
