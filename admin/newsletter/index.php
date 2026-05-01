<?php
// Habilitar visualización de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/newsletter_errors.log');

// Iniciar la sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    // Configuración de cookies de sesión seguras
    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    session_start();
}

// Actualizar el tiempo de última actividad
$_SESSION['last_activity'] = time();

// Incluir configuración de la base de datos
require_once __DIR__ . '/../config.php';

// Verificar autenticación y permisos
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'admin') {
    // Registrar intento de acceso no autorizado
    error_log("Acceso denegado a newsletter - Usuario: " . 
             ($_SESSION['usuario_id'] ?? 'no autenticado') . 
             ", Rol: " . ($_SESSION['usuario_rol'] ?? 'no definido'));
    
    // Redirigir al login si no está autenticado
    if (!isset($_SESSION['usuario_id'])) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: /admin/login.php');
        exit();
    }
    
    // Mostrar error de permiso denegado
    header('HTTP/1.1 403 Forbidden');
    die("<h1>Acceso denegado</h1><p>No tienes permisos para acceder a esta sección. Por favor, inicia sesión como administrador.</p>");
}

// Verificar el tiempo de inactividad (30 minutos)
$inactive = 1800;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $inactive)) {
    // Registrar cierre de sesión por inactividad
    error_log("Sesión cerrada por inactividad - Usuario: " . $_SESSION['usuario_id']);
    
    // Destruir la sesión
    session_unset();
    session_destroy();
    
    // Redirigir al login
    header('Location: /admin/login.php?inactivo=1');
    exit();
}

// Configuración de paginación
$por_pagina = 20;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$inicio = ($pagina > 1) ? ($pagina * $por_pagina - $por_pagina) : 0;

// Solo ordenación por fecha
$whereClause = '1=1';

