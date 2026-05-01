<?php
// Incluir archivos necesarios
define('ADMIN_PATH', dirname(__FILE__));
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Verificar permisos de administrador
if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'administrador') {
    $_SESSION['error_message'] = 'No tienes permiso para acceder a esta sección';
    header('Location: index.php');
    exit();
}

// Establecer la página actual
$current_page = 'usuarios';

// Obtener parámetros de paginación
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$porPagina = 15;
$offset = ($pagina - 1) * $porPagina;

// Obtener parámetros de búsqueda
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$filtro_rol = isset($_GET['rol']) ? $_GET['rol'] : '';
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : '';

// Construir la consulta SQL
$where = [];
$params = [];

if (!empty($busqueda)) {
    $where[] = "(nombre LIKE :busqueda OR apellidos LIKE :busqueda OR email LIKE :busqueda)";
    $params[':busqueda'] = "%$busqueda%";
}

if (!empty($filtro_rol)) {
    $where[] = "rol = :rol";
    $params[':rol'] = $filtro_rol;
}

if ($filtro_estado !== '') {
    $where[] = "activo = :activo";
    $params[':activo'] = (int)$filtro_estado;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Obtener el total de usuarios
$sqlTotal = "SELECT COUNT(*) as total FROM usuarios_registrados $whereClause";
$stmtTotal = $pdo->prepare($sqlTotal);

foreach ($params as $key => $value) {
    $stmtTotal->bindValue($key, $value);
}

$stmtTotal->execute();
$totalUsuarios = $stmtTotal->fetch()['total'];
$totalPaginas = ceil($totalUsuarios / $porPagina);

// Obtener los usuarios con paginación
$sql = "SELECT * FROM usuarios_registrados $whereClause ORDER BY id DESC LIMIT :offset, :limit";
$stmt = $pdo->prepare($sql);

// Vincular parámetros de búsqueda
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

// Vincular parámetros de paginación
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $porPagina, PDO::PARAM_INT);

$stmt->execute();
$usuarios = $stmt->fetchAll();

// Obtener estadísticas
$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM usuarios_registrados")->fetchColumn(),
    'activos' => $pdo->query("SELECT COUNT(*) FROM usuarios_registrados WHERE activo = 1")->fetchColumn(),
    'verificados' => $pdo->query("SELECT COUNT(*) FROM usuarios_registrados WHERE fecha_verificacion IS NOT NULL")->fetchColumn(),
    'ultimo_registro' => $pdo->query("SELECT fecha_registro FROM usuarios_registrados ORDER BY id DESC LIMIT 1")->fetchColumn()
];

