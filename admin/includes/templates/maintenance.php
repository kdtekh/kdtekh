<?php
/**
 * Página de mantenimiento
 * 
 * Esta página se muestra cuando el sitio está en modo mantenimiento.
 * Los administradores pueden iniciar sesión para ver el sitio normalmente.
 */

// Configurar la respuesta HTTP 503
http_response_code(503);
header('Retry-After: 3600'); // Volver a intentar después de 1 hora

// Configurar el título de la página
$pageTitle = 'Sitio en mantenimiento';

// Verificar si el usuario es un administrador
$isAdmin = isUserLoggedIn() && hasRole('administrator');

// Si es administrador, redirigir al panel de control
if ($isAdmin && !isset($_GET['preview'])) {
    redirect(ADMIN_URL . '/index.php');
}

// Obtener la configuración de mantenimiento
$maintenanceTitle = 'Sitio en mantenimiento';
$maintenanceMessage = 'Actualmente estamos realizando tareas de mantenimiento. Por favor, vuelve más tarde.';
$estimatedTime = '1 hora';
$contactEmail = 'soporte@' . parse_url(SITE_URL, PHP_URL_HOST);

// Si hay un archivo de mensaje personalizado, usarlo
$customMessageFile = __DIR__ . '/maintenance-message.html';
if (file_exists($customMessageFile)) {
    $maintenanceMessage = file_get_contents($customMessageFile);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($maintenanceTitle); ?> - <?php echo SITE_NAME; ?></title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/plugins/fontawesome-free/css/all.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/adminlte.min.css">
    
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Source Sans Pro', sans-serif;
            color: #333;
            line-height: 1.6;
        }
        .maintenance-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
        .maintenance-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            overflow: hidden;
            width: 100%;
            max-width: 600px;
        }
        .maintenance-header {
            background: linear-gradient(135deg, #4b6cb7 0%, #182848 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .maintenance-header h1 {
            margin: 0;
            font-size: 2.2rem;
            font-weight: 700;
        }
        .maintenance-body {
            padding: 2.5rem;
        }
        .maintenance-icon {
            font-size: 4rem;
            color: #4b6cb7;
            margin-bottom: 1.5rem;
        }
        .maintenance-title {
            font-size: 1.8rem;
            margin-bottom: 1rem;
            color: #2c3e50;
            font-weight: 700;
        }
        .maintenance-message {
            font-size: 1.1rem;
            margin-bottom: 2rem;
            color: #555;
        }
        .maintenance-details {
            background: #f8f9fa;
            border-left: 4px solid #4b6cb7;
            padding: 1rem;
            margin: 1.5rem 0;
            border-radius: 0 4px 4px 0;
        }
        .maintenance-footer {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
            font-size: 0.9rem;
            color: #6c757d;
            text-align: center;
        }
        .btn-maintenance {
            padding: 0.6rem 1.5rem;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s;
        }
        .countdown {
            font-size: 1.2rem;
            font-weight: 700;
            color: #e74c3c;
            margin: 1rem 0;
        }
        .progress {
            height: 10px;
            border-radius: 5px;
            margin: 1.5rem 0;
            overflow: hidden;
        }
        .progress-bar {
            background: linear-gradient(90deg, #4b6cb7, #182848);
            animation: progress-animation 2s ease-in-out infinite;
            background-size: 200% 100%;
        }
        @keyframes progress-animation {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
    </style>
</head>
<body class="hold-transition">
<div class="maintenance-page">
    <div class="maintenance-card">
        <div class="maintenance-header">
            <h1><?php echo htmlspecialchars($maintenanceTitle); ?></h1>
        </div>
        
        <div class="maintenance-body text-center">
            <div class="maintenance-icon">
                <i class="fas fa-tools"></i>
            </div>
            
            <h2 class="maintenance-title">Estamos mejorando tu experiencia</h2>
            
            <div class="maintenance-message">
                <?php if (is_string($maintenanceMessage) && strpos($maintenanceMessage, '<') !== false): ?>
                    <?php echo $maintenanceMessage; ?>
                <?php else: ?>
                    <p><?php echo nl2br(htmlspecialchars($maintenanceMessage)); ?></p>
                <?php endif; ?>
                
                <div class="maintenance-details text-left">
                    <p class="mb-1"><i class="far fa-clock mr-2"></i> <strong>Tiempo estimado:</strong> <?php echo htmlspecialchars($estimatedTime); ?></p>
                    <p class="mb-0"><i class="far fa-envelope mr-2"></i> <strong>Contacto:</strong> <a href="mailto:<?php echo htmlspecialchars($contactEmail); ?>"><?php echo htmlspecialchars($contactEmail); ?></a></p>
                </div>
                
                <div class="countdown" id="countdown">Volviendo pronto...</div>
                <div class="progress">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%"></div>
                </div>
            </div>
            
            <?php if (!$isAdmin): ?>
                <div class="mt-4">
                    <a href="<?php echo ADMIN_URL; ?>/login.php" class="btn btn-primary btn-maintenance">
                        <i class="fas fa-sign-in-alt mr-2"></i> Iniciar sesión como administrador
                    </a>
                </div>
            <?php else: ?>
                <div class="mt-4">
                    <a href="<?php echo ADMIN_URL; ?>" class="btn btn-primary btn-maintenance">
                        <i class="fas fa-tachometer-alt mr-2"></i> Ir al panel de control
                    </a>
                    <a href="<?php echo SITE_URL; ?>?maintenance=0" class="btn btn-success btn-maintenance">
                        <i class="fas fa-eye mr-2"></i> Ver sitio web
                    </a>
                </div>
            <?php endif; ?>
            
            <div class="maintenance-footer">
                <p class="mb-0">
                    &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Todos los derechos reservados.
                    <br>
                    <small>Versión <?php echo APP_VERSION; ?></small>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- jQuery -->
<script src="<?php echo ASSETS_URL; ?>/plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="<?php echo ASSETS_URL; ?>/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>

<script>
$(function() {
    // Contador regresivo de ejemplo (1 hora)
    let seconds = 60 * 60; // 1 hora en segundos
    
    function updateCountdown() {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const remainingSeconds = seconds % 60;
        
        const display = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
        $('#countdown').text(`Volvemos en: ${display}`);
        
        if (seconds > 0) {
            seconds--;
            setTimeout(updateCountdown, 1000);
        } else {
            // Recargar la página cuando termine el tiempo
            location.reload();
        }
    }
    
    // Iniciar el contador
    updateCountdown();
    
    // Actualizar la barra de progreso
    function updateProgress() {
        const progress = (seconds / 3600) * 100; // 1 hora = 100%
        $('.progress-bar').css('width', progress + '%');
        
        if (seconds > 0) {
            requestAnimationFrame(updateProgress);
        }
    }
    
    // Iniciar la animación de la barra de progreso
    requestAnimationFrame(updateProgress);
});
</script>

</body>
</html>
<?php
// Terminar la ejecución del script
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}
exit(0);
?>
