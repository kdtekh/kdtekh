<?php
/**
 * Encabezado del panel de administración
 */

// Iniciar el buffer de salida
ob_start();

// Configuración de seguridad de sesión
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
ini_set('session.use_only_cookies', 1);

// Headers de seguridad
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// CSP Header (Content Security Policy)
$csp = [
    "default-src 'self' https: data: 'unsafe-inline' 'unsafe-eval';",
    "script-src 'self' 'unsafe-inline' 'unsafe-eval' https:;",
    "style-src 'self' 'unsafe-inline' https:;",
    "img-src 'self' data: https: http:;",
    "font-src 'self' https: data:;",
    "connect-src 'self' https:;",
    "frame-src 'self' https:;",
    "media-src 'self' https: data:;"
];
header("Content-Security-Policy: " . implode(" ", $csp));

// Verificar si el usuario está autenticado
$isLoggedIn = isUserLoggedIn();
$currentUser = $isLoggedIn ? getUserById($_SESSION['user_id']) : null;

// Obtener notificaciones no leídas
$unreadCount = 0;
if ($isLoggedIn) {
    $unreadCount = DB::fetchColumn(
        "SELECT COUNT(*) FROM notifications 
         WHERE (user_id = :user_id OR user_id IS NULL) 
         AND (read_at IS NULL OR read_at = '')",
        [':user_id' => $_SESSION['user_id']]
    );
}

// Obtener la ruta actual y configurar clases de cuerpo
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$bodyClass = 'sidebar-mini';
if (strpos($currentPath, '/admin/login.php') !== false) {
    $bodyClass = 'login-page';
} elseif (strpos($currentPath, '/admin/forgot-password') !== false) {
    $bodyClass = 'forgot-password-page';
} elseif (strpos($currentPath, '/admin/reset-password') !== false) {
    $bodyClass = 'reset-password-page';
}
?>
<!DOCTYPE html>
<html lang="es" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Panel de administración de <?php echo htmlspecialchars(SITE_NAME); ?>">
    <meta name="author" content="<?php echo htmlspecialchars(SITE_NAME); ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="robots" content="noindex, nofollow">
    
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : ''; ?><?php echo htmlspecialchars(SITE_NAME); ?> - Panel de Administración</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/plugins/fontawesome-free/css/all.min.css">
    
    <!-- Theme style -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/adminlte.min.css">
    
    <!-- DataTables -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
    
    <!-- Select2 -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/plugins/select2/css/select2.min.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
    
    <!-- Summernote -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/plugins/summernote/summernote-bs4.min.css">
    
    <!-- Toastr -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/plugins/toastr/toastr.min.css">
    
    <!-- Custom styles -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/custom.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo ASSETS_URL; ?>/img/favicon.png">
    <link rel="apple-touch-icon" href="<?php echo ASSETS_URL; ?>/img/apple-touch-icon.png">
    
    <!-- Preload critical resources -->
    <link rel="preload" href="<?php echo ASSETS_URL; ?>/plugins/fontawesome-free/webfonts/fa-solid-900.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="<?php echo ASSETS_URL; ?>/plugins/fontawesome-free/webfonts/fa-regular-400.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="<?php echo ASSETS_URL; ?>/plugins/fontawesome-free/webfonts/fa-brands-400.woff2" as="font" type="font/woff2" crossorigin>
    
    <!-- Preconnect to external domains -->
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="dns-prefetch" href="//www.google-analytics.com">
    
    <?php if (isset($extraCss)): ?>
        <?php foreach ($extraCss as $css): ?>
            <link rel="stylesheet" href="<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- jQuery -->
    <script src="<?php echo ASSETS_URL; ?>/plugins/jquery/jquery.min.js"></script>
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?php echo generateCsrfToken(); ?>">
    
    <!-- Scripts globales -->
    <script nonce="<?php echo generateNonce(); ?>">
        // Configuración global
        window.App = {
            csrfToken: '<?php echo generateCsrfToken(); ?>',
            baseUrl: '<?php echo SITE_URL; ?>',
            adminUrl: '<?php echo ADMIN_URL; ?>',
            assetsUrl: '<?php echo ASSETS_URL; ?>',
            currentUser: <?php echo $isLoggedIn ? json_encode($currentUser) : 'null'; ?>
        };
        
        // Configuración de accesibilidad
        document.documentElement.setAttribute('data-bs-theme', window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        
        // Detectar si es un dispositivo táctil
        window.isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0 || navigator.msMaxTouchPoints > 0;
        
        // Configuración de AJAX
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': window.App.csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        // Manejar errores de AJAX
        $(document).ajaxError(function(event, jqXHR, settings, error) {
            console.error('AJAX Error:', {
                status: jqXHR.status,
                statusText: jqXHR.statusText,
                response: jqXHR.responseText,
                url: settings.url,
                method: settings.type,
                error: error
            });
            
            // Ignorar peticiones canceladas
            if (error === 'abort') return;
            
            // Mostrar mensaje de error al usuario
            let errorMessage = 'Ha ocurrido un error inesperado.';
            let errorTitle = 'Error';
            
            if (jqXHR.status === 0) {
                errorMessage = 'No se pudo conectar con el servidor. Verifica tu conexión a internet.';
            } else if (jqXHR.status === 401) {
                errorMessage = 'Tu sesión ha expirado. Serás redirigido para iniciar sesión.';
                errorTitle = 'Sesión expirada';
                setTimeout(() => window.location.href = window.App.adminUrl + '/login.php?expired=1', 2000);
                return;
            } else if (jqXHR.status === 403) {
                errorMessage = 'No tienes permiso para realizar esta acción.';
                errorTitle = 'Acceso denegado';
            } else if (jqXHR.status === 404) {
                errorMessage = 'El recurso solicitado no fue encontrado.';
                errorTitle = 'No encontrado';
            } else if (jqXHR.status >= 500) {
                errorMessage = 'Error interno del servidor. Por favor, inténtalo de nuevo más tarde.';
                errorTitle = 'Error del servidor';
            }
            
            // Mostrar mensaje de error
            if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                showToast('error', errorTitle, jqXHR.responseJSON.message);
            } else {
                showToast('error', errorTitle, errorMessage);
            }
        });
        
        // Función para mostrar notificaciones
        function showToast(type, title, message) {
            const toast = {
                title: title || type.charAt(0).toUpperCase() + type.slice(1),
                message: message,
                type: type,
                icon: 'fas fa-info-circle',
                position: 'topRight',
                timeOut: 5000
            };
            
            switch (type) {
                case 'success': toast.icon = 'fas fa-check-circle'; break;
                case 'error': toast.icon = 'fas fa-exclamation-circle'; break;
                case 'warning': toast.icon = 'fas fa-exclamation-triangle'; break;
            }
            
            // Usar Toastr si está disponible, de lo contrario usar alert
            if (typeof toastr !== 'undefined') {
                toastr[type](message, title);
            } else {
                alert(`[${title}] ${message}`);
            }
        }
        
        // Mostrar mensajes flash del servidor
        $(document).ready(function() {
            // Inicializar tooltips
            $('[data-toggle="tooltip"]').tooltip();
        });
    </script>