// Obtener roles únicos para el filtro
$roles = $pdo->query("SELECT DISTINCT rol FROM usuarios_registrados WHERE rol IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);

// Incluir encabezado
$page_title = 'Gestión de Usuarios';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Gestión de Usuarios</h1>
    
    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-filter me-1"></i>
            Filtros
        </div>
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="busqueda" class="form-control" placeholder="Buscar por nombre o email" 
                           value="<?php echo htmlspecialchars($busqueda); ?>">
                </div>
                <div class="col-md-3">
                    <select name="rol" class="form-select">
                        <option value="">Todos los roles</option>
                        <?php foreach ($roles as $rol): ?>
                            <option value="<?php echo htmlspecialchars($rol); ?>" 
                                <?php echo $filtro_rol === $rol ? 'selected' : ''; ?>>
                                <?php echo ucfirst(htmlspecialchars($rol)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="estado" class="form-select">
                        <option value="">Todos los estados</option>
                        <option value="1" <?php echo $filtro_estado === '1' ? 'selected' : ''; ?>>Activos</option>
                        <option value="0" <?php echo $filtro_estado === '0' ? 'selected' : ''; ?>>Inactivos</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i> Buscar
                    </button>
                </div>
                <?php if (!empty($busqueda) || !empty($filtro_rol) || $filtro_estado !== ''): ?>
                    <div class="col-12">
                        <a href="usuarios.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-times me-1"></i> Limpiar filtros
                        </a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Estadísticas -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small">Total Usuarios</div>
                            <div class="h4 mb-0"><?php echo number_format($stats['total']); ?></div>
                        </div>
                        <i class="fas fa-users fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small">Usuarios Activos</div>
                            <div class="h4 mb-0"><?php echo number_format($stats['activos']); ?></div>
                        </div>
                        <i class="fas fa-user-check fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-info text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small">Cuentas Verificadas</div>
                            <div class="h4 mb-0"><?php echo number_format($stats['verificados']); ?></div>
                        </div>
                        <i class="fas fa-envelope-open-text fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small">Último Registro</div>
                            <div class="h6 mb-0">
                                <?php echo $stats['ultimo_registro'] ? date('d/m/Y', strtotime($stats['ultimo_registro'])) : 'N/A'; ?>
                            </div>
                        </div>
                        <i class="fas fa-calendar-alt fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de usuarios -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-users me-1"></i>
                Lista de Usuarios
            </div>
            <a href="usuario-nuevo.php" class="btn btn-primary btn-sm">
                <i class="fas fa-plus me-1"></i> Nuevo Usuario
            </a>
        </div>
        <div class="card-body">
            <?php if (!empty($usuarios)): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="usuariosTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Registro</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td>#<?php echo $usuario['id']; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar me-2">
                                                <?php 
                                                $inicial = mb_substr($usuario['nombre'], 0, 1, 'UTF-8');
                                                $color = 'bg-' . substr(md5($usuario['id']), 0, 6);
                                                ?>
                                                <div class="avatar-initial rounded-circle d-flex align-items-center justify-content-center" 
                                                     style="width: 36px; height: 36px; background-color: #<?php echo $color; ?>20; color: #<?php echo $color; ?>; font-weight: 600;">
                                                    <?php echo strtoupper($inicial); ?>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($usuario['nombre'] . ' ' . ($usuario['apellidos'] ?? '')); ?></div>
                                                <small class="text-muted">ID: <?php echo $usuario['id']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($usuario['email']); ?>">
                                            <?php echo htmlspecialchars($usuario['email']); ?>
                                        </div>
                                        <?php if (!empty($usuario['fecha_verificacion'])): ?>
                                            <small class="text-success">
                                                <i class="fas fa-check-circle"></i> Verificado
                                            </small>
                                        <?php else: ?>
                                            <small class="text-warning">
                                                <i class="fas fa-exclamation-circle"></i> Sin verificar
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $rol = $usuario['rol'] ?? 'usuario';
                                        $rolClases = [
                                            'administrador' => 'bg-danger',
                                            'editor' => 'bg-primary',
                                            'usuario' => 'bg-success',
                                            'lector' => 'bg-info'
                                        ];
                                        $claseRol = $rolClases[strtolower($rol)] ?? 'bg-secondary';
                                        ?>
                                        <span class="badge <?php echo $claseRol; ?> text-white">
                                            <?php echo ucfirst(htmlspecialchars($rol)); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($usuario['activo']): ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check-circle me-1"></i> Activo
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-times-circle me-1"></i> Inactivo
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <?php echo date('d/m/Y', strtotime($usuario['fecha_registro'])); ?>
                                        </div>
                                        <div class="text-muted small">
                                            <?php echo date('H:i', strtotime($usuario['fecha_registro'])); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="usuario-editar.php?id=<?php echo $usuario['id']; ?>" 
                                               class="btn btn-outline-primary" 
                                               title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-outline-<?php echo $usuario['activo'] ? 'warning' : 'success'; ?> toggle-estado" 
                                                    data-id="<?php echo $usuario['id']; ?>"
                                                    data-estado="<?php echo $usuario['activo'] ? 0 : 1; ?>"
                                                    title="<?php echo $usuario['activo'] ? 'Desactivar' : 'Activar'; ?> usuario">
                                                <i class="fas fa-<?php echo $usuario['activo'] ? 'ban' : 'check'; ?>"></i>
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-outline-danger eliminar-usuario" 
                                                    data-id="<?php echo $usuario['id']; ?>"
                                                    title="Eliminar usuario">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <?php if ($totalPaginas > 1): ?>
                    <nav aria-label="Navegación de páginas" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($pagina > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina - 1])); ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link">
                                        <i class="fas fa-chevron-left"></i>
                                    </span>
                                </li>
                            <?php endif; ?>

                            <?php
                            // Mostrar máximo 5 enlaces de página
                            $inicioPagina = max(1, $pagina - 2);
                            $finPagina = min($totalPaginas, $inicioPagina + 4);
                            $inicioPagina = max(1, $finPagina - 4);
                            
                            if ($inicioPagina > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => 1])); ?>">1</a>
                                </li>
                                <?php if ($inicioPagina > 2): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for ($i = $inicioPagina; $i <= $finPagina; $i++): ?>
                                <li class="page-item <?php echo $i == $pagina ? 'active' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $i])); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($finPagina < $totalPaginas): ?>
                                <?php if ($finPagina < $totalPaginas - 1): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif; ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $totalPaginas])); ?>">
                                        <?php echo $totalPaginas; ?>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php if ($pagina < $totalPaginas): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina + 1])); ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link">
                                        <i class="fas fa-chevron-right"></i>
                                    </span>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>

            <?php else: ?>
                <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="fas fa-users-slash fa-4x text-muted"></i>
                    </div>
                    <h5>No se encontraron usuarios</h5>
                    <p class="text-muted">No hay usuarios que coincidan con los criterios de búsqueda.</p>
                    <a href="usuarios.php" class="btn btn-primary mt-3">
                        <i class="fas fa-redo me-1"></i> Mostrar todos los usuarios
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de confirmación para eliminar usuario -->
<div class="modal fade" id="confirmarEliminarModal" tabindex="-1" aria-labelledby="confirmarEliminarModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="confirmarEliminarModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i> Confirmar eliminación
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas eliminar este usuario? Esta acción no se puede deshacer.</p>
                <p class="mb-0"><strong>Nota:</strong> El usuario no será eliminado físicamente, solo se marcará como inactivo.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cancelar
                </button>
                <form id="formEliminarUsuario" action="api/usuarios/eliminar.php" method="POST" class="d-inline">
                    <input type="hidden" name="id" id="usuarioEliminarId" value="">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash-alt me-1"></i> Eliminar
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal de cambio de estado -->
<div class="modal fade" id="cambiarEstadoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-exchange-alt me-2"></i> Cambiar estado de usuario
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p id="mensajeCambioEstado"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cerrar
                </button>
                <button type="button" class="btn btn-primary" id="confirmarCambioEstado">
                    <i class="fas fa-check me-1"></i> Aceptar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts adicionales -->
