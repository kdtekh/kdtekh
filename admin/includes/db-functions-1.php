<?php
/**
 * Funciones para interactuar con la base de datos (Parte 1)
 */

// Incluir archivos necesarios
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

/**
 * Obtiene un usuario por su ID
 * 
 * @param int $userId ID del usuario
 * @return array|false Datos del usuario o false si no se encuentra
 */
function getUserById($userId) {
    $table = DB::table('usuarios');
    return DB::fetch("SELECT * FROM $table WHERE id = ?", [$userId]);
}

/**
 * Obtiene un usuario por su dirección de correo electrónico
 * 
 * @param string $email Correo electrónico del usuario
 * @return array|false Datos del usuario o false si no se encuentra
 */
function getUserByEmail($email) {
    $table = DB::table('usuarios');
    return DB::fetch("SELECT * FROM $table WHERE email = ?", [$email]);
}

/**
 * Obtiene todos los usuarios
 * 
 * @param int $limit Límite de resultados (opcional)
 * @param int $offset Desplazamiento (opcional)
 * @return array Lista de usuarios
 */
function getAllUsers($limit = null, $offset = 0) {
    $table = DB::table('usuarios');
    $sql = "SELECT * FROM $table ORDER BY nombre ASC";
    
    if ($limit !== null) {
        $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
    }
    
    return DB::fetchAll($sql);
}

/**
 * Cuenta el número total de usuarios
 * 
 * @return int Número total de usuarios
 */
function countUsers() {
    $table = DB::table('usuarios');
    return (int)DB::fetchColumn("SELECT COUNT(*) FROM $table");
}

/**
 * Crea un nuevo usuario
 * 
 * @param array $data Datos del usuario
 * @return int|false ID del nuevo usuario o false en caso de error
 */
function createUser($data) {
    $table = DB::table('usuarios');
    $now = date('Y-m-d H:i:s');
    
    $sql = "INSERT INTO $table (
        nombre, email, password, rol, estado, 
        fecha_creacion, fecha_actualizacion
    ) VALUES (
        :nombre, :email, :password, :rol, :estado,
        :fecha_creacion, :fecha_actualizacion
    )";
    
    $params = [
        ':nombre' => $data['nombre'] ?? '',
        ':email' => $data['email'] ?? '',
        ':password' => $data['password'] ?? '',
        ':rol' => $data['rol'] ?? 'usuario',
        ':estado' => $data['estado'] ?? 'activo',
        ':fecha_creacion' => $now,
        ':fecha_actualizacion' => $now
    ];
    
    try {
        DB::query($sql, $params);
        return DB::lastInsertId();
    } catch (Exception $e) {
        error_log("Error al crear usuario: " . $e->getMessage());
        return false;
    }
}

/**
 * Actualiza un usuario existente
 * 
 * @param int $userId ID del usuario a actualizar
 * @param array $data Datos a actualizar
 * @return bool Verdadero si se actualizó correctamente, falso en caso contrario
 */
function updateUser($userId, $data) {
    $table = DB::table('usuarios');
    $updates = [];
    $params = [':id' => $userId];
    
    // Construir la consulta dinámicamente
    foreach ($data as $key => $value) {
        if ($key === 'password' && !empty($value)) {
            $updates[] = "password = :password";
            $params[':password'] = $value;
        } elseif ($key !== 'id') {
            $updates[] = "$key = :$key";
            $params[":$key"] = $value;
        }
    }
    
    if (empty($updates)) {
        return false;
    }
    
    $updates[] = 'fecha_actualizacion = :fecha_actualizacion';
    $params[':fecha_actualizacion'] = date('Y-m-d H:i:s');
    
    $sql = "UPDATE $table SET " . implode(', ', $updates) . " WHERE id = :id";
    
    try {
        $stmt = DB::query($sql, $params);
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        error_log("Error al actualizar usuario: " . $e->getMessage());
        return false;
    }
}

/**
 * Elimina un usuario
 * 
 * @param int $userId ID del usuario a eliminar
 * @return bool Verdadero si se eliminó correctamente, falso en caso contrario
 */
function deleteUser($userId) {
    $table = DB::table('usuarios');
    
    try {
        $stmt = DB::query("DELETE FROM $table WHERE id = ?", [$userId]);
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        error_log("Error al eliminar usuario: " . $e->getMessage());
        return false;
    }
}

/**
 * Verifica las credenciales de un usuario
 * 
 * @param string $email Correo electrónico
 * @param string $password Contraseña sin encriptar
 * @return array|false Datos del usuario si las credenciales son correctas, false en caso contrario
 */
function verifyCredentials($email, $password) {
    $user = getUserByEmail($email);
    
    if ($user && password_verify($password, $user['password'])) {
        unset($user['password']);
        return $user;
    }
    
    return false;
}

/**
 * Obtiene todos los artículos
 * 
 * @param array $filters Filtros para la consulta (opcional)
 * @param int $limit Límite de resultados (opcional)
 * @param int $offset Desplazamiento (opcional)
 * @return array Lista de artículos con información del autor y categoría
 */
