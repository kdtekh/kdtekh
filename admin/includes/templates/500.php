<?php
/**
 * Página de error 500 - Error interno del servidor
 */

// Configurar la respuesta HTTP 500
http_response_code(500);

// Configurar el título de la página
$pageTitle = 'Error del servidor';

// Determinar si mostrar el diseño completo o solo el contenido
$showLayout = !isset($noLayout) || $noLayout === false;

// Obtener el mensaje de error
$errorMessage = $errorMessage ?? 'Ha ocurrido un error inesperado en el servidor.';
$errorDetails = $errorDetails ?? '';
$errorFile = $errorFile ?? '';
$errorLine = $errorLine ?? '';

// Mostrar errores solo en entorno de desarrollo
$showDebugInfo = (defined('ENVIRONMENT') && ENVIRONMENT === 'development');

if ($showLayout) {
    // Incluir cabecera
    require_once __DIR__ . '/header.php';
}
?>

<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>500 <small>Error del servidor</small></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?php echo ADMIN_URL; ?>">Inicio</a></li>
                        <li class="breadcrumb-item active">500 Error</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="error-page">
            <h2 class="headline text-danger">500</h2>

            <div class="error-content">
                <h3><i class="fas fa-exclamation-triangle text-danger"></i> ¡Ups! Algo salió mal.</h3>

                <p>
                    <?php echo htmlspecialchars($errorMessage); ?>
                    <br>
                    Nuestro equipo ha sido notificado y estamos trabajando para solucionarlo.
                    <br>
                    Por favor, inténtalo de nuevo más tarde o <a href="<?php echo ADMIN_URL; ?>">vuelve al panel de control</a>.
                </p>

                <?php if ($showDebugInfo && !empty($errorDetails)): ?>
                    <div class="card card-danger card-outline mt-4">
                        <div class="card-header">
                            <h3 class="card-title">Detalles del error</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <p><strong>Mensaje:</strong> <?php echo htmlspecialchars($errorDetails); ?></p>
                            <?php if (!empty($errorFile)): ?>
                                <p><strong>Archivo:</strong> <?php echo htmlspecialchars($errorFile); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($errorLine)): ?>
                                <p><strong>Línea:</strong> <?php echo htmlspecialchars($errorLine); ?></p>
                            <?php endif; ?>
                            
                            <h5 class="mt-4">Traza de pila:</h5>
                            <pre class="bg-light p-3 rounded"><?php 
                                $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
                                foreach ($trace as $i => $call) {
                                    $file = $call['file'] ?? 'unknown';
                                    $line = $call['line'] ?? '0';
                                    $class = $call['class'] ?? '';
                                    $type = $call['type'] ?? '';
                                    $function = $call['function'] ?? '';
                                    $args = isset($call['args']) ? '('.count($call['args']).' args)' : '';
                                    
                                    echo sprintf(
                                        "#%d %s(%d): %s%s%s%s\n",
                                        $i,
                                        $file,
                                        $line,
                                        $class,
                                        $type,
                                        $function,
                                        $args
                                    );
                                }
                            ?></pre>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="mt-4">
                    <a href="<?php echo ADMIN_URL; ?>" class="btn btn-primary">
                        <i class="fas fa-tachometer-alt mr-2"></i> Volver al panel de control
                    </a>
                    <a href="<?php echo SITE_URL; ?>" class="btn btn-default">
                        <i class="fas fa-home mr-2"></i> Ir al sitio web
                    </a>
                    <button onclick="window.history.back();" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-2"></i> Volver atrás
                    </button>
                    <?php if (isUserLoggedIn()): ?>
                        <a href="<?php echo ADMIN_URL; ?>/logout.php" class="btn btn-danger float-right">
                            <i class="fas fa-sign-out-alt mr-2"></i> Cerrar sesión
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <!-- /.error-content -->
        </div>
        <!-- /.error-page -->
    </section>
    <!-- /.content -->
</div>

<?php
if ($showLayout) {
    // Incluir pie de página
    require_once __DIR__ . '/footer.php';
} else {
    // Si no se muestra el diseño completo, terminar la ejecución
    exit;
}
?>
