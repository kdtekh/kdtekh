<?php
// Iniciar el buffer de salida
ob_start();

// Incluir archivos necesarios
require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/config.php';

// Verificar si el usuario está autenticado
if (!isAuthenticated() && basename($_SERVER['PHP_SELF']) !== 'login.php') {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: ' . ADMIN_URL . '/login.php');
    exit();
}

// Obtener información del usuario actual
$usuario_actual = [
    'id' => $_SESSION['usuario_id'] ?? 0,
    'nombre' => $_SESSION['usuario_nombre'] ?? 'Invitado',
    'email' => $_SESSION['usuario_email'] ?? '',
    'rol' => $_SESSION['usuario_rol'] ?? 'invitado',
    'avatar' => $_SESSION['usuario_avatar'] ?? 'default-avatar.png'
];

// Obtener el título de la página actual
$page_title = $page_title ?? 'Panel de Administración';

// Obtener mensajes flash
$flash_message = '';
$flash_type = '';

if (isset($_SESSION['flash_message'])) {
    $flash_message = $_SESSION['flash_message']['message'] ?? '';
    $flash_type = $_SESSION['flash_message']['type'] ?? 'info';
    unset($_SESSION['flash_message']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - <?php echo APP_NAME; ?></title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="<?php echo ADMIN_URL; ?>/assets/img/favicon.ico">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    
    <!-- Toastr CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo ADMIN_URL; ?>/assets/css/admin.min.css">
    
    <style>
        :root {
            --primary-color: #4a6cf7;
            --sidebar-width: 250px;
        }
        
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f5f7fb;
        }
        
        .sidebar {
            position: fixed;
            width: var(--sidebar-width);
            height: 100vh;
            background: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }
        
        .header {
            height: 60px;
            background: #fff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .card {
            border: none;
            border-radius: 0.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header p-3 border-bottom">
            <a href="<?php echo ADMIN_URL; ?>" class="d-flex align-items-center text-decoration-none">
                <img src="<?php echo ADMIN_URL; ?>/assets/img/logo.png" alt="Logo" height="30" class="me-2">
                <span class="fs-5 fw-bold"><?php echo APP_NAME; ?></span>
            </a>
        </div>
        
        <div class="sidebar-menu p-3">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a href="<?php echo ADMIN_URL; ?>" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                </li>
                
                <li class="nav-item mt-2">
                    <div class="nav-link fw-bold text-uppercase small text-muted">Contenido</div>
                </li>
                
                <li class="nav-item">
                    <a href="<?php echo ADMIN_URL; ?>/articulos/" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/articulos/') !== false ? 'active' : ''; ?>">
                        <i class="fas fa-newspaper me-2"></i> Artículos
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="<?php echo ADMIN_URL; ?>/categorias/" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/categorias/') !== false ? 'active' : ''; ?>">
                        <i class="fas fa-tags me-2"></i> Categorías
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="<?php echo ADMIN_URL; ?>/medios/" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/medios/') !== false ? 'active' : ''; ?>">
                        <i class="fas fa-images me-2"></i> Medios
                    </a>
                </li>
                
                <li class="nav-item mt-2">
                    <div class="nav-link fw-bold text-uppercase small text-muted">Comunicación</div>
                </li>
                
                <li class="nav-item">
                    <a href="<?php echo ADMIN_URL; ?>/comentarios/" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/comentarios/') !== false ? 'active' : ''; ?>">
                        <i class="fas fa-comments me-2"></i> Comentarios
                        <span class="badge bg-danger rounded-pill float-end">3</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="<?php echo ADMIN_URL; ?>/contacto/" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/contacto/') !== false ? 'active' : ''; ?>">
                        <i class="fas fa-envelope me-2"></i> Mensajes
                        <?php if ($mensajes_no_leidos > 0): ?>
                            <span class="badge bg-danger rounded-pill float-end"><?php echo $mensajes_no_leidos; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="<?php echo ADMIN_URL; ?>/newsletter/" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/newsletter/') !== false ? 'active' : ''; ?>">
                        <i class="fas fa-paper-plane me-2"></i> Newsletter
                    </a>
                </li>
                
                <?php if (isAdmin()): ?>
                <li class="nav-item mt-2">
                    <div class="nav-link fw-bold text-uppercase small text-muted">Administración</div>
                </li>
                
                <li class="nav-item">
                    <a href="<?php echo ADMIN_URL; ?>/usuarios/" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/usuarios/') !== false ? 'active' : ''; ?>">
                        <i class="fas fa-users me-2"></i> Usuarios
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="<?php echo ADMIN_URL; ?>/configuracion/" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/configuracion/') !== false ? 'active' : ''; ?>">
                        <i class="fas fa-cog me-2"></i> Configuración
                    </a>
                </li>
                <?php endif; ?>
                
                <li class="nav-item mt-auto pt-3 border-top">
                    <a href="<?php echo SITE_URL; ?>" target="_blank" class="nav-link text-primary">
                        <i class="fas fa-external-link-alt me-2"></i> Ver Sitio Web
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <header class="header sticky-top d-flex align-items-center">
            <div class="d-flex align-items-center">
                <button class="btn btn-link text-dark d-md-none me-2" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="h5 mb-0 d-none d-md-block"><?php echo $page_title; ?></h1>
            </div>
            
            <div class="ms-auto d-flex align-items-center">
                <!-- Notificaciones -->
                <div class="dropdown me-3">
                    <button class="btn btn-link text-dark position-relative" type="button" id="notificationsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            3
                            <span class="visually-hidden">Notificaciones no leídas</span>
                        </span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end dropdown-menu-lg-start" aria-labelledby="notificationsDropdown">
                        <div class="dropdown-header">
                            <h6 class="mb-0">Notificaciones</h6>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a href="#" class="dropdown-item d-flex align-items-center">
                            <div class="me-3">
                                <div class="bg-primary bg-opacity-10 text-primary p-2 rounded">
                                    <i class="fas fa-comment"></i>
                                </div>
                            </div>
                            <div>
                                <div class="small text-muted">Hace 5 minutos</div>
                                <span class="fw-bold">Nuevo comentario</span> en "Artículo de ejemplo"
                            </div>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="#" class="dropdown-item text-center text-primary small">Ver todas las notificaciones</a>
                    </div>
                </div>
                
                <!-- Usuario -->
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="<?php echo ADMIN_URL; ?>/assets/img/avatars/<?php echo $usuario_actual['avatar']; ?>" alt="" width="32" height="32" class="rounded-circle me-2">
                        <span class="d-none d-md-inline"><?php echo htmlspecialchars($usuario_actual['nombre']); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="<?php echo ADMIN_URL; ?>/perfil/"><i class="fas fa-user me-2"></i> Perfil</a></li>
                        <li><a class="dropdown-item" href="<?php echo ADMIN_URL; ?>/configuracion/"><i class="fas fa-cog me-2"></i> Configuración</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?php echo ADMIN_URL; ?>/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Cerrar sesión</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Contenido principal -->
        <main class="p-4">
            <?php if ($flash_message): ?>
                <div class="alert alert-<?php echo $flash_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $flash_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            <?php endif; ?>
