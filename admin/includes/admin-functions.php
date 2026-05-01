<?php
/**
 * Funciones específicas para el panel de administración
 */

// Incluir archivos necesarios
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

/**
 * Verifica si el usuario tiene permiso para acceder a una sección
 * 
 * @param string $permission Permiso requerido
 * @return bool Verdadero si tiene permiso, falso en caso contrario
 */
function hasPermission($permission) {
    // Si el usuario es administrador, tiene todos los permisos
    if (Auth::isAdmin()) {
        return true;
    }
    
    // Obtener el rol del usuario
    $userRole = Auth::user('rol');
    
    // Definir los permisos por rol
    $permissions = [
        'admin' => [
            'view_dashboard',
            'manage_users',
            'manage_roles',
            'manage_settings',
            'manage_articles',
            'manage_categories',
            'manage_comments',
            'manage_media',
            'view_reports',
            'export_data'
        ],
        'editor' => [
            'view_dashboard',
            'manage_articles',
            'manage_categories',
            'manage_comments',
            'manage_media',
            'view_reports',
        ],
        'author' => [
            'view_dashboard',
            'manage_own_articles',
            'manage_media'
        ],
        'subscriber' => [
            'view_dashboard',
            'manage_own_profile'
        ]
    ];
    
    // Verificar si el rol existe y tiene el permiso
    return isset($permissions[$userRole]) && in_array($permission, $permissions[$userRole]);
}

/**
 * Verifica si el usuario tiene permiso y redirige si no lo tiene
 * 
 * @param string $permission Permiso requerido
 * @param string $redirectUrl URL a la que redirigir si no tiene permiso
 * @return void
 */
function requirePermission($permission, $redirectUrl = null) {
    if (!hasPermission($permission)) {
        if ($redirectUrl === null) {
            $redirectUrl = ADMIN_URL . '/index.php';
        }
        
        setFlashMessage('No tiene permiso para acceder a esta sección.', 'error');
        header('Location: ' . $redirectUrl);
        exit();
    }
}

/**
 * Obtiene las estadísticas del panel de administración
 * 
 * @return array Array con las estadísticas
 */
function getAdminStats() {
    $stats = [
        'total_articles' => 0,
        'total_categories' => 0,
        'total_comments' => 0,
        'total_users' => 0,
        'pending_comments' => 0,
        'pending_articles' => 0,
        'recent_articles' => [],
        'recent_comments' => [],
        'popular_articles' => []
    ];
    
    try {
        // Obtener estadísticas básicas
        $stats['total_articles'] = DB::fetchColumn("SELECT COUNT(*) FROM " . DB::table('articulos'));
        $stats['total_categories'] = DB::fetchColumn("SELECT COUNT(*) FROM " . DB::table('categorias'));
        $stats['total_comments'] = DB::fetchColumn("SELECT COUNT(*) FROM " . DB::table('comentarios'));
        $stats['total_users'] = DB::fetchColumn("SELECT COUNT(*) FROM " . DB::table('usuarios'));
        
        // Obtener comentarios pendientes
        $stats['pending_comments'] = DB::fetchColumn(
            "SELECT COUNT(*) FROM " . DB::table('comentarios') . " WHERE estado = 'pendiente'"
        );
        
        // Obtener artículos pendientes (solo si no es administrador)
        if (Auth::isAdmin()) {
            $stats['pending_articles'] = DB::fetchColumn(
                "SELECT COUNT(*) FROM " . DB::table('articulos') . " WHERE estado = 'pendiente'"
            );
        } else {
            $stats['pending_articles'] = DB::fetchColumn(
                "SELECT COUNT(*) FROM " . DB::table('articulos') . " 
                WHERE estado = 'pendiente' AND autor_id = ?",
                [Auth::id()]
            );
        }
        
        // Obtener artículos recientes
        $stats['recent_articles'] = DB::fetchAll(
            "SELECT a.*, u.nombre as autor, c.nombre as categoria 
            FROM " . DB::table('articulos') . " a 
            LEFT JOIN " . DB::table('usuarios') . " u ON a.autor_id = u.id 
            LEFT JOIN " . DB::table('categorias') . " c ON a.categoria_id = c.id 
            ORDER BY a.fecha_publicacion DESC LIMIT 5"
        );
        
        // Obtener comentarios recientes
        $stats['recent_comments'] = DB::fetchAll(
            "SELECT c.*, a.titulo as articulo_titulo, u.nombre as usuario 
            FROM " . DB::table('comentarios') . " c 
            LEFT JOIN " . DB::table('articulos') . " a ON c.articulo_id = a.id 
            LEFT JOIN " . DB::table('usuarios') . " u ON c.usuario_id = u.id 
            ORDER BY c.fecha_creacion DESC LIMIT 5"
        );
        
        // Obtener artículos populares (por vistas)
        $stats['popular_articles'] = DB::fetchAll(
            "SELECT a.*, u.nombre as autor, c.nombre as categoria 
            FROM " . DB::table('articulos') . " a 
            LEFT JOIN " . DB::table('usuarios') . " u ON a.autor_id = u.id 
            LEFT JOIN " . DB::table('categorias') . " c ON a.categoria_id = c.id 
            ORDER BY a.vistas DESC, a.fecha_publicacion DESC LIMIT 5"
        );
        
    } catch (Exception $e) {
        error_log("Error al obtener estadísticas del panel de administración: " . $e->getMessage());
    }
    
    return $stats;
}
