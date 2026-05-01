<?php
// Habilitar visualización de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticación
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

// Incluir configuración de la base de datos
require_once __DIR__ . '/config.php';

// Configuración específica de la página
$current_page = 'dashboard';
$error = null;

// Obtener estadísticas para el dashboard
try {
    // Contar mensajes de contacto
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM mensajes_contacto");
    $totalMensajes = $stmt->fetch()['total'];
    
    // Contar mensajes no leídos
    $stmt = $pdo->query("SELECT COUNT(*) as noLeidos FROM mensajes_contacto WHERE leido = 0");
    $mensajesNoLeidos = $stmt->fetch()['noLeidos'];
    
    // Obtener estadísticas de suscriptores
    $totalSuscriptores = 0;
    $nuevosSuscriptores = 0;
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM newsletter WHERE activo = 1");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalSuscriptores = (int)$result['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as nuevos FROM newsletter WHERE activo = 1 AND fecha_registro >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $nuevosSuscriptores = (int)$result['nuevos'];
    
    // Obtener los últimos mensajes
    $stmt = $pdo->query("SELECT * FROM mensajes_contacto ORDER BY fecha DESC LIMIT 5");
    $ultimosMensajes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Error al cargar las estadísticas: " . $e->getMessage();
    error_log($error);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - KDTekh</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #858796;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --light-color: #f8f9fc;
            --dark-color: #5a5c69;
        }
        
        body {
            font-size: 0.9rem;
            background-color: var(--light-color);
        }
        
        .sidebar {
            min-height: 100vh;
            background: #1c0d34; /* Cambiado a color Haiti */
            color: white;
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.75rem 1rem;
            margin: 0.2rem 0;
            border-radius: 0.35rem;
        }
        
        .sidebar .nav-link:hover, 
        .sidebar .nav-link.active {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        .sidebar .nav-link i {
            margin-right: 0.5rem;
        }
        
        .main-content {
            padding: 1.5rem;
        }
        
        .card {
            border: none;
            border-radius: 0.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            font-weight: 600;
        }
        
        .stat-card {
            border-left: 0.25rem solid var(--primary-color);
        }
        
        .stat-card.primary { border-left-color: var(--primary-color); }
        .stat-card.success { border-left-color: var(--success-color); }
        .stat-card.info { border-left-color: var(--info-color); }
        .stat-card.warning { border-left-color: var(--warning-color); }
        
        .stat-card .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .stat-card .stat-label {
            text-transform: uppercase;
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--secondary-color);
        }
        
        .table th {
            border-top: none;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            color: var(--secondary-color);
        }
        
        .badge {
            font-weight: 500;
            padding: 0.35em 0.65em;
        }
        
        .navbar {
            padding: 0.5rem 1rem;
            background: white;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h4>KDTekh</h4>
                        <p class="text-white-50 small mb-0">Panel de Administración</p>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="index.php">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="mensajes/simple_messages.php">
                                <i class="bi bi-envelope"></i> Mensajes
                                <?php if ($mensajesNoLeidos > 0): ?>
                                    <span class="badge bg-danger rounded-pill float-end"><?php echo $mensajesNoLeidos; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="newsletter/">
                                <i class="bi bi-people"></i> Suscriptores
                            </a>
                        </li>
                        <li class="nav-item mt-4">
                            <a class="nav-link" href="logout.php">
                                <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <!-- Header -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">Exportar</button>
                        </div>
                    </div>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <!-- Stats Cards -->
                <div class="row">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card primary h-100">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="stat-label">Mensajes Totales</div>
                                        <div class="stat-value"><?php echo $totalMensajes; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-envelope fs-1 text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card success h-100">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="stat-label">No Leídos</div>
                                        <div class="stat-value"><?php echo $mensajesNoLeidos; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-envelope-exclamation fs-1 text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card info h-100">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="stat-label">Suscriptores</div>
                                        <div class="stat-value"><?php echo $totalSuscriptores; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-people fs-1 text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card warning h-100">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="stat-label">Nuevos (7 días)</div>
                                        <div class="stat-value"><?php echo $nuevosSuscriptores; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-person-plus fs-1 text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Messages -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Mensajes Recientes</h5>
                        <a href="mensajes/simple_messages.php" class="btn btn-sm btn-outline-primary">Ver todos los mensajes</a>
                    </div>
                    <div class="card-body p-0">
                        <?php if (!empty($ultimosMensajes)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Nombre</th>
                                            <th>Email</th>
                                            <th>Asunto</th>
                                            <th>Fecha</th>
                                            <th class="d-none">Estado</th>
                                            <th class="d-none">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($ultimosMensajes as $mensaje): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($mensaje['nombre']); ?></td>
                                                <td><?php echo htmlspecialchars($mensaje['email']); ?></td>
                                                <td><?php echo htmlspecialchars($mensaje['asunto']); ?></td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($mensaje['fecha'])); ?></td>
                                                <td class="d-none">
                                                    <?php if ($mensaje['leido']): ?>
                                                        <span class="badge bg-success">Leído</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning text-dark">No leído</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="d-none">
                                                    <a href="mensajes/ver.php?id=<?php echo $mensaje['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-eye"></i> Ver
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center p-4">
                                <p class="text-muted mb-0">No hay mensajes recientes</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Activar tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Función para actualizar estadísticas
        function actualizarEstadisticas() {
            // Implementar lógica de actualización si es necesario
            console.log('Actualizando estadísticas...');
        }
        
        // Actualizar cada 5 minutos
        setInterval(actualizarEstadisticas, 300000);
    </script>
</body>
</html>
