<?php
/**
 * Funciones para interactuar con la base de datos (Parte 2)
 */

// Incluir archivos necesarios
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

/**
 * Actualiza un artículo existente
 * 
 * @param int $articleId ID del artículo a actualizar
 * @param array $data Datos a actualizar
 * @return bool Verdadero si se actualizó correctamente, falso en caso contrario
 */
function updateArticle($articleId, $data) {
    $table = DB::table('articulos');
    $updates = [];
    $params = [':id' => $articleId];
    
    // Campos permitidos para actualizar
    $allowedFields = [
        'titulo', 'slug', 'extracto', 'contenido', 'imagen_destacada',
        'categoria_id', 'estado', 'fecha_publicacion', 'vistas'
    ];
    
    // Construir la consulta dinámicamente
    foreach ($data as $key => $value) {
        if (in_array($key, $allowedFields)) {
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
        error_log("Error al actualizar artículo: " . $e->getMessage());
        return false;
    }
}

/**
 * Elimina un artículo
 * 
 * @param int $articleId ID del artículo a eliminar
 * @return bool Verdadero si se eliminó correctamente, falso en caso contrario
 */
function deleteArticle($articleId) {
    $table = DB::table('articulos');
    
    try {
        $stmt = DB::query("DELETE FROM $table WHERE id = ?", [$articleId]);
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        error_log("Error al eliminar artículo: " . $e->getMessage());
        return false;
    }
}

/**
 * Incrementa el contador de vistas de un artículo
 * 
 * @param int $articleId ID del artículo
 * @return bool Verdadero si se actualizó correctamente, falso en caso contrario
 */
function incrementArticleViews($articleId) {
    $table = DB::table('articulos');
    
    try {
        $sql = "UPDATE $table SET vistas = vistas + 1 WHERE id = ?";
        $stmt = DB::query($sql, [$articleId]);
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        error_log("Error al incrementar vistas del artículo: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtiene todas las categorías
 * 
 * @param bool $withCount Si es verdadero, incluye el recuento de artículos por categoría
 * @return array Lista de categorías
 */
function getAllCategories($withCount = false) {
    $tableCategories = DB::table('categorias');
    $tableArticles = DB::table('articulos');
    
    if ($withCount) {
        $sql = "SELECT 
                    c.*, 
                    COUNT(a.id) as total_articulos
                FROM $tableCategories c
                LEFT JOIN $tableArticles a ON c.id = a.categoria_id AND a.estado = 'publicado'
                GROUP BY c.id
                ORDER BY c.nombre ASC";
    } else {
        $sql = "SELECT * FROM $tableCategories ORDER BY nombre ASC";
    }
    
    return DB::fetchAll($sql);
}

/**
 * Obtiene una categoría por su ID
 * 
 * @param int $categoryId ID de la categoría
 * @return array|false Datos de la categoría o false si no se encuentra
 */
function getCategoryById($categoryId) {
    $table = DB::table('categorias');
    return DB::fetch("SELECT * FROM $table WHERE id = ?", [$categoryId]);
}

/**
 * Obtiene una categoría por su slug
 * 
 * @param string $slug Slug de la categoría
 * @return array|false Datos de la categoría o false si no se encuentra
 */
function getCategoryBySlug($slug) {
    $table = DB::table('categorias');
    return DB::fetch("SELECT * FROM $table WHERE slug = ?", [$slug]);
}

/**
 * Crea una nueva categoría
 * 
 * @param array $data Datos de la categoría
 * @return int|false ID de la nueva categoría o false en caso de error
 */
function createCategory($data) {
    $table = DB::table('categorias');
    $now = date('Y-m-d H:i:s');
    
    $sql = "INSERT INTO $table (
        nombre, slug, descripcion, 
        fecha_creacion, fecha_actualizacion
    ) VALUES (
        :nombre, :slug, :descripcion,
        :fecha_creacion, :fecha_actualizacion
    )";
    
    $params = [
        ':nombre' => $data['nombre'] ?? '',
        ':slug' => $data['slug'] ?? slugify($data['nombre'] ?? ''),
        ':descripcion' => $data['descripcion'] ?? '',
        ':fecha_creacion' => $now,
        ':fecha_actualizacion' => $now
    ];
    
    try {
        DB::query($sql, $params);
        return DB::lastInsertId();
    } catch (Exception $e) {
        error_log("Error al crear categoría: " . $e->getMessage());
        return false;
    }
}

/**
 * Actualiza una categoría existente
 * 
 * @param int $categoryId ID de la categoría a actualizar
 * @param array $data Datos a actualizar
 * @return bool Verdadero si se actualizó correctamente, falso en caso contrario
 */
function updateCategory($categoryId, $data) {
    $table = DB::table('categorias');
    $updates = [];
    $params = [':id' => $categoryId];
    
    // Campos permitidos para actualizar
    $allowedFields = ['nombre', 'slug', 'descripcion'];
    
    // Construir la consulta dinámicamente
    foreach ($data as $key => $value) {
        if (in_array($key, $allowedFields)) {
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
        error_log("Error al actualizar categoría: " . $e->getMessage());
        return false;
    }
}

/**
 * Elimina una categoría
 * 
 * @param int $categoryId ID de la categoría a eliminar
 * @return bool Verdadero si se eliminó correctamente, falso en caso contrario
 */
function deleteCategory($categoryId) {
    $table = DB::table('categorias');
    
    try {
        // Verificar si hay artículos asociados a esta categoría
        $count = (int)DB::fetchColumn(
            "SELECT COUNT(*) FROM " . DB::table('articulos') . " WHERE categoria_id = ?", 
            [$categoryId]
        );
        
        if ($count > 0) {
            // Si hay artículos asociados, no se puede eliminar
            return false;
        }
        
        // Si no hay artículos asociados, eliminar la categoría
        $stmt = DB::query("DELETE FROM $table WHERE id = ?", [$categoryId]);
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        error_log("Error al eliminar categoría: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtiene todos los comentarios
 * 
 * @param array $filters Filtros para la consulta (opcional)
 * @param int $limit Límite de resultados (opcional)
 * @param int $offset Desplazamiento (opcional)
 * @return array Lista de comentarios con información del artículo y del usuario
 */
function getAllComments($filters = [], $limit = null, $offset = 0) {
    $tableComments = DB::table('comentarios');
    $tableArticles = DB::table('articulos');
    $tableUsers = DB::table('usuarios');
    
    $sql = "SELECT 
                c.*, 
                a.titulo as articulo_titulo, a.slug as articulo_slug,
                u.nombre as autor_nombre, u.email as autor_email
            FROM $tableComments c
            LEFT JOIN $tableArticles a ON c.articulo_id = a.id
            LEFT JOIN $tableUsers u ON c.usuario_id = u.id
            WHERE 1=1";
    
    $params = [];
    
    // Aplicar filtros
    if (!empty($filters['articulo_id'])) {
        $sql .= " AND c.articulo_id = :articulo_id";
        $params[':articulo_id'] = $filters['articulo_id'];
    }
    
    if (!empty($filters['usuario_id'])) {
        $sql .= " AND c.usuario_id = :usuario_id";
        $params[':usuario_id'] = $filters['usuario_id'];
    }
    
    if (!empty($filters['estado'])) {
        $sql .= " AND c.estado = :estado";
        $params[':estado'] = $filters['estado'];
    }
    
    if (!empty($filters['busqueda'])) {
        $sql .= " AND (c.contenido LIKE :busqueda OR c.autor_nombre LIKE :busqueda OR c.autor_email LIKE :busqueda)";
        $params[':busqueda'] = "%{$filters['busqueda']}%";
    }
    
    // Ordenar por fecha de creación (más recientes primero)
    $sql .= " ORDER BY c.fecha_creacion DESC";
    
    // Limitar resultados
    if ($limit !== null) {
        $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
    }
    
    return DB::fetchAll($sql, $params);
}

/**
 * Cuenta el número total de comentarios que coinciden con los filtros
 * 
 * @param array $filters Filtros para la consulta (opcional)
 * @return int Número total de comentarios
 */
function countComments($filters = []) {
    $table = DB::table('comentarios');
    
    $sql = "SELECT COUNT(*) as total FROM $table WHERE 1=1";
    $params = [];
    
    // Aplicar filtros (los mismos que en getAllComments)
    if (!empty($filters['articulo_id'])) {
        $sql .= " AND articulo_id = :articulo_id";
        $params[':articulo_id'] = $filters['articulo_id'];
    }
    
    if (!empty($filters['usuario_id'])) {
        $sql .= " AND usuario_id = :usuario_id";
        $params[':usuario_id'] = $filters['usuario_id'];
    }
    
    if (!empty($filters['estado'])) {
        $sql .= " AND estado = :estado";
        $params[':estado'] = $filters['estado'];
    }
    
    if (!empty($filters['busqueda'])) {
        $sql .= " AND (contenido LIKE :busqueda OR autor_nombre LIKE :busqueda OR autor_email LIKE :busqueda)";
        $params[':busqueda'] = "%{$filters['busqueda']}%";
    }
    
    $result = DB::fetch($sql, $params);
    return (int)($result['total'] ?? 0);
}

/**
 * Obtiene un comentario por su ID
 * 
 * @param int $commentId ID del comentario
 * @return array|false Datos del comentario o false si no se encuentra
 */
function getCommentById($commentId) {
    $table = DB::table('comentarios');
    return DB::fetch("SELECT * FROM $table WHERE id = ?", [$commentId]);
}

/**
 * Crea un nuevo comentario
 * 
 * @param array $data Datos del comentario
 * @return int|false ID del nuevo comentario o false en caso de error
 */
function createComment($data) {
    $table = DB::table('comentarios');
    $now = date('Y-m-d H:i:s');
    
    $sql = "INSERT INTO $table (
        articulo_id, usuario_id, contenido, autor_nombre, autor_email,
        estado, fecha_creacion, fecha_actualizacion
    ) VALUES (
        :articulo_id, :usuario_id, :contenido, :autor_nombre, :autor_email,
        :estado, :fecha_creacion, :fecha_actualizacion
    )";
    
    $params = [
        ':articulo_id' => $data['articulo_id'] ?? null,
        ':usuario_id' => $data['usuario_id'] ?? null,
        ':contenido' => $data['contenido'] ?? '',
        ':autor_nombre' => $data['autor_nombre'] ?? null,
        ':autor_email' => $data['autor_email'] ?? null,
        ':estado' => $data['estado'] ?? 'pendiente',
        ':fecha_creacion' => $now,
        ':fecha_actualizacion' => $now
    ];
    
    try {
        DB::query($sql, $params);
        return DB::lastInsertId();
    } catch (Exception $e) {
        error_log("Error al crear comentario: " . $e->getMessage());
        return false;
    }
}

/**
 * Actualiza un comentario existente
 * 
 * @param int $commentId ID del comentario a actualizar
 * @param array $data Datos a actualizar
 * @return bool Verdadero si se actualizó correctamente, falso en caso contrario
 */
function updateComment($commentId, $data) {
    $table = DB::table('comentarios');
    $updates = [];
    $params = [':id' => $commentId];
    
    // Campos permitidos para actualizar
    $allowedFields = ['contenido', 'estado'];
    
    // Construir la consulta dinámicamente
    foreach ($data as $key => $value) {
        if (in_array($key, $allowedFields)) {
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
        error_log("Error al actualizar comentario: " . $e->getMessage());
        return false;
    }
}

/**
 * Elimina un comentario
 * 
 * @param int $commentId ID del comentario a eliminar
 * @return bool Verdadero si se eliminó correctamente, falso en caso contrario
 */
function deleteComment($commentId) {
    $table = DB::table('comentarios');
    
    try {
        $stmt = DB::query("DELETE FROM $table WHERE id = ?", [$commentId]);
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        error_log("Error al eliminar comentario: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtiene los comentarios de un artículo
 * 
 * @param int $articleId ID del artículo
 * @param bool $onlyApproved Si es verdadero, solo devuelve los comentarios aprobados
 * @return array Lista de comentarios
 */
function getArticleComments($articleId, $onlyApproved = true) {
    $table = DB::table('comentarios');
    
    $sql = "SELECT c.*, u.nombre as autor_nombre, u.avatar as autor_avatar 
            FROM $table c
            LEFT JOIN " . DB::table('usuarios') . " u ON c.usuario_id = u.id
            WHERE c.articulo_id = ?";
    
    $params = [$articleId];
    
    if ($onlyApproved) {
        $sql .= " AND c.estado = 'aprobado'";
    }
    
    $sql .= " ORDER BY c.fecha_creacion ASC";
    
    return DB::fetchAll($sql, $params);
}

/**
 * Obtiene las estadísticas del sitio
 * 
 * @return array Estadísticas del sitio
 */
function getSiteStats() {
    return [
        'total_articulos' => (int)DB::fetchColumn("SELECT COUNT(*) FROM " . DB::table('articulos')),
        'articulos_publicados' => (int)DB::fetchColumn("SELECT COUNT(*) FROM " . DB::table('articulos') . " WHERE estado = 'publicado'"),
        'total_usuarios' => (int)DB::fetchColumn("SELECT COUNT(*) FROM " . DB::table('usuarios')),
        'total_comentarios' => (int)DB::fetchColumn("SELECT COUNT(*) FROM " . DB::table('comentarios')),
        'comentarios_pendientes' => (int)DB::fetchColumn("SELECT COUNT(*) FROM " . DB::table('comentarios') . " WHERE estado = 'pendiente'"),
        'total_categorias' => (int)DB::fetchColumn("SELECT COUNT(*) FROM " . DB::table('categorias')),
    ];
}

/**
 * Obtiene los artículos más populares
 * 
 * @param int $limit Número máximo de artículos a devolver (por defecto: 5)
 * @return array Lista de artículos populares
 */
function getPopularArticles($limit = 5) {
    $table = DB::table('articulos');
    $sql = "SELECT id, titulo, slug, vistas, fecha_publicacion 
            FROM $table 
            WHERE estado = 'publicado' 
            ORDER BY vistas DESC, fecha_publicacion DESC 
            LIMIT " . (int)$limit;
    
    return DB::fetchAll($sql);
}

/**
 * Busca artículos por término de búsqueda
 * 
 * @param string $search Término de búsqueda
 * @param int $limit Límite de resultados (opcional)
 * @param int $offset Desplazamiento (opcional)
 * @return array Lista de artículos que coinciden con la búsqueda
 */
function searchArticles($search, $limit = null, $offset = 0) {
    $table = DB::table('articulos');
    $sql = "SELECT * FROM $table 
            WHERE (titulo LIKE :search OR contenido LIKE :search OR extracto LIKE :search)
            AND estado = 'publicado'
            ORDER BY fecha_publicacion DESC";
    
    $params = [':search' => "%$search%"];
    
    if ($limit !== null) {
        $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
    }
    
    return DB::fetchAll($sql, $params);
}

/**
 * Cuenta el número total de artículos que coinciden con un término de búsqueda
 * 
 * @param string $search Término de búsqueda
 * @return int Número total de artículos que coinciden con la búsqueda
 */
function countSearchArticles($search) {
    $table = DB::table('articulos');
    $sql = "SELECT COUNT(*) as total FROM $table 
            WHERE (titulo LIKE :search OR contenido LIKE :search OR extracto LIKE :search)
            AND estado = 'publicado'";
    
    $result = DB::fetch($sql, [':search' => "%$search%"]);
    return (int)($result['total'] ?? 0);
}

/**
 * Obtiene los artículos recientes
 * 
 * @param int $limit Número máximo de artículos a devolver (por defecto: 5)
 * @return array Lista de artículos recientes
 */
function getRecentArticles($limit = 5) {
    $table = DB::table('articulos');
    $sql = "SELECT id, titulo, slug, fecha_publicacion, imagen_destacada 
            FROM $table 
            WHERE estado = 'publicado' 
            ORDER BY fecha_publicacion DESC 
            LIMIT " . (int)$limit;
    
    return DB::fetchAll($sql);
}

/**
 * Obtiene los artículos por categoría
 * 
 * @param int $categoryId ID de la categoría
 * @param int $limit Límite de resultados (opcional)
 * @param int $offset Desplazamiento (opcional)
 * @return array Lista de artículos de la categoría
 */
function getArticlesByCategory($categoryId, $limit = null, $offset = 0) {
    $table = DB::table('articulos');
    $sql = "SELECT * FROM $table 
            WHERE categoria_id = :category_id 
            AND estado = 'publicado'
            ORDER BY fecha_publicacion DESC";
    
    $params = [':category_id' => $categoryId];
    
    if ($limit !== null) {
        $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
    }
    
    return DB::fetchAll($sql, $params);
}

/**
 * Cuenta el número total de artículos en una categoría
 * 
 * @param int $categoryId ID de la categoría
 * @return int Número total de artículos en la categoría
 */
function countArticlesByCategory($categoryId) {
    $table = DB::table('articulos');
    $sql = "SELECT COUNT(*) as total FROM $table 
            WHERE categoria_id = :category_id 
            AND estado = 'publicado'";
    
    $result = DB::fetch($sql, [':category_id' => $categoryId]);
    return (int)($result['total'] ?? 0);
}

/**
 * Obtiene los artículos por etiqueta
 * 
 * @param string $tag Nombre de la etiqueta
 * @param int $limit Límite de resultados (opcional)
 * @param int $offset Desplazamiento (opcional)
 * @return array Lista de artículos con la etiqueta
 */
function getArticlesByTag($tag, $limit = null, $offset = 0) {
    $tableArticles = DB::table('articulos');
    $tableTags = DB::table('tags');
    $tableArticleTags = DB::table('articulo_tags');
    
    $sql = "SELECT DISTINCT a.* 
            FROM $tableArticles a
            INNER JOIN $tableArticleTags at ON a.id = at.articulo_id
            INNER JOIN $tableTags t ON at.tag_id = t.id
            WHERE t.nombre = :tag 
            AND a.estado = 'publicado'
            ORDER BY a.fecha_publicacion DESC";
    
    $params = [':tag' => $tag];
    
    if ($limit !== null) {
        $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
    }
    
    return DB::fetchAll($sql, $params);
}

/**
 * Cuenta el número total de artículos con una etiqueta
 * 
 * @param string $tag Nombre de la etiqueta
 * @return int Número total de artículos con la etiqueta
 */
function countArticlesByTag($tag) {
    $tableArticles = DB::table('articulos');
    $tableTags = DB::table('tags');
    $tableArticleTags = DB::table('articulo_tags');
    
    $sql = "SELECT COUNT(DISTINCT a.id) as total 
            FROM $tableArticles a
            INNER JOIN $tableArticleTags at ON a.id = at.articulo_id
            INNER JOIN $tableTags t ON at.tag_id = t.id
            WHERE t.nombre = :tag 
            AND a.estado = 'publicado'";
    
    $result = DB::fetch($sql, [':tag' => $tag]);
    return (int)($result['total'] ?? 0);
}

/**
 * Obtiene las etiquetas más populares
 * 
 * @param int $limit Número máximo de etiquetas a devolver (por defecto: 10)
 * @return array Lista de etiquetas populares con su recuento
 */
function getPopularTags($limit = 10) {
    $tableTags = DB::table('tags');
    $tableArticleTags = DB::table('articulo_tags');
    $tableArticles = DB::table('articulos');
    
    $sql = "SELECT t.id, t.nombre, t.slug, COUNT(at.articulo_id) as total_articulos
            FROM $tableTags t
            INNER JOIN $tableArticleTags at ON t.id = at.tag_id
            INNER JOIN $tableArticles a ON at.articulo_id = a.id AND a.estado = 'publicado'
            GROUP BY t.id
            ORDER BY total_articulos DESC, t.nombre ASC
            LIMIT " . (int)$limit;
    
    return DB::fetchAll($sql);
}
