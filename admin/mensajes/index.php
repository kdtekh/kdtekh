<?php
// Habilitar visualización de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/mensajes_errors.log');

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticación
if (!isset($_SESSION['usuario_id'])) {
    // Guardar la URL actual para redirigir después del login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    
    // Redirigir al login
    header('Location: /admin/login.php');
    exit();
}

// Verificar el tiempo de inactividad (30 minutos)
$inactive = 1800; // 30 minutos en segundos
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $inactive)) {
    // Destruir la sesión
    session_unset();
    session_destroy();
    
    // Redirigir al login con mensaje de tiempo de espera agotado
    header('Location: /admin/login.php?timeout=1');
    exit();
}

// Actualizar tiempo de última actividad
$_SESSION['last_activity'] = time();

// Incluir configuración principal
require_once __DIR__ . '/../config.php';

// Incluir configuración común
try {
    require_once __DIR__ . '/_config.php';
    error_log("Archivos de configuración cargados correctamente");
} catch (Throwable $e) {
    error_log("Error al cargar archivos de configuración: " . $e->getMessage());
    die("<h1>Error de configuración</h1><p>Por favor, contacta al administrador.</p>");
}

// Configuración de paginación
$por_pagina = 15;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$inicio = ($pagina > 1) ? ($pagina * $por_pagina - $por_pagina) : 0;

// Búsqueda y filtros
$busqueda = $_GET['buscar'] ?? '';
$filtro_estado = $_GET['estado'] ?? 'todos';

// Construir consulta
$where = [];
$params = [];

if (!empty($busqueda)) {
    $where[] = "(nombre LIKE :busqueda OR email LIKE :busqueda2 OR asunto LIKE :busqueda3 OR mensaje LIKE :busqueda4)";
    $params[':busqueda'] = $params[':busqueda2'] = $params[':busqueda3'] = $params[':busqueda4'] = "%$busqueda%";
}

if ($filtro_estado === 'no_leidos') {
    $where[] = "leido = 0";
} elseif ($filtro_estado === 'leidos') {
    $where[] = "leido = 1";
}

$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Obtener total de mensajes
$total = $pdo->prepare("SELECT COUNT(*) FROM mensajes_contacto $where_clause");
$total->execute($params);
$total_mensajes = $total->fetchColumn();
$total_paginas = ceil($total_mensajes / $por_pagina);

// Obtener mensajes
$query = "SELECT * FROM mensajes_contacto $where_clause ORDER BY fecha DESC LIMIT $inicio, $por_pagina";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$mensajes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mensajes de Contacto - Panel de Control</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main class="container">
        <div class="page-header">
            <h1>Mensajes de Contacto</h1>
            <a href="#" class="btn btn-primary" id="exportarMensajes">
                <i class="fas fa-download"></i> Exportar CSV
            </a>
        </div>
        
        <!-- Filtros y búsqueda -->
        <div class="filters">
            <form method="GET" class="search-form">
                <div class="form-group">
                    <input type="text" name="buscar" placeholder="Buscar mensajes..." value="<?= htmlspecialchars($busqueda) ?>">
                    <select name="estado">
                        <option value="todos" <?= $filtro_estado === 'todos' ? 'selected' : '' ?>>Todos los mensajes</option>
                        <option value="no_leidos" <?= $filtro_estado === 'no_leidos' ? 'selected' : '' ?>>No leídos</option>
                        <option value="leidos" <?= $filtro_estado === 'leidos' ? 'selected' : '' ?>>Leídos</option>
                    </select>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                    <?php if (!empty($busqueda) || $filtro_estado !== 'todos'): ?>
                    <a href="index.php" class="btn btn-secondary">Limpiar filtros</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Asunto</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($mensajes)): ?>
                    <tr>
                        <td colspan="7" class="text-center">No se encontraron mensajes</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($mensajes as $mensaje): ?>
                        <tr class="<?= $mensaje['leido'] ? '' : 'unread' ?>">
                            <td><?= $mensaje['id'] ?></td>
                            <td><?= htmlspecialchars($mensaje['nombre']) ?></td>
                            <td><a href="mailto:<?= htmlspecialchars($mensaje['email']) ?>"><?= htmlspecialchars($mensaje['email']) ?></a></td>
                            <td><?= htmlspecialchars($mensaje['asunto']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($mensaje['fecha_envio'])) ?></td>
                            <td>
                                <span class="badge <?= $mensaje['leido'] ? 'badge-success' : 'badge-warning' ?>">
                                    <?= $mensaje['leido'] ? 'Leído' : 'Nuevo' ?>
                                </span>
                            </td>
                            <td class="actions">
                                <a href="ver.php?id=<?= $mensaje['id'] ?>" class="btn btn-sm btn-primary" title="Ver">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="responder.php?id=<?= $mensaje['id'] ?>" class="btn btn-sm btn-secondary" title="Responder">
                                    <i class="fas fa-reply"></i>
                                </a>
                                <a href="eliminar.php?id=<?= $mensaje['id'] ?>" class="btn btn-sm btn-danger" title="Eliminar" 
                                   onclick="return confirm('¿Estás seguro de eliminar este mensaje?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($total_paginas > 1): ?>
        <div class="pagination">
            <?php if ($pagina > 1): ?>
                <a href="?pagina=<?= $pagina - 1 ?><?= !empty($busqueda) ? '&buscar='.urlencode($busqueda) : '' ?><?= $filtro_estado !== 'todos' ? '&estado='.urlencode($filtro_estado) : '' ?>" class="btn">
                    &laquo; Anterior
                </a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                <a href="?pagina=<?= $i ?><?= !empty($busqueda) ? '&buscar='.urlencode($busqueda) : '' ?><?= $filtro_estado !== 'todos' ? '&estado='.urlencode($filtro_estado) : '' ?>" 
                   class="btn <?= $i == $pagina ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($pagina < $total_paginas): ?>
                <a href="?pagina=<?= $pagina + 1 ?><?= !empty($busqueda) ? '&buscar='.urlencode($busqueda) : '' ?><?= $filtro_estado !== 'todos' ? '&estado='.urlencode($filtro_estado) : '' ?>" class="btn">
                    Siguiente &raquo;
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </main>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
    // Marcar como leído al hacer clic en una fila
    document.querySelectorAll('tr[data-id]').forEach(row => {
        row.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            // Marcar como leído vía AJAX
            fetch(`marcar-leido.php?id=${id}`, { method: 'POST' });
            // Actualizar la interfaz
            this.classList.remove('unread');
            const badge = this.querySelector('.badge');
            if (badge) {
                badge.textContent = 'Leído';
                badge.classList.remove('badge-warning');
                badge.classList.add('badge-success');
            }
        });
    });
    
    // Exportar a CSV
    document.getElementById('exportarMensajes').addEventListener('click', function(e) {
        e.preventDefault();
        // Agregar parámetros de búsqueda a la URL de exportación
        let url = 'exportar.php';
        const params = new URLSearchParams();
        
        if ('<?= $busqueda ?>') params.append('buscar', '<?= $busqueda ?>');
        if ('<?= $filtro_estado ?>' !== 'todos') params.append('estado', '<?= $filtro_estado ?>');
        
        if (params.toString()) {
            url += '?' + params.toString();
        }
        
        window.location.href = url;
    });
    </script>
</body>
</html>
