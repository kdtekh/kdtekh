<?php
/**
 * Página de error 404 - No encontrado
 */

// Configurar la respuesta HTTP 404
http_response_code(404);

// Configurar el título de la página
$pageTitle = 'Página no encontrada';

// Determinar si mostrar el diseño completo o solo el contenido
$showLayout = !isset($noLayout) || $noLayout === false;

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
                    <h1>404 <small>Página no encontrada</small></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?php echo ADMIN_URL; ?>">Inicio</a></li>
                        <li class="breadcrumb-item active">404 Error</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="error-page">
            <h2 class="headline text-warning">404</h2>

            <div class="error-content">
                <h3><i class="fas fa-exclamation-triangle text-warning"></i> ¡Ups! Página no encontrada.</h3>

                <p>
                    No pudimos encontrar la página que estabas buscando.
                    Mientras tanto, puedes <a href="<?php echo ADMIN_URL; ?>">volver al panel de control</a> o intentar usar el formulario de búsqueda.
                </p>

                <form class="search-form">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Buscar...">

                        <div class="input-group-append">
                            <button type="submit" name="submit" class="btn btn-warning">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <!-- /.input-group -->
                </form>
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
