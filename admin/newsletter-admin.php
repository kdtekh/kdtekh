<?php
// Habilitar la visualización de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluir configuración común
require_once 'includes/config_common.php';

// Configuración específica de la página
$current_page = 'suscriptores'; // Para resaltar el ítem activo en el menú lateral
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Suscriptores - KDTekh</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/admin.min.css">
    <link rel="stylesheet" href="css/sortable.min.css">
    <style id="sortable-styles">
        /* Los estilos se cargarán dinámicamente */
    </style>
    <style>
        /* Estilos específicos para la página de suscriptores */
        .card {
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 0.5rem 1rem rgba(94, 67, 159, 0.1);
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
            border-top: 4px solid #5e439f;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 1rem 2rem rgba(94, 67, 159, 0.15);
        }


        .card-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #5e439f;
            width: 70px;
            height: 70px;
            line-height: 70px;
            background-color: rgba(94, 67, 159, 0.1);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }
        
        /* Ajustes para el contenido principal */
        .main-content {
            padding: 1.5rem 1.5rem;
            margin-left: 0;
            width: 100%;
        }
        
        /* Ajustes para el header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 0;
        }
        
        /* Ajustes para los botones de acción */
        .btn-action {
            margin-right: 0.5rem;
        }
        
        /* Asegurar que la tabla sea responsive */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        /* Estilos para el menú lateral en móviles */
        @media (max-width: 991.98px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: -250px;
                z-index: 1050;
                height: 100vh;
                transition: all 0.3s;
                overflow-y: auto;
            }
            
            .sidebar.show {
                left: 0;
                box-shadow: 4px 0 10px rgba(0, 0, 0, 0.1);
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            body.sidebar-toggled {
                overflow: hidden;
            }
            
            body.sidebar-toggled::after {
                content: '';
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 1040;
                opacity: 0;
                transition: opacity 0.3s;
                pointer-events: none;
            }
            
            body.sidebar-toggled.sidebar-show::after {
                opacity: 1;
                pointer-events: auto;
            }
        }
        
        /* Ajustes para pantallas grandes */
        @media (min-width: 992px) {
            .main-content {
                margin-left: 250px;
                width: calc(100% - 250px);
            }
            
            .sidebar-toggle {
                display: none;
            }
        }
        
        /* Mejoras en la tabla */
        .table th {
            white-space: nowrap;
            border-top: none;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6c757d;
        }
        
        .table td {
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <div class="container-fluid p-0">
        <div class="row g-0">
            <?php 
            // Incluir el menú lateral
            $current_page = 'suscriptores';
            include 'includes/sidebar.php';
            ?>

            <main class="main-content">
                <!-- Header -->
                <div class="header px-4 pt-4">
                    <div>
                        <button class="btn btn-link text-dark p-0 me-3 d-inline-block d-lg-none" id="sidebarToggle">
                            <i class="fas fa-bars fa-lg"></i>
                        </button>
                        <h1 class="h3 mb-0">Panel de Suscriptores</h1>
                    </div>
                    <div class="user-menu d-flex align-items-center">
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary dropdown-toggle d-flex align-items-center" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="me-2 d-none d-sm-inline"><?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Admin'); ?></span>
                                <i class="fas fa-user-circle"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i>Perfil</a></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Configuración</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Cerrar sesión</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Contenido principal -->
                <div class="container-fluid p-4">

                <!-- Stats Grid -->
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="card-subtitle text-muted">Total Suscriptores</h6>
                                    <div class="bg-primary bg-opacity-10 p-2 rounded">
                                        <i class="fas fa-users text-primary"></i>
                                    </div>
                                </div>
                                <h3 class="card-title mb-1" id="total-subscribers">Cargando...</h3>
                                <p class="text-muted mb-0" id="total-trend">
                                    <i class="fas fa-sync fa-spin"></i> Actualizando datos...
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="card-subtitle text-muted">Nuevos hoy</h6>
                                    <div class="bg-success bg-opacity-10 p-2 rounded">
                                        <i class="fas fa-user-plus text-success"></i>
                                    </div>
                                </div>
                                <h3 class="card-title mb-1" id="monthly-subscribers">--</h3>
                                <p class="text-muted mb-0" id="monthly-trend">
                                    <span class="text-success"><i class="fas fa-arrow-up"></i> 0%</span> respecto al mes pasado
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="card-subtitle text-muted">Tasa de crecimiento</h6>
                                    <div class="bg-warning bg-opacity-10 p-2 rounded">
                                        <i class="fas fa-chart-line text-warning"></i>
                                    </div>
                                </div>
                                <h3 class="card-title mb-1" id="growth-rate">--%</h3>
                                <p class="text-muted mb-0">
                                    <span id="growth-trend">Cargando...</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Subscribers Table -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-0 py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Lista de Suscriptores</h5>
                            <div class="input-group" style="max-width: 300px;">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="text" id="searchInput" class="form-control border-start-0" placeholder="Buscar suscriptor...">
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="border-0 sortable" data-sort="email">
                                            Email
                                            <i class="fas fa-sort ms-1"></i>
                                        </th>
                                        <th class="border-0 sortable" data-sort="status">
                                            Estado
                                            <i class="fas fa-sort ms-1"></i>
                                        </th>
                                        <th class="border-0 sortable" data-sort="date">
                                            Fecha de Suscripción
                                            <i class="fas fa-sort ms-1"></i>
                                        </th>
                                        <th class="border-0 text-end">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="subscribersTableBody">
                                    <tr>
                                        <td colspan="4" class="text-center py-4">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Cargando...</span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-0 py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-muted small">
                                Mostrando <span id="showingFrom">0</span> a <span id="showingTo">0</span> de <span id="totalSubscribers">0</span> suscriptores
                            </div>
                            <nav aria-label="Page navigation">
                                <ul class="pagination pagination-sm mb-0" id="pagination">
                                    <!-- Pagination will be generated by JavaScript -->
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>

                <!-- Newsletter Form -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0">Enviar Newsletter</h5>
                    </div>
                    <div class="card-body">
                        <form id="newsletterForm">
                            <div class="mb-3">
                                <label for="newsletterSubject" class="form-label">Asunto</label>
                                <input type="text" class="form-control" id="newsletterSubject" placeholder="Asunto del correo" required>
                            </div>
                            <div class="mb-3">
                                <label for="newsletterContent" class="form-label">Contenido</label>
                                <textarea class="form-control" id="newsletterContent" rows="8" placeholder="Escribe aquí el contenido de tu newsletter..." required></textarea>
                                <div class="form-text">Puedes usar etiquetas HTML para dar formato a tu mensaje.</div>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary" id="sendNewsletterBtn">
                                    <i class="far fa-paper-plane me-1"></i> Enviar a todos los suscriptores
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                    </div> <!-- Cierre de main-content -->
                </div> <!-- Cierre de card -->
                </div> <!-- Cierre de container-fluid -->
            </main>
        </div> <!-- Cierre de row -->
    </div> <!-- Cierre de container-fluid -->

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/newsletter-admin.min.js"></script>
    
    <!-- Script para el menú desplegable en móviles -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.querySelector('.sidebar');
        
        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', function(e) {
                e.preventDefault();
                sidebar.classList.toggle('show');
                document.body.classList.toggle('sidebar-toggled');
            });
        }
        
        // Cerrar el menú al hacer clic en un enlace en dispositivos móviles
        const navLinks = document.querySelectorAll('.sidebar .nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 992) {
                    sidebar.classList.remove('show');
                    document.body.classList.remove('sidebar-toggled');
                }
            });
        });
    });
    </script>
</body>
</html>