try {
    // Obtener total de registros
    $sqlCount = "SELECT COUNT(*) as total FROM newsletter WHERE $whereClause";
    $stmt = $pdo->prepare($sqlCount);
    $stmt->execute();
    $total_registros = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_paginas = ($por_pagina > 0) ? ceil($total_registros / $por_pagina) : 1;

    // Asegurar que la página actual sea válida
    if ($total_paginas > 0 && $pagina > $total_paginas) {
        header("Location: ?pagina=" . $total_paginas . "&" . http_build_query($_GET));
        exit();
    }

    // Obtener registros para la página actual
    $sql = "SELECT * FROM newsletter WHERE $whereClause ORDER BY fecha_registro DESC LIMIT :inicio, :por_pagina";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':inicio', (int)$inicio, PDO::PARAM_INT);
    $stmt->bindValue(':por_pagina', (int)$por_pagina, PDO::PARAM_INT);
    $stmt->execute();
    $suscriptores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Registrar el error
    error_log("Error en la consulta SQL: " . $e->getMessage());
    error_log("Consulta SQL: " . ($sql ?? 'No se pudo determinar la consulta'));
    
    // Mostrar un mensaje de error genérico al usuario
    die("<div class='alert alert-danger'>Ha ocurrido un error al cargar los suscriptores. Por favor, inténtalo de nuevo más tarde.</div>");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Suscriptores - Panel de Administración</title>
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            min-height: 100vh;
            background-color: #1c0d34 !important; /* Haiti color with !important */
            background: #1c0d34 !important; /* Fallback with !important */
            color: white;
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.75rem 1rem;
            margin: 0.2rem 0;
            border-radius: 0.35rem;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover, 
        .sidebar .nav-link.active {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        .sidebar .nav-link i {
            margin-right: 0.5rem;
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            padding: 1.5rem;
            background-color: #f8f9fc;
        }
        
        .card {
            border: none;
            border-radius: 0.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-bottom: 1.5rem;
            transition: transform 0.2s ease-in-out;
        }
        
        .card:hover {
            transform: translateY(-2px);
        }
        
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #e3e6f0;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.25rem;
        }
        
        .card-body {
            padding: 1.25rem;
        }
        
        .table {
            margin-bottom: 0;
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
        
        .pagination .page-link {
            color: var(--primary-color);
        }
        
        .pagination .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .search-form {
            max-width: 500px;
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
                            <a class="nav-link" href="/admin/">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/mensajes/simple_messages.php">
                                <i class="bi bi-envelope"></i> Mensajes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="/admin/newsletter/">
                                <i class="bi bi-people"></i> Suscriptores
                            </a>
                        </li>
                        <li class="nav-item mt-4">
                            <a class="nav-link" href="/admin/logout.php">
                                <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <!-- Header -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4 border-bottom">
                    <h1 class="h3 mb-0 text-gray-800">Gestión de Suscriptores</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <form action="exportar.php" method="post" id="exportForm" style="display: inline-block;">
                                <?php if (isset($busqueda) && $busqueda !== ''): ?>
                                    <input type="hidden" name="busqueda" value="<?php echo htmlspecialchars($busqueda); ?>">
                                <?php endif; ?>
                                <button type="submit" class="btn btn-sm btn-primary">
                                    <i class="bi bi-download me-1"></i> Exportar CSV
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Tabla de suscriptores -->
                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Suscriptores</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($total_registros); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-people fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros eliminados, solo se mantiene la ordenación por fecha -->

                <!-- Subscribers Table -->
                <div class="card shadow">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Lista de Suscriptores</h6>
                        <span class="badge bg-primary"><?php echo number_format($total_registros); ?> registros</span>
                    </div>
                    <div class="card-body p-0">
                        <?php if (count($suscriptores) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Email</th>
                                            <th>Nombre</th>
                                            <th class="text-center">Estado</th>
                                            <th>Fecha de Registro</th>
                                            <th class="text-end">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($suscriptores as $suscriptor): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($suscriptor['id']); ?></td>
                                                <td><?php echo htmlspecialchars($suscriptor['email']); ?></td>
                                                <td>
                                                    <?php 
                                                    $nombre = trim(($suscriptor['nombre'] ?? '') . ' ' . ($suscriptor['apellido'] ?? ''));
                                                    echo !empty($nombre) ? htmlspecialchars($nombre) : '<span class="text-muted">No especificado</span>';
                                                    ?>
                                                </td>
                                                <td class="text-center">
                                                    <a href="#" class="toggle-status" data-id="<?php echo $suscriptor['id']; ?>" style="text-decoration: none;">
                                                        <span class="badge bg-<?php echo ($suscriptor['activo'] ?? 1) ? 'success' : 'secondary'; ?> text-uppercase" style="font-size: 0.7em; letter-spacing: 0.5px; cursor: pointer;">
                                                            <i class="bi bi-<?php echo ($suscriptor['activo'] ?? 1) ? 'check' : 'x'; ?>-circle me-1"></i>
                                                            <?php echo ($suscriptor['activo'] ?? 1) ? 'Activo' : 'Inactivo'; ?>
                                                        </span>
                                                    </a>
                                                </td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($suscriptor['fecha_registro'])); ?></td>

                                                <td class="text-end">
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-danger btn-eliminar" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#confirmarEliminarModal" 
                                                            data-id="<?php echo $suscriptor['id']; ?>" 
                                                            title="Eliminar suscriptor">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="card-footer bg-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="text-muted small">
                                        Mostrando <span class="fw-bold"><?php echo $inicio + 1; ?></span> a <span class="fw-bold"><?php echo min($inicio + $por_pagina, $total_registros); ?></span> de <span class="fw-bold"><?php echo number_format($total_registros); ?></span> registros
                                    </div>
                                    <nav aria-label="Paginación">
                                        <ul class="pagination pagination-sm mb-0">
                                            <?php if ($pagina > 1): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?pagina=1<?php echo $query_params; ?>" aria-label="Primera">
                                                        <span aria-hidden="true">&laquo;&laquo;</span>
                                                    </a>
                                                </li>
                                                <li class="page-item">
                                                    <a class="page-link" href="?pagina=<?php echo ($pagina - 1) . $query_params; ?>" aria-label="Anterior">
                                                        <span aria-hidden="true">&laquo;</span>
                                                    </a>
                                                </li>
                                            <?php endif; ?>

                                            <?php
                                            $inicio_pag = max(1, $pagina - 1);
                                            $fin_pag = min($total_paginas, $pagina + 1);
                                            
                                            if ($inicio_pag > 1) {
                                                echo '<li class="page-item"><a class="page-link" href="?pagina=1' . $query_params . '">1</a></li>';
                                                if ($inicio_pag > 2) {
                                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                                }
                                            }
                                            
                                            for ($i = $inicio_pag; $i <= $fin_pag; $i++):
                                                $active = $i == $pagina ? ' active' : '';
                                                echo "<li class=\"page-item$active\"><a class=\"page-link\" href=\"?pagina=$i$query_params\">$i</a></li>";
                                            endfor;
                                            
                                            if ($fin_pag < $total_paginas) {
                                                if ($fin_pag < $total_paginas - 1) {
                                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                                }
                                                echo '<li class="page-item"><a class="page-link" href="?pagina=' . $total_paginas . $query_params . '">' . $total_paginas . '</a></li>';
                                            }
                                            ?>

                                            <?php if ($pagina < $total_paginas): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?pagina=<?php echo ($pagina + 1) . $query_params; ?>" aria-label="Siguiente">
                                                        <span aria-hidden="true">&raquo;</span>
                                                    </a>
                                                </li>
                                                <li class="page-item">
                                                    <a class="page-link" href="?pagina=<?php echo $total_paginas . $query_params; ?>" aria-label="Última">
                                                        <span aria-hidden="true">&raquo;&raquo;</span>
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal de confirmación de eliminación -->
    <div class="modal fade" id="confirmarEliminarModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    ¿Estás seguro de que deseas eliminar este suscriptor? Esta acción no se puede deshacer.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <a href="#" id="confirmarEliminarBtn" class="btn btn-danger">Eliminar</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Variable para almacenar el ID del suscriptor a eliminar
        let suscriptorAEliminarId = null;
        let deleteButton = null;
        
        // Función para mostrar el modal de confirmación
        function mostrarModalEliminar(button) {
            deleteButton = button;
            suscriptorAEliminarId = button.getAttribute('data-id');
            const modal = new bootstrap.Modal(document.getElementById('confirmarEliminarModal'));
            modal.show();
        }
        
        // Configurar el botón de confirmación
        document.getElementById('confirmarEliminarBtn').addEventListener('click', function() {
            if (!suscriptorAEliminarId) return;
            
            const row = deleteButton.closest('tr');
            const deleteBtn = deleteButton;
            
            // Mostrar indicador de carga
            deleteBtn.disabled = true;
            deleteBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
            
            // Enviar petición AJAX para eliminar
            $.ajax({
                url: 'eliminar.php',
                type: 'POST',
                data: { id: suscriptorAEliminarId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Eliminar la fila de la tabla
                        row.style.opacity = '0.5';
                        setTimeout(() => {
                            row.remove();
                            // Actualizar contador de registros
                            const totalElement = document.querySelector('.table tbody');
                            if (totalElement && totalElement.children.length === 0) {
                                window.location.reload();
                            }
                        }, 300);
                    } else {
                        alert('Error al eliminar el suscriptor: ' + (response.error || 'Error desconocido'));
                        deleteBtn.disabled = false;
                        deleteBtn.innerHTML = '<i class="bi bi-trash"></i>';
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error en la petición AJAX:', error);
                    alert('Error al conectar con el servidor. Por favor, inténtalo de nuevo.');
                    deleteBtn.disabled = false;
                    deleteBtn.innerHTML = '<i class="bi bi-trash"></i>';
                }
            });
            
            // Cerrar el modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('confirmarEliminarModal'));
            modal.hide();
        });
        
        // Event delegation para los botones de eliminar
        document.addEventListener('click', function(e) {
            // Manejar clic en botón de eliminar
            const button = e.target.closest('.btn-eliminar');
            if (button) {
                e.preventDefault();
                mostrarModalEliminar(button);
            }
            
            // Manejar clic en toggle de estado
            if (e.target.closest('.toggle-status') || e.target.closest('.badge')) {
                e.preventDefault();
                const link = e.target.closest('.toggle-status') || e.target.closest('.badge').closest('.toggle-status');
                const id = link.getAttribute('data-id');
                const badge = link.querySelector('.badge');
                
                // Mostrar indicador de carga
                const originalHtml = badge.innerHTML;
                badge.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
                
                // Hacer la petición AJAX
                fetch(`toggle_status.php?id=${id}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Error en la respuesta del servidor');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data && data.success) {
                            // Actualizar la interfaz
                            const isActive = data.activo === true || data.activo === 1 || data.activo === '1';
                            const estadoText = isActive ? 'Activo' : 'Inactivo';
                            const iconClass = isActive ? 'check' : 'x';
                            
                            badge.className = `badge bg-${isActive ? 'success' : 'secondary'} text-uppercase`;
                            badge.style.fontSize = '0.7em';
                            badge.style.letterSpacing = '0.5px';
                            badge.style.cursor = 'pointer';
                            badge.innerHTML = `<i class="bi bi-${iconClass}-circle me-1"></i>${estadoText}`;
                            
                            // Actualizar el atributo title si existe
                            if (link.title) {
                                link.title = `Cambiar a ${data.estado === 'activo' ? 'Inactivo' : 'Activo'}`;
                            }
                        } else {
                            throw new Error(data?.error || 'Error desconocido al actualizar el estado');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error: ' + (error.message || 'No se pudo actualizar el estado'));
                        badge.innerHTML = originalHtml; // Restaurar el contenido original en caso de error
                    });
                    });
            }
        });
        
        // Mostrar notificaciones
        <?php if (isset($_GET['exito'])): ?>
            alert('Operación realizada con éxito');
            // Eliminar parámetro de la URL sin recargar la página
            if (window.history.replaceState) {
                const url = new URL(window.location.href);
                url.searchParams.delete('exito');
                window.history.replaceState({}, '', url);
            }
        <?php endif; ?>
        
        // Función para exportar a CSV con los filtros actuales
    document.getElementById('exportarCSV').addEventListener('click', function(e) {
        e.preventDefault();
        
        // Construir la URL con los parámetros actuales
        let url = 'exportar.php';
        const params = new URLSearchParams();
        
        if ('<?= $busqueda ?>') params.append('buscar', '<?= $busqueda ?>');
        if ('<?= $filtro_estado ?>' !== 'todos') params.append('estado', '<?= $filtro_estado ?>');
        
        if (params.toString()) {
            url += '?' + params.toString();
        }
        
        // Redirigir a la URL de exportación
        window.location.href = url;
    });
    
    // Función para seleccionar/deseleccionar todos los checkboxes
    const selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('input[type="checkbox"][name="suscriptores[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }
    
    // Función para manejar acciones por lotes
    const accionLote = document.getElementById('accionLote');
    if (accionLote) {
        accionLote.addEventListener('change', function() {
            const accion = this.value;
            if (accion) {
                if (confirm(`¿Estás seguro de querer ${this.options[this.selectedIndex].text.toLowerCase()} los suscriptores seleccionados?`)) {
                    document.getElementById('formAccionesLote').submit();
                } else {
                    this.value = '';
                }
            }
        });
    }
    
    // Inicializar tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Configuración del modal de confirmación de eliminación
    var confirmarEliminarModal = document.getElementById('confirmarEliminarModal');
    if (confirmarEliminarModal) {
        var modal = new bootstrap.Modal(confirmarEliminarModal);
        var btnEliminar = document.getElementById('btnEliminar');
        
        // Función para manejar el clic en el botón de eliminar
        window.confirmarEliminacion = function(id) {
            btnEliminar.href = `eliminar.php?id=${id}`;
            modal.show();
        }
    }
    
    // Mostrar notificaciones
    <?php 
    if (isset($_GET['exito'])) {
        echo "alert('Operación realizada con éxito');
        // Eliminar parámetro de la URL sin recargar la página
        if (window.history.replaceState) {
            const url = new URL(window.location.href);
            url.searchParams.delete('exito');
            window.history.replaceState({}, '', url);
        }";
    }
    ?>
    </script>
<?php
// Definir constantes
define('ROOT_DIR', dirname(__DIR__));
define('APP_DIR', ROOT_DIR . '/app');
define('VIEWS_DIR', APP_DIR . '/views');
define('MODELS_DIR', APP_DIR . '/models');
define('CONTROLLERS_DIR', APP_DIR . '/controllers');

// Incluir el footer
include '../includes/footer.php';
?>
</body>
</html>
