<?php
// Incluir configuración común
require_once 'includes/config_common.php';

// Configuración específica de la página
$current_page = 'mensajes'; // Para resaltar el ítem activo en el menú lateral

// Manejar eliminación de mensajes
if (isset($_GET['eliminar']) && is_numeric($_GET['eliminar'])) {
    try {
        $id = (int)$_GET['eliminar'];
        $stmt = $pdo->prepare("DELETE FROM mensajes_contacto WHERE id = ?");
        $stmt->execute([$id]);
        
        // Redirigir a la misma página sin el parámetro de eliminación
        $params = $_GET;
        unset($params['eliminar']);
        $query_string = http_build_query($params);
        header("Location: contacto-admin.php" . (!empty($query_string) ? '?' . $query_string : ''));
        exit();
    } catch (Exception $e) {
        $error = "Error al eliminar el mensaje: " . $e->getMessage();
    }
}

// Manejar marcar como leído
if (isset($_GET['marcar_leido']) && is_numeric($_GET['marcar_leido'])) {
    try {
        $id = (int)$_GET['marcar_leido'];
        $stmt = $pdo->prepare("UPDATE mensajes_contacto SET leido = 1 WHERE id = ?");
        $stmt->execute([$id]);
        
        // Redirigir a la misma página sin el parámetro de marcar como leído
        $params = $_GET;
        unset($params['marcar_leido']);
        $query_string = http_build_query($params);
        header("Location: contacto-admin.php" . (!empty($query_string) ? '?' . $query_string : ''));
        exit();
    } catch (Exception $e) {
        $error = "Error al marcar el mensaje como leído: " . $e->getMessage();
    }
}

// Obtener el conteo de mensajes no leídos
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM mensajes_contacto WHERE leido = 0");
    $mensajesNoLeidos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
} catch (Exception $e) {
    $mensajesNoLeidos = 0;
    $error = "Error al obtener el conteo de mensajes no leídos: " . $e->getMessage();
}

// Obtener los mensajes de contacto
$mensajes = [];
$busqueda = '';
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : 'todos';
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$por_pagina = 10;
$offset = ($pagina - 1) * $por_pagina;

try {
    // Construir la consulta base
    $sql = "FROM mensajes_contacto WHERE 1=1";
    $params = [];
    
    // Aplicar filtros
    if (!empty($_GET['busqueda'])) {
        $busqueda = trim($_GET['busqueda']);
        $sql .= " AND (nombre LIKE ? OR email LIKE ? OR asunto LIKE ? OR mensaje LIKE ?)";
        $busqueda_param = "%$busqueda%";
        $params = array_merge($params, [$busqueda_param, $busqueda_param, $busqueda_param, $busqueda_param]);
    }
    
    if ($filtro_estado !== 'todos') {
        $leido = $filtro_estado === 'leidos' ? 1 : 0;
        $sql .= " AND leido = ?";
        $params[] = $leido;
    }
    
    // Obtener el total de registros para la paginación
    $count_sql = "SELECT COUNT(*) as total $sql";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_registros = $stmt->fetch()['total'];
    $total_paginas = ceil($total_registros / $por_pagina);
    
    // Aplicar ordenación y paginación
    $orden = isset($_GET['orden']) && in_array(strtoupper($_GET['orden']), ['ASC', 'DESC']) ? $_GET['orden'] : 'DESC';
    $orden_por = isset($_GET['orden_por']) ? $_GET['orden_por'] : 'fecha';
    $orden_por = in_array($orden_por, ['nombre', 'email', 'asunto', 'fecha', 'leido']) ? $orden_por : 'fecha';
    
    // Asegurarse de que si ordenan por 'fecha' se ordene por 'created_at'
    $campo_orden = ($orden_por === 'fecha') ? 'created_at' : $orden_por;
    
    // Construir la consulta final con LIMIT y OFFSET directamente en la cadena SQL
    $select_sql = "SELECT *, created_at as fecha $sql ORDER BY $campo_orden $orden LIMIT $por_pagina OFFSET $offset";
    $stmt = $pdo->prepare($select_sql);
    $stmt->execute($params);
    $mensajes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = "Error al obtener los mensajes: " . $e->getMessage();
}