</head>
<body class="hold-transition <?php echo $bodyClass; ?>">
<?php if ($isLoggedIn && !in_array($bodyClass, ['login-page', 'forgot-password-page', 'reset-password-page'])): ?>
<div class="wrapper">
    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <!-- Sidebar toggle button -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                    <i class="fas fa-bars"></i>
                </a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="<?php echo ADMIN_URL; ?>" class="nav-link">Inicio</a>
            </li>
        </ul>

        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
            <!-- Botón de actualización -->
            <li class="nav-item">
                <a class="nav-link" href="#" onclick="window.location.reload(true); return false;" title="Actualizar página">
                    <i class="fas fa-sync-alt"></i>
                </a>
            </li>
            
            <!-- Notifications -->
            <li class="nav-item dropdown notifications-menu">
                <a class="nav-link" data-toggle="dropdown" href="#" aria-label="Notificaciones" role="button" aria-haspopup="true" aria-expanded="false">
                    <i class="far fa-bell" aria-hidden="true"></i>
                    <?php if ($unreadCount > 0): ?>
                        <span class="badge badge-warning navbar-badge" aria-hidden="true"><?php echo $unreadCount; ?></span>
                        <span class="sr-only"><?php echo $unreadCount; ?> notificaciones sin leer</span>
                    <?php endif; ?>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right" role="menu">
                    <span class="dropdown-header"><?php echo $unreadCount; ?> Notificaciones</span>
                    <div class="dropdown-divider"></div>
                    <div id="notifications-container" class="dropdown-list">
                        <div class="text-center py-3">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Cargando notificaciones...</span>
                            </div>
                        </div>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a href="<?php echo ADMIN_URL; ?>/notifications.php" class="dropdown-item dropdown-footer" role="menuitem">Ver todas las notificaciones</a>
                </div>
            </li>
            <script nonce="<?php echo generateNonce(); ?>">
            // Cargar notificaciones al abrir el menú
            document.addEventListener('DOMContentLoaded', function() {
                const notificationsMenu = document.querySelector('.notifications-menu');
                
                notificationsMenu.addEventListener('shown.bs.dropdown', function() {
                    if (window.notificationsLoaded) return;
                    
                    $.ajax({
                        url: window.App.adminUrl + '/ajax/get-notifications.php',
                        type: 'GET',
                        dataType: 'json',
                        success: function(response) {
                            const container = document.getElementById('notifications-container');
                            if (response.success && response.data && response.data.length > 0) {
                                let html = '';
                                response.data.forEach(function(notification) {
                                    const timeAgo = new Date(notification.created_at).toLocaleTimeString('es-ES', {
                                        hour: '2-digit',
                                        minute: '2-digit'
                                    });
                                    
                                    html += `
                                    <a href="${notification.url || '#'}" class="dropdown-item" role="menuitem">
                                        <div class="d-flex align-items-center">
                                            <div class="notification-icon">
                                                <i class="${notification.icon || 'fas fa-info-circle'} ${notification.type || 'text-primary'}" aria-hidden="true"></i>
                                            </div>
                                            <div class="notification-content">
                                                <p class="mb-0">${notification.message || 'Nueva notificación'}</p>
                                                <small class="text-muted">${timeAgo}</small>
                                            </div>
                                        </div>
                                    </a>
                                    <div class="dropdown-divider"></div>`;
                                });
                                container.innerHTML = html;
                            } else {
                                container.innerHTML = `
                                    <div class="dropdown-item text-center py-3">
                                        <i class="far fa-bell-slash fa-2x text-muted mb-2" aria-hidden="true"></i>
                                        <p class="mb-0">No hay notificaciones nuevas</p>
                                    </div>`;
                            }
                            window.notificationsLoaded = true;
                        },
                        error: function(xhr) {
                            console.error('Error al cargar notificaciones:', xhr);
                            const container = document.getElementById('notifications-container');
                            container.innerHTML = `
                                <div class="dropdown-item text-center py-3 text-danger">
                                    <i class="fas fa-exclamation-triangle fa-2x mb-2" aria-hidden="true"></i>
                                    <p class="mb-0">Error al cargar notificaciones</p>
                                </div>`;
                        }
                    });
                });
                
                // Marcar notificaciones como leídas al abrir el menú
                notificationsMenu.addEventListener('show.bs.dropdown', function() {
                    if (window.App.unreadCount > 0) {
                        $.post(window.App.adminUrl + '/ajax/mark-notifications-read.php', function() {
                            // Actualizar contador
                            const badge = document.querySelector('.notifications-menu .navbar-badge');
                            if (badge) {
                                badge.remove();
                            }
                            const srBadge = document.querySelector('.notifications-menu .sr-badge');
                            if (srBadge) {
                                srBadge.remove();
                            }
                            window.App.unreadCount = 0;
                        });
                    }
                });
            });
            </script>
            
            <!-- User Menu -->
            <li class="nav-item dropdown user-menu">
                <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">
                    <img src="<?php echo !empty($currentUser['avatar']) ? UPLOADS_URL . '/' . $currentUser['avatar'] : ASSETS_URL . '/img/default-avatar.png'; ?>" 
                         class="user-image img-circle elevation-2" alt="User Image">
                    <span class="d-none d-md-inline"><?php echo htmlspecialchars($currentUser['name'] ?? 'Usuario'); ?></span>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <!-- User image -->
                    <div class="user-header bg-primary">
                        <img src="<?php echo !empty($currentUser['avatar']) ? UPLOADS_URL . '/' . $currentUser['avatar'] : ASSETS_URL . '/img/default-avatar.png'; ?>" 
                             class="img-circle elevation-2" alt="User Image">
                        <p>
                            <?php echo htmlspecialchars($currentUser['name'] ?? 'Usuario'); ?>
                            <small>Miembro desde <?php echo date('M. Y', strtotime($currentUser['created_at'] ?? 'now')); ?></small>
                        </p>
                    </div>
                    <!-- Menu Footer-->
                    <div class="user-footer">
                        <a href="<?php echo ADMIN_URL; ?>/profile.php" class="btn btn-default btn-flat">Perfil</a>
                        <a href="<?php echo ADMIN_URL; ?>/logout.php" class="btn btn-default btn-flat float-right">Salir</a>
                    </div>
                </div>
            </li>
            
            <li class="nav-item">
                <a class="nav-link" data-widget="fullscreen" href="#" role="button">
                    <i class="fas fa-expand-arrows-alt"></i>
                </a>
            </li>
        </ul>
    </nav>
    <!-- /.navbar -->

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <!-- Brand Logo -->
        <a href="<?php echo ADMIN_URL; ?>" class="brand-link">
            <img src="<?php echo ASSETS_URL; ?>/img/logo-white.png" alt="Logo" class="brand-image img-circle elevation-3">
            <span class="brand-text font-weight-light"><?php echo SITE_NAME; ?></span>
        </a>

        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Sidebar user panel -->
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="image">
                    <img src="<?php echo !empty($currentUser['avatar']) ? UPLOADS_URL . '/' . $currentUser['avatar'] : ASSETS_URL . '/img/default-avatar.png'; ?>" 
                         class="img-circle elevation-2" alt="User Image">
                </div>
                <div class="info">
                    <a href="<?php echo ADMIN_URL; ?>/profile.php" class="d-block">
                        <?php echo htmlspecialchars($currentUser['name'] ?? 'Usuario'); ?>
                    </a>
                    <small><?php echo ucfirst($currentUser['role'] ?? 'usuario'); ?></small>
                </div>
            </div>

            <!-- Sidebar Menu -->
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                    <!-- Dashboard -->
                    <li class="nav-item">
                        <a href="<?php echo ADMIN_URL; ?>" class="nav-link <?php echo isRouteActive('/admin/') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>
                    
                    <!-- Artículos -->
                    <li class="nav-item <?php echo isRouteActive('/admin/articles.php') ? 'menu-open' : ''; ?>">
                        <a href="#" class="nav-link <?php echo isRouteActive('/admin/articles.php') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-newspaper"></i>
                            <p>Artículos<i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?php echo ADMIN_URL; ?>/articles.php" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Todos los artículos</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo ADMIN_URL; ?>/articles.php?action=add" class="nav-link">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Añadir nuevo</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <!-- Páginas -->
                    <li class="nav-item">
                        <a href="<?php echo ADMIN_URL; ?>/pages.php" class="nav-link <?php echo isRouteActive('/admin/pages.php') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-file-alt"></i>
                            <p>Páginas</p>
                        </a>
                    </li>
                    
                    <!-- Medios -->
                    <li class="nav-item">
                        <a href="<?php echo ADMIN_URL; ?>/media.php" class="nav-link <?php echo isRouteActive('/admin/media.php') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-images"></i>
                            <p>Medios</p>
                        </a>
                    </li>
                    
                    <!-- Comentarios -->
                    <li class="nav-item">
                        <a href="<?php echo ADMIN_URL; ?>/comments.php" class="nav-link <?php echo isRouteActive('/admin/comments.php') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-comments"></i>
                            <p>Comentarios</p>
                            <?php if ($unreadCount > 0): ?>
                                <span class="badge badge-warning right"><?php echo $unreadCount; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    
                    <!-- Usuarios -->
                    <li class="nav-item">
                        <a href="<?php echo ADMIN_URL; ?>/users.php" class="nav-link <?php echo isRouteActive('/admin/users.php') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-users"></i>
                            <p>Usuarios</p>
                        </a>
                    </li>
                    
                    <!-- Ajustes -->
                    <li class="nav-item">
                        <a href="<?php echo ADMIN_URL; ?>/settings.php" class="nav-link <?php echo isRouteActive('/admin/settings.php') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-cog"></i>
                            <p>Ajustes</p>
                        </a>
                    </li>
                    
                    <!-- Cerrar sesión -->
                    <li class="nav-item">
                        <a href="<?php echo ADMIN_URL; ?>/logout.php" class="nav-link text-danger">
                            <i class="nav-icon fas fa-sign-out-alt"></i>
                            <p>Cerrar sesión</p>
                        </a>
                    </li>
                </ul>
            </nav>
            <!-- /.sidebar-menu -->
        </div>
        <!-- /.sidebar -->
    </aside>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0"><?php echo $pageTitle ?? 'Panel de Control'; ?></h1>
                    </div><!-- /.col -->
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="<?php echo ADMIN_URL; ?>">Inicio</a></li>
                            <?php if (isset($breadcrumbs)): ?>
                                <?php foreach ($breadcrumbs as $title => $url): ?>
                                    <?php if ($url): ?>
                                        <li class="breadcrumb-item"><a href="<?php echo $url; ?>"><?php echo $title; ?></a></li>
                                    <?php else: ?>
                                        <li class="breadcrumb-item active"><?php echo $title; ?></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ol>
                    </div><!-- /.col -->
                </div><!-- /.row -->
            </div><!-- /.container-fluid -->
        </div>
        <!-- /.content-header -->

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <?php displayFlashMessages(); ?>
                <!-- El contenido principal se insertará aquí -->
            </div>
        </section>
        <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->
</div>
<!-- ./wrapper -->
<?php endif; ?>