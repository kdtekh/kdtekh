<?php
// Habilitar visualización de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/mensajes_nuevo.log');

// Incluir configuración principal
require_once __DIR__ . '/../config.php';

// Incluir autenticación común
require_once __DIR__ . '/../includes/auth_common.php';

// Verificar autenticación
requireAuth();

// Incluir configuración común
require_once __DIR__ . '/../includes/config_common.php';

// Configuración de paginación
$por_pagina = 15;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$inicio = ($pagina > 1) ? ($pagina * $por_pagina - $por_pagina) : 0;

// Búsqueda y filtros
$busqueda = $_GET['buscar'] ?? '';
$filtro_estado = $_GET['estado'] ?? 'todos';

// Consulta para contar mensajes totales
$where = [];
$params = [];

if (!empty($busqueda)) {
    $where[] = "(nombre LIKE :busqueda OR email LIKE :busqueda2 OR asunto LIKE :busqueda3 OR mensaje LIKE :busqueda4)";
    $params[':busqueda'] = $params[':busqueda2'] = $params[':busqueda3'] = $params[':busqueda4'] = "%$busqueda%";
}

if ($filtro_estado !== 'todos') {
    $where[] = "leido = :leido";
    $params[':leido'] = ($filtro_estado === 'leidos') ? 1 : 0;
}

$where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Contar mensajes totales
try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM mensajes_contacto $where_sql");
    $stmt->execute($params);
    $total_mensajes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_paginas = ceil($total_mensajes / $por_pagina);
    
    // Obtener mensajes para la página actual
    $sql = "SELECT * FROM mensajes_contacto $where_sql ORDER BY fecha_envio DESC LIMIT :inicio, :por_pagina";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':inicio', $inicio, PDO::PARAM_INT);
    $stmt->bindValue(':por_pagina', $por_pagina, PDO::PARAM_INT);
    
    // Añadir parámetros de búsqueda si existen
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $mensajes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Error de base de datos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mensajes de Contacto - Panel de Administración</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .unread { font-weight: bold; }
        .pagination { margin: 20px 0; }
        .pagination a { margin: 0 5px; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1>Mensajes de Contacto</h1>
        
        <!-- Barra de búsqueda y filtros -->
        <div class="row mb-4">
            <div class="col-md-6">
                <form method="get" class="d-flex">
                    <input type="text" name="buscar" class="form-control me-2" value="<?= htmlspecialchars($busqueda) ?>" placeholder="Buscar...">
                    <select name="estado" class="form-select me-2" style="width: auto;">
                        <option value="todos" <?= $filtro_estado === 'todos' ? 'selected' : '' ?>>Todos</option>
                        <option value="leidos" <?= $filtro_estado === 'leidos' ? 'selected' : '' ?>>Leídos</option>
                        <option value="no_leidos" <?= $filtro_estado === 'no_leidos' ? 'selected' : '' ?>>No leídos</option>
                    </select>
                    <button type="submit" class="btn btn-primary">Buscar</button>
                </form>
            </div>
        </div>
        
        <!-- Tabla de mensajes -->
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Asunto</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mensajes as $mensaje): ?>
                    <tr class="<?= $mensaje['leido'] ? '' : 'table-primary' ?>">
                        <td><?= htmlspecialchars($mensaje['id']) ?></td>
                        <td><?= htmlspecialchars($mensaje['nombre']) ?></td>
                        <td><?= htmlspecialchars($mensaje['email']) ?></td>
                        <td><?= htmlspecialchars($mensaje['asunto']) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($mensaje['fecha_envio'])) ?></td>
                        <td>
                            <a href="ver.php?id=<?= $mensaje['id'] ?>" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i> Ver
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Paginación -->
        <?php if ($total_paginas > 1): ?>
        <nav aria-label="Paginación">
            <ul class="pagination">
                <?php if ($pagina > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?pagina=<?= ($pagina - 1) ?>&buscar=<?= urlencode($busqueda) ?>&estado=<?= $filtro_estado ?>">Anterior</a>
                </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                <li class="page-item <?= $i === $pagina ? 'active' : '' ?>">
                    <a class="page-link" href="?pagina=<?= $i ?>&buscar=<?= urlencode($busqueda) ?>&estado=<?= $filtro_estado ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
                
                <?php if ($pagina < $total_paginas): ?>
                <li class="page-item">
                    <a class="page-link" href="?pagina=<?= ($pagina + 1) ?>&buscar=<?= urlencode($busqueda) ?>&estado=<?= $filtro_estado ?>">Siguiente</a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