<script>
$(document).ready(function() {
    // Inicializar DataTables
    $('#usuariosTable').DataTable({
        responsive: true,
        order: [[0, 'desc']],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json'
        },
        dom: '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6"p>>',
        lengthMenu: [10, 25, 50, 100],
        pageLength: 25
    });

    // Variables para los modales
    let usuarioId = null;
    let nuevoEstado = null;

    // Manejar el clic en el botón de eliminar
    $('.eliminar-usuario').on('click', function(e) {
        e.preventDefault();
        usuarioId = $(this).data('id');
        $('#usuarioEliminarId').val(usuarioId);
        $('#confirmarEliminarModal').modal('show');
    });

    // Manejar el envío del formulario de eliminación
    $('#formEliminarUsuario').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const originalBtnText = submitBtn.html();
        
        // Mostrar indicador de carga
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Eliminando...');
        
        // Enviar la solicitud AJAX
        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Mostrar mensaje de éxito y recargar la página
                    mostrarAlerta('success', 'Usuario eliminado correctamente');
                    setTimeout(() => { location.reload(); }, 1500);
                } else {
                    mostrarAlerta('danger', response.message || 'Error al eliminar el usuario');
                }
            },
            error: function() {
                mostrarAlerta('danger', 'Error de conexión. Inténtalo de nuevo.');
            },
            complete: function() {
                $('#confirmarEliminarModal').modal('hide');
                submitBtn.prop('disabled', false).html(originalBtnText);
            }
        });
    });

    // Manejar el clic en el botón de cambiar estado
    $('.toggle-estado').on('click', function() {
        usuarioId = $(this).data('id');
        nuevoEstado = $(this).data('estado');
        
        const accion = nuevoEstado == 1 ? 'activar' : 'desactivar';
        $('#mensajeCambioEstado').text(`¿Estás seguro de que deseas ${accion} este usuario?`);
        $('#cambiarEstadoModal').modal('show');
    });

    // Confirmar cambio de estado
    $('#confirmarCambioEstado').on('click', function() {
        if (!usuarioId || nuevoEstado === null) return;
        
        const btn = $(this);
        const originalBtnText = btn.html();
        
        // Mostrar indicador de carga
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Procesando...');
        
        $.ajax({
            url: 'api/usuarios/cambiar-estado.php',
            method: 'POST',
            data: { 
                id: usuarioId,
                activo: nuevoEstado
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Mostrar mensaje de éxito y recargar la página
                    const accion = nuevoEstado == 1 ? 'activado' : 'desactivado';
                    mostrarAlerta('success', `Usuario ${accion} correctamente`);
                    setTimeout(() => { location.reload(); }, 1500);
                } else {
                    mostrarAlerta('danger', response.message || 'Error al cambiar el estado del usuario');
                }
            },
            error: function() {
                mostrarAlerta('danger', 'Error de conexión. Inténtalo de nuevo.');
            },
            complete: function() {
                $('#cambiarEstadoModal').modal('hide');
                btn.prop('disabled', false).html(originalBtnText);
                usuarioId = null;
                nuevoEstado = null;
            }
        });
    });

    // Función para mostrar alertas
    function mostrarAlerta(tipo, mensaje) {
        const alerta = `
            <div class="alert alert-${tipo} alert-dismissible fade show" role="alert">
                ${mensaje}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        `;
        
        // Insertar la alerta al principio del contenedor principal
        $('.container-fluid').prepend(alerta);
        
        // Desaparecer después de 5 segundos
        setTimeout(() => {
            $('.alert').alert('close');
        }, 5000);
    }
});
</script>

<!-- Estilos adicionales -->
<style>
.avatar-initial {
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 500;
}

.table th {
    font-weight: 600;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-top: none;
    border-bottom-width: 1px;
}

.table td {
    vertical-align: middle;
}

.badge {
    font-weight: 500;
    padding: 0.4em 0.6em;
}

.btn-group-sm > .btn, .btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

/* Estilos para los botones de acción */
.btn-outline-primary, .btn-outline-success, .btn-outline-warning, .btn-outline-danger {
    transition: all 0.2s;
}

.btn-outline-primary:hover {
    background-color: #0d6efd;
    color: white;
}

.btn-outline-success:hover {
    background-color: #198754;
    color: white;
}

.btn-outline-warning:hover {
    background-color: #ffc107;
    color: #000;
}

.btn-outline-danger:hover {
    background-color: #dc3545;
    color: white;
}

/* Ajustes para dispositivos móviles */
@media (max-width: 768px) {
    .card-body {
        padding: 1rem;
    }
    
    .btn {
        padding: 0.4rem 0.75rem;
        font-size: 0.9rem;
    }
    
    .table-responsive {
        border: none;
    }
}
</style>

<?php
// Incluir pie de página
require_once __DIR__ . '/includes/footer.php';
?>