// Marcar mensaje como leído
if (isset($_GET['marcar_leido']) && is_numeric($_GET['marcar_leido'])) {
    $id = (int)$_GET['marcar_leido'];
    try {
        $stmt = $pdo->prepare("UPDATE mensajes_contacto SET leido = 1 WHERE id = ?");
        $stmt->execute([$id]);
        
        // Redirigir para evitar reenvío del formulario
        header("Location: " . str_replace('&marcar_leido=' . $id, '', $_SERVER['REQUEST_URI']));
        exit();
    } catch (Exception $e) {
        $error = "Error al marcar el mensaje como leído: " . $e->getMessage();
    }
}

// Eliminar mensaje
if (isset($_GET['eliminar']) && is_numeric($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    try {
        $stmt = $pdo->prepare("DELETE FROM mensajes_contacto WHERE id = ?");
        $stmt->execute([$id]);
        
        // Redirigir para evitar reenvío del formulario
        header("Location: " . str_replace('&eliminar=' . $id, '', $_SERVER['REQUEST_URI']));
        exit();
    } catch (Exception $e) {
        $error = "Error al eliminar el mensaje: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Mensajes de Contacto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/admin.min.css">
    <style>
        /* Estilos específicos para la página de contacto */
        .table {
            width: 100%;
            margin-bottom: 1rem;
            color: #212529;
        }
        
        .table th, .table td {
            padding: 0.75rem;
            vertical-align: top;
            border-top: 1px solid #dee2e6;
        }
        
        .table thead th {
            vertical-align: bottom;
            border-bottom: 2px solid #dee2e6;
            background-color: #f8f9fa;
        }
        
        .table tbody + tbody {
            border-top: 2px solid #dee2e6;
        }
        
        .badge {
            font-size: 0.75em;
            font-weight: 600;
            padding: 0.35em 0.65em;
        }
        
        .modal-body {
            white-space: pre-wrap;
        }
        
        .main-content {
            padding: 0;
            max-width: 100%;
            margin: 0;
        }
        
        .card {
            margin: 0;
            width: 100%;
            border: none;
            box-shadow: none;
            background: transparent;
        }
        
        .card-body {
            padding: 0;
        }
        
        .card-header {
            background: white;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 1rem 0.5rem;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .table {
            width: 100%;
            margin-bottom: 0;
        }
        
        .table th, 
        .table td {
            padding: 0.75rem 0.5rem;
            white-space: nowrap;
            vertical-align: middle;
            font-size: 0.9rem;
        }
        
        .table td {
            padding-top: 0.6rem;
            padding-bottom: 0.6rem;
        }
        
        /* Ajustar el ancho de las columnas específicas */
        .table th:nth-child(1),
        .table td:nth-child(1) {
            width: 3%;
            min-width: 30px;
            padding-left: 1rem;
        }
        
        .table th:nth-child(2),
        .table td:nth-child(2) {
            width: 10%;
            min-width: 80px;
        }
        
        .table th:nth-child(3),
        .table td:nth-child(3) {
            width: 16%;
            min-width: 160px;
        }
        
        .table th:nth-child(4),
        .table td:nth-child(4) {
            width: 35%;
            min-width: 250px;
            max-width: none;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .table th:nth-child(5),
        .table td:nth-child(5) {
            width: 10%;
            min-width: 90px;
        }
        
        .table th:nth-child(6),
        .table td:nth-child(6) {
            width: 6%;
            min-width: 70px;
        }
        
        .table th:nth-child(7),
        .table td:nth-child(7) {
            width: 20%;
            min-width: 200px;
            text-align: right;
            padding-right: 1rem;
        }
        
        .table td:last-child {
            white-space: nowrap;
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
        }
        
        .table thead th {
            border-bottom: 2px solid #e9ecef;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            color: #6c757d;
        }
        
        .table tbody tr {
            transition: all 0.2s;
        }
        
        .table tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php 
            // Incluir el menú lateral
            $current_page = 'mensajes';
            include 'includes/sidebar.php';
            ?>
            <main class="col-md-9 ms-sm-auto col-lg-10 px-0">
                <!-- Main Content -->
                <div class="main-content">
                    <!-- Header -->
                    <header class="header">
                        <button class="sidebar-toggle" id="sidebarToggle">
                            <i class="fas fa-bars"></i>
                        </button>
                        <h1>Mensajes de Contacto</h1>
                        <div class="user-menu d-flex align-items-center">
                            <span class="me-2"><?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Admin'); ?></span>
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-user-circle me-1"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                    <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i>Perfil</a></li>
                                    <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Configuración</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Cerrar sesión</a></li>
                                </ul>
                            </div>
                        </div>
                    </header>

            <!-- Mensajes de error o éxito -->
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Filtros y búsqueda -->
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="mb-0">Filtrar Mensajes</h2>
                </div>
                <div class="card-body">
                    <form action="" method="get" class="form-inline">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="busqueda" placeholder="Buscar mensajes..." value="<?php echo htmlspecialchars($busqueda); ?>">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Buscar
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <select name="estado" class="form-select" onchange="this.form.submit()">
                                    <option value="todos" <?php echo $filtro_estado === 'todos' ? 'selected' : ''; ?>>Todos los mensajes</option>
                                    <option value="nuevos" <?php echo $filtro_estado === 'nuevos' ? 'selected' : ''; ?>>No leídos</option>
                                    <option value="leidos" <?php echo $filtro_estado === 'leidos' ? 'selected' : ''; ?>>Leídos</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <a href="contacto-admin.php" class="btn btn-outline-secondary w-100">
                                    <i class="fas fa-sync-alt"></i> Restablecer
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Lista de mensajes -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="mb-0">Mensajes Recibidos</h2>
                    <div class="text-muted">
                        Mostrando <?php echo count($mensajes); ?> de <?php echo $total_registros; ?> mensajes
                    </div>
                </div>
                
                <div class="table-responsive">
                    <?php if (count($mensajes) > 0): ?>
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Asunto</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mensajes as $index => $mensaje): ?>
                                    <tr class="<?php echo $mensaje['leido'] ? '' : 'table-active'; ?>" data-id="<?php echo $mensaje['id']; ?>">
                                        <td><?php echo $index + 1 + $offset; ?></td>
                                        <td><?php echo htmlspecialchars($mensaje['nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($mensaje['email']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars(mb_strlen($mensaje['asunto']) > 50 ? mb_substr($mensaje['asunto'], 0, 50) . '...' : $mensaje['asunto']); ?>
                                        </td>
                                        <td>
                                            <?php 
                                            // Verificar si la fecha es válida
                                            $fecha = !empty($mensaje['fecha']) ? $mensaje['fecha'] : $mensaje['created_at'];
                                            $fecha_dt = new DateTime($fecha);
                                            echo htmlspecialchars($fecha_dt->format('d/m/Y H:i'));
                                            ?>
                                        </td>
                                        <td>
                                            <?php if ($mensaje['leido']): ?>
                                                <span class="badge bg-success">Leído</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Nuevo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="verMensaje(<?php echo $mensaje['id']; ?>, '<?php echo htmlspecialchars(addslashes($mensaje['nombre'])); ?>')">
                                                <i class="far fa-eye"></i> Ver
                                            </button>
                                            <?php if (!$mensaje['leido']): ?>
                                                <a href="?marcar_leido=<?php echo $mensaje['id']; ?>" class="btn btn-sm btn-outline-success">
                                                    <i class="far fa-check-circle"></i> Marcar como leído
                                                </a>
                                            <?php endif; ?>
                                            <a href="?eliminar=<?php echo $mensaje['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Estás seguro de que deseas eliminar este mensaje? Esta acción no se puede deshacer.')">
                                                <i class="far fa-trash-alt"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="text-center p-5">
                            <i class="far fa-envelope-open fa-4x mb-3 text-muted"></i>
                            <h3>No se encontraron mensajes</h3>
                            <p class="text-muted">No hay mensajes que coincidan con los criterios de búsqueda.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($total_paginas > 1): ?>
                    <div class="card-footer d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Página <?php echo $pagina; ?> de <?php echo $total_paginas; ?>
                        </div>
                        <nav>
                            <ul class="pagination mb-0">
                                <?php if ($pagina > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina - 1])); ?>">
                                            &laquo; Anterior
                                        </a>
                                    </li>
                                <?php else: ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">&laquo; Anterior</span>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                    <?php if ($i == $pagina): ?>
                                        <li class="page-item active">
                                            <span class="page-link"><?php echo $i; ?></span>
                                        </li>
                                    <?php else: ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $i])); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                
                                <?php if ($pagina < $total_paginas): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina + 1])); ?>">
                                            Siguiente &raquo;
                                        </a>
                                    </li>
                                <?php else: ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">Siguiente &raquo;</span>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
                </div>
                </div> <!-- Cierre de main-content -->
            </main>
        </div> <!-- Cierre de row -->
    </div> <!-- Cierre de container-fluid -->

    <!-- Modal para ver el mensaje completo -->
    <div class="modal fade" id="mensajeModal" tabindex="-1" aria-labelledby="mensajeModalLabel" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5" id="mensajeModalLabel">Mensaje de <span id="nombreMensaje"></span></h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="message-meta">
                        <p><strong>Email:</strong> <span id="emailMensaje"></span></p>
                        <p><strong>Teléfono:</strong> <span id="telefonoMensaje"></span></p>
                        <p><strong>Fecha:</strong> <span id="fechaMensaje"></span></p>
                        <p><strong>Asunto:</strong> <span id="asuntoMensaje"></span></p>
                    </div>
                    <div class="message-content" id="contenidoMensaje"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <a href="#" id="responderBtn" class="btn btn-primary">
                        <i class="fas fa-reply"></i> Responder
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Función para ver el mensaje completo
        function verMensaje(id, nombre) {
            // Mostrar el modal
            const modal = new bootstrap.Modal(document.getElementById('mensajeModal'));
            
            // Cargar los datos del mensaje vía AJAX
            fetch(`get_mensaje.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('nombreMensaje').textContent = data.mensaje.nombre;
                        document.getElementById('emailMensaje').textContent = data.mensaje.email || 'No especificado';
                        document.getElementById('telefonoMensaje').textContent = data.mensaje.telefono || 'No especificado';
                        
                        // Formatear la fecha correctamente
                        const fecha = data.mensaje.fecha || data.mensaje.created_at;
                        const fechaObj = new Date(fecha);
                        const opciones = { 
                            day: '2-digit', 
                            month: '2-digit', 
                            year: 'numeric', 
                            hour: '2-digit', 
                            minute: '2-digit',
                            hour12: false
                        };
                        const fechaFormateada = fechaObj.toLocaleString('es-ES', opciones).replace(',', '');
                        document.getElementById('fechaMensaje').textContent = fechaFormateada;
                        
                        document.getElementById('asuntoMensaje').textContent = data.mensaje.asunto || 'Sin asunto';
                        document.getElementById('contenidoMensaje').textContent = data.mensaje.mensaje;
                        document.getElementById('responderBtn').href = `mailto:${data.mensaje.email}?subject=Re: ${encodeURIComponent(data.mensaje.asunto || 'Consulta')}`;
                        
                        // Marcar como leído si no lo está
                        if (!data.mensaje.leido) {
                            fetch(`marcar_leido.php?id=${id}`, { method: 'POST' })
                                .then(() => {
                                    // Actualizar la interfaz
                                    const fila = document.querySelector(`tr[data-id="${id}"]`);
                                    if (fila) {
                                        fila.classList.remove('table-active');
                                        const badge = fila.querySelector('.badge');
                                        if (badge) {
                                            badge.className = 'badge bg-success';
                                            badge.textContent = 'Leído';
                                        }
                                    }
                                });
                        }
                        
                        modal.show();
                    }
                })
                .catch(error => {
                    console.error('Error al cargar el mensaje:', error);
                    alert('Error al cargar el mensaje. Por favor, inténtalo de nuevo.');
                });
        }
    </script>
</body>
</html>
