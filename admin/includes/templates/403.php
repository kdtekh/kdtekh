<?php
/**
 * Página de error 403 - Acceso denegado
 */

// Configurar la respuesta HTTP 403
http_response_code(403);

// Configurar el título de la página
$pageTitle = 'Acceso denegado';

// Determinar si mostrar el diseño completo o solo el contenido
$showLayout = !isset($noLayout) || $noLayout === false;

if ($showLayout) {
    // Incluir cabecera
    require_once __DIR__ . '/header.php';
}

// Obtener el mensaje de error
$errorMessage = $errorMessage ?? 'No tienes permiso para acceder a esta página.';
?>

<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>403 <small>Acceso denegado</small></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?php echo ADMIN_URL; ?>">Inicio</a></li>
                        <li class="breadcrumb-item active">403 Error</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="error-page">
            <h2 class="headline text-danger">403</h2>

            <div class="error-content">
                <h3><i class="fas fa-ban text-danger"></i> ¡Acceso denegado!</h3>

                <p>
                    <?php echo htmlspecialchars($errorMessage); ?>
                    <br>
                    Por favor, contacta al administrador si crees que esto es un error.
                    <br>
                    Mientras tanto, puedes <a href="<?php echo ADMIN_URL; ?>">volver al panel de control</a>.
                </p>

                <div class="mt-4">
                    <a href="<?php echo ADMIN_URL; ?>" class="btn btn-primary">
                        <i class="fas fa-tachometer-alt mr-2"></i> Volver al panel de control
                    </a>
                    <a href="<?php echo SITE_URL; ?>" class="btn btn-default">
                        <i class="fas fa-home mr-2"></i> Ir al sitio web
                    </a>
                    <?php if (isUserLoggedIn()): ?>
                        <a href="<?php echo ADMIN_URL; ?>/logout.php" class="btn btn-danger float-right">
                            <i class="fas fa-sign-out-alt mr-2"></i> Cerrar sesión
                        </a>
                    <?php else: ?>
                        <a href="<?php echo ADMIN_URL; ?>/login.php" class="btn btn-success float-right">
                            <i class="fas fa-sign-in-alt mr-2"></i> Iniciar sesión
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
