<?php
// Incluir configuración temporal
require_once __DIR__ . '/_config.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mensajes de Contacto - Panel de Administración</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .unread {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .message-preview {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 300px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="../">Panel de Administración</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#">Mensajes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../newsletter/">Newsletter</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <span class="navbar-text me-3">
                        <?php echo htmlspecialchars($_SESSION['usuario_email']); ?>
                    </span>
                    <a href="../logout.php" class="btn btn-outline-light btn-sm">Cerrar sesión</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Mensajes de Contacto</h1>
            <div>
                <a href="exportar.php" class="btn btn-primary">
                    <i class="fas fa-download me-1"></i> Exportar CSV
                </a>
            </div>
        </div>

        <!-- Filtros y búsqueda -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="get" class="row g-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" class="form-control" name="buscar" placeholder="Buscar..." value="<?php echo htmlspecialchars($busqueda); ?>">
                            <button class="btn btn-outline-secondary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <select name="estado" class="form-select" onchange="this.form.submit()">
                            <option value="todos" <?php echo $filtro_estado === 'todos' ? 'selected' : ''; ?>>Todos los mensajes</option>
                            <option value="no_leidos" <?php echo $filtro_estado === 'no_leidos' ? 'selected' : ''; ?>>No leídos</option>
                            <option value="leidos" <?php echo $filtro_estado === 'leidos' ? 'selected' : ''; ?>>Leídos</option>
                        </select>
                    </div>
                    <?php if (!empty($busqueda) || $filtro_estado !== 'todos'): ?>
                    <div class="col-md-2">
                        <a href="?" class="btn btn-outline-secondary w-100">Limpiar filtros</a>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Listado de mensajes -->
        <div class="card">
            <div class="card-body p-0">
                <?php if (count($mensajes) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Asunto</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mensajes as $mensaje): ?>
                                <tr class="<?php echo !$mensaje['leido'] ? 'unread' : ''; ?>">
                                    <td><?php echo $mensaje['id']; ?></td>
                                    <td><?php echo htmlspecialchars($mensaje['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($mensaje['email']); ?></td>
                                    <td>
                                        <div class="message-preview" title="<?php echo htmlspecialchars($mensaje['asunto']); ?>">
                                            <?php echo htmlspecialchars($mensaje['asunto']); ?>
                                        </div>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($mensaje['fecha'])); ?></td>
                                    <td>
                                        <?php if ($mensaje['leido']): ?>
                                            <span class="badge bg-success">Leído</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">No leído</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="ver.php?id=<?php echo $mensaje['id']; ?>" class="btn btn-sm btn-outline-primary">
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
                    <div class="p-3 border-top">
                        <nav aria-label="Paginación">
                            <ul class="pagination justify-content-center mb-0">
                                <?php if ($pagina > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?pagina=<?php echo ($pagina - 1); ?><?php echo !empty($busqueda) ? '&buscar='.urlencode($busqueda) : ''; ?><?php echo $filtro_estado !== 'todos' ? '&estado='.urlencode($filtro_estado) : ''; ?>">
                                            Anterior
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                    <li class="page-item <?php echo $i === $pagina ? 'active' : ''; ?>">
                                        <a class="page-link" href="?pagina=<?php echo $i; ?><?php echo !empty($busqueda) ? '&buscar='.urlencode($busqueda) : ''; ?><?php echo $filtro_estado !== 'todos' ? '&estado='.urlencode($filtro_estado) : ''; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($pagina < $total_paginas): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?pagina=<?php echo ($pagina + 1); ?><?php echo !empty($busqueda) ? '&buscar='.urlencode($busqueda) : ''; ?><?php echo $filtro_estado !== 'todos' ? '&estado='.urlencode($filtro_estado) : ''; ?>">
                                            Siguiente
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="text-center p-5">
                        <div class="mb-3">
                            <i class="fas fa-inbox fa-4x text-muted"></i>
                        </div>
                        <h4>No hay mensajes para mostrar</h4>
                        <p class="text-muted">No se encontraron mensajes que coincidan con los criterios de búsqueda.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Marcar como leído al hacer clic en un mensaje
        document.querySelectorAll('tr[onclick]').forEach(row => {
            row.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                if (id) {
                    // Marcar como leído vía AJAX
                    fetch('marcar_leido.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'id=' + id
                    });
                    
                    // Actualizar la interfaz
                    this.classList.remove('unread');
                    this.querySelector('.badge').className = 'badge bg-success';
                    this.querySelector('.badge').textContent = 'Leído';
                }
            });
        });
    </script>
</body>
</html>