function getAllArticles($filters = [], $limit = null, $offset = 0) {
    $tableArticles = DB::table('articulos');
    $tableUsers = DB::table('usuarios');
    $tableCategories = DB::table('categorias');
    
    $sql = "SELECT 
                a.*, 
                u.nombre as autor_nombre,
                c.nombre as categoria_nombre
            FROM $tableArticles a
            LEFT JOIN $tableUsers u ON a.autor_id = u.id
            LEFT JOIN $tableCategories c ON a.categoria_id = c.id
            WHERE 1=1";
    
    $params = [];
    
    // Aplicar filtros
    if (!empty($filters['categoria_id'])) {
        $sql .= " AND a.categoria_id = :categoria_id";
        $params[':categoria_id'] = $filters['categoria_id'];
    }
    
    if (!empty($filters['autor_id'])) {
        $sql .= " AND a.autor_id = :autor_id";
        $params[':autor_id'] = $filters['autor_id'];
    }
    
    if (!empty($filters['estado'])) {
        $sql .= " AND a.estado = :estado";
        $params[':estado'] = $filters['estado'];
    }
    
    if (!empty($filters['busqueda'])) {
        $sql .= " AND (a.titulo LIKE :busqueda OR a.contenido LIKE :busqueda OR a.extracto LIKE :busqueda)";
        $params[':busqueda'] = "%{$filters['busqueda']}%";
    }
    
    // Ordenar
    $orderBy = 'a.fecha_publicacion';
    $orderDir = 'DESC';
    
    if (!empty($filters['ordenar_por'])) {
        $orderBy = 'a.' . preg_replace('/[^a-zA-Z0-9_]/', '', $filters['ordenar_por']);
    }
    
    if (!empty($filters['orden_dir']) && in_array(strtoupper($filters['orden_dir']), ['ASC', 'DESC'])) {
        $orderDir = strtoupper($filters['orden_dir']);
    }
    
    $sql .= " ORDER BY $orderBy $orderDir";
    
    // Limitar resultados
    if ($limit !== null) {
        $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
    }
    
    return DB::fetchAll($sql, $params);
}

/**
 * Cuenta el número total de artículos que coinciden con los filtros
 * 
 * @param array $filters Filtros para la consulta (opcional)
 * @return int Número total de artículos
 */
function countArticles($filters = []) {
    $tableArticles = DB::table('articulos');
    $tableUsers = DB::table('usuarios');
    $tableCategories = DB::table('categorias');
    
    $sql = "SELECT COUNT(*) as total 
            FROM $tableArticles a
            LEFT JOIN $tableUsers u ON a.autor_id = u.id
            LEFT JOIN $tableCategories c ON a.categoria_id = c.id
            WHERE 1=1";
    
    $params = [];
    
    // Aplicar filtros (los mismos que en getAllArticles)
    if (!empty($filters['categoria_id'])) {
        $sql .= " AND a.categoria_id = :categoria_id";
        $params[':categoria_id'] = $filters['categoria_id'];
    }
    
    if (!empty($filters['autor_id'])) {
        $sql .= " AND a.autor_id = :autor_id";
        $params[':autor_id'] = $filters['autor_id'];
    }
    
    if (!empty($filters['estado'])) {
        $sql .= " AND a.estado = :estado";
        $params[':estado'] = $filters['estado'];
    }
    
    if (!empty($filters['busqueda'])) {
        $sql .= " AND (a.titulo LIKE :busqueda OR a.contenido LIKE :busqueda OR a.extracto LIKE :busqueda)";
        $params[':busqueda'] = "%{$filters['busqueda']}%";
    }
    
    $result = DB::fetch($sql, $params);
    return (int)($result['total'] ?? 0);
}

/**
 * Obtiene un artículo por su ID
 * 
 * @param int $articleId ID del artículo
 * @return array|false Datos del artículo o false si no se encuentra
 */
function getArticleById($articleId) {
    $tableArticles = DB::table('articulos');
    $tableUsers = DB::table('usuarios');
    $tableCategories = DB::table('categorias');
    
    $sql = "SELECT 
                a.*, 
                u.nombre as autor_nombre, u.email as autor_email,
                c.nombre as categoria_nombre, c.slug as categoria_slug
            FROM $tableArticles a
            LEFT JOIN $tableUsers u ON a.autor_id = u.id
            LEFT JOIN $tableCategories c ON a.categoria_id = c.id
            WHERE a.id = ?";
    
    return DB::fetch($sql, [$articleId]);
}

/**
 * Crea un nuevo artículo
 * 
 * @param array $data Datos del artículo
 * @return int|false ID del nuevo artículo o false en caso de error
 */
function createArticle($data) {
    $table = DB::table('articulos');
    $now = date('Y-m-d H:i:s');
    
    $sql = "INSERT INTO $table (
        titulo, slug, extracto, contenido, imagen_destacada, 
        categoria_id, autor_id, estado, fecha_publicacion, 
        fecha_creacion, fecha_actualizacion
    ) VALUES (
        :titulo, :slug, :extracto, :contenido, :imagen_destacada,
        :categoria_id, :autor_id, :estado, :fecha_publicacion,
        :fecha_creacion, :fecha_actualizacion
    )";
    
    $params = [
        ':titulo' => $data['titulo'] ?? '',
        ':slug' => $data['slug'] ?? slugify($data['titulo'] ?? ''),
        ':extracto' => $data['extracto'] ?? '',
        ':contenido' => $data['contenido'] ?? '',
        ':imagen_destacada' => $data['imagen_destacada'] ?? null,
        ':categoria_id' => $data['categoria_id'] ?? null,
        ':autor_id' => $data['autor_id'] ?? null,
        ':estado' => $data['estado'] ?? 'borrador',
        ':fecha_publicacion' => $data['fecha_publicacion'] ?? $now,
        ':fecha_creacion' => $now,
        ':fecha_actualizacion' => $now
    ];
    
    try {
        DB::query($sql, $params);
        return DB::lastInsertId();
    } catch (Exception $e) {
        error_log("Error al crear artículo: " . $e->getMessage());
        return false;
    }
}
