<?php
// Incluir archivos necesarios
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/auth.php';

// Verificar que la solicitud sea GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('HTTP/1.1 405 Method Not Allowed');
    header('Allow: GET');
    exit(json_encode(['success' => false, 'message' => 'Método no permitido']));
}

// Verificar autenticación y permisos
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'administrador') {
    header('HTTP/1.1 403 Forbidden');
    exit(json_encode(['success' => false, 'message' => 'No autorizado']));
}

// Obtener y validar parámetros de paginación
$pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$por_pagina = isset($_GET['por_pagina']) ? max(1, min(100, (int)$_GET['por_pagina'])) : 20;

// Obtener y validar parámetros de filtrado
$filtros = [
    'busqueda' => trim($_GET['busqueda'] ?? ''),
    'rol' => in_array($_GET['rol'] ?? '', ['administrador', 'editor', 'usuario', 'lector']) ? $_GET['rol'] : '',
    'activo' => isset($_GET['activo']) ? (int)$_GET['activo'] : -1,
    'verificado' => isset($_GET['verificado']) ? (int)$_GET['verificado'] : -1,
    'orden' => in_array(strtoupper($_GET['orden'] ?? ''), ['ASC', 'DESC']) ? strtoupper($_GET['orden']) : 'ASC',
    'orden_por' => in_array($_GET['orden_por'] ?? '', ['id', 'nombre', 'email', 'fecha_registro', 'ultimo_acceso']) ? $_GET['orden_por'] : 'fecha_registro'
];

try {
    // Construir la consulta base
    $sql = "FROM usuarios_registrados WHERE 1=1";
    $params = [];
    $conditions = [];
    
    // Aplicar filtros
    if (!empty($filtros['busqueda'])) {
        $conditions[] = "(nombre LIKE :busqueda OR apellidos LIKE :busqueda OR email LIKE :busqueda)";
        $params[':busqueda'] = "%{$filtros['busqueda']}%";
    }
    
    if (!empty($filtros['rol'])) {
        $conditions[] = "rol = :rol";
        $params[':rol'] = $filtros['rol'];
    }
    
    if ($filtros['activo'] !== -1) {
        $conditions[] = "activo = :activo";
        $params[':activo'] = $filtros['activo'];
    }
    
    if ($filtros['verificado'] !== -1) {
        if ($filtros['verificado']) {
            $conditions[] = "fecha_verificacion IS NOT NULL";
        } else {
            $conditions[] = "fecha_verificacion IS NULL";
        }
    }
    
    // Aplicar condiciones a la consulta
    if (!empty($conditions)) {
        $sql .= " AND " . implode(" AND ", $conditions);
    }
    
    // Obtener el total de registros
    $stmt = $pdo->prepare("SELECT COUNT(*) as total " . $sql);
    $stmt->execute($params);
    $total_registros = $stmt->fetch()['total'];
    $total_paginas = ceil($total_registros / $por_pagina);
    
    // Ajustar la página actual si es necesario
    $pagina = max(1, min($total_paginas, $pagina));
    
    // Calcular el offset
    $offset = ($pagina - 1) * $por_pagina;
    
    // Consulta para obtener los usuarios
    $sql_usuarios = "
        SELECT 
            id, nombre, apellidos, email, rol, activo, 
            fecha_registro, fecha_verificacion, ultimo_acceso,
            CASE 
                WHEN fecha_verificacion IS NOT NULL THEN 1 
                ELSE 0 
            END as verificado
        " . $sql . "
        ORDER BY {$filtros['orden_por']} {$filtros['orden']}
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $pdo->prepare($sql_usuarios);
    
    // Vincular parámetros
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->bindValue(':limit', (int)$por_pagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear fechas
    foreach ($usuarios as &$usuario) {
        $usuario['fecha_registro_formateada'] = $usuario['fecha_registro'] 
            ? date('d/m/Y H:i', strtotime($usuario['fecha_registro'])) 
            : '';
            
        $usuario['ultimo_acceso_formateado'] = $usuario['ultimo_acceso'] 
            ? date('d/m/Y H:i', strtotime($usuario['ultimo_acceso'])) 
            : 'Nunca';
            
        $usuario['verificado'] = (bool)$usuario['verificado'];
    }
    
    // Registrar la acción
    logAccion('usuarios', 'listar', "Consultó la lista de usuarios (página $pagina)");
    
    // Devolver respuesta exitosa
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => [
            'usuarios' => $usuarios,
            'paginacion' => [
                'pagina_actual' => $pagina,
                'por_pagina' => $por_pagina,
                'total_registros' => (int)$total_registros,
                'total_paginas' => $total_paginas
            ],
            'filtros' => $filtros
        ]
    ]);
    
} catch (Exception $e) {
    // Registrar el error
    error_log("Error al listar usuarios: " . $e->getMessage());
    
    // Devolver error
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener la lista de usuarios',
        'debug' => DEBUG_MODE ? $e->getMessage() : null
    ]);
}
?>
