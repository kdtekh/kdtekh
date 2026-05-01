<?php
/**
 * Configuración de rutas para el panel de administración
 */

// Verificar si la sesión está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir archivos necesarios
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth-functions.php';
require_once __DIR__ . '/functions.php';

// Definir rutas públicas (no requieren autenticación)
$publicRoutes = [
    '/admin/login.php',
    '/admin/forgot-password.php',
    '/admin/reset-password.php',
    '/admin/includes/ajax-handler.php',
    '/admin/includes/upload-handler.php'
];

// Obtener la ruta actual
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Verificar si la ruta actual es pública
$isPublicRoute = false;
foreach ($publicRoutes as $route) {
    if (strpos($currentPath, $route) !== false) {
        $isPublicRoute = true;
        break;
    }
}

// Redirigir a la página de inicio de sesión si el usuario no está autenticado y la ruta no es pública
if (!$isPublicRoute && !isUserLoggedIn()) {
    $_SESSION['redirect_url'] = $currentPath;
    header('Location: ' . ADMIN_URL . '/login.php');
    exit();
}

// Definir rutas del panel de administración
$adminRoutes = [
    'dashboard' => [
        'path' => '/admin/',
        'title' => 'Panel de Control',
        'icon' => 'fas fa-tachometer-alt',
        'permission' => 'view_dashboard',
        'children' => []
    ],
    'articles' => [
        'path' => '/admin/articles.php',
        'title' => 'Artículos',
        'icon' => 'fas fa-newspaper',
        'permission' => 'view_articles',
        'children' => [
            'all_articles' => [
                'path' => '/admin/articles.php',
                'title' => 'Todos los Artículos',
                'permission' => 'view_articles'
            ],
            'add_article' => [
                'path' => '/admin/articles.php?action=add',
                'title' => 'Añadir Nuevo',
                'permission' => 'add_articles'
            ],
            'categories' => [
                'path' => '/admin/categories.php',
                'title' => 'Categorías',
                'permission' => 'view_categories'
            ],
            'tags' => [
                'path' => '/admin/tags.php',
                'title' => 'Etiquetas',
                'permission' => 'view_tags'
            ]
        ]
    ],
    'pages' => [
        'path' => '/admin/pages.php',
        'title' => 'Páginas',
        'icon' => 'fas fa-file-alt',
        'permission' => 'view_pages',
        'children' => [
            'all_pages' => [
                'path' => '/admin/pages.php',
                'title' => 'Todas las Páginas',
                'permission' => 'view_pages'
            ],
            'add_page' => [
                'path' => '/admin/pages.php?action=add',
                'title' => 'Añadir Nueva',
                'permission' => 'add_pages'
            ]
        ]
    ],
    'media' => [
        'path' => '/admin/media.php',
        'title' => 'Medios',
        'icon' => 'fas fa-images',
        'permission' => 'view_media',
        'children' => [
            'library' => [
                'path' => '/admin/media.php',
                'title' => 'Biblioteca de Medios',
                'permission' => 'view_media'
            ],
            'add_media' => [
                'path' => '/admin/media.php?action=add',
                'title' => 'Añadir Nuevo',
                'permission' => 'upload_media'
            ]
        ]
    ],
    'comments' => [
        'path' => '/admin/comments.php',
        'title' => 'Comentarios',
        'icon' => 'fas fa-comments',
        'permission' => 'moderate_comments',
        'badge' => function() {
            $count = DB::fetchColumn(
                "SELECT COUNT(*) FROM comments 
                 WHERE status = 'pending'"
            );
            return $count > 0 ? $count : null;
        },
        'children' => [
            'all_comments' => [
                'path' => '/admin/comments.php',
                'title' => 'Todos los Comentarios',
                'permission' => 'view_comments'
            ],
            'pending' => [
                'path' => '/admin/comments.php?status=pending',
                'title' => 'Pendientes de Moderación',
                'permission' => 'moderate_comments',
                'badge' => function() {
                    $count = DB::fetchColumn(
                        "SELECT COUNT(*) FROM comments 
                         WHERE status = 'pending'"
                    );
                    return $count > 0 ? $count : null;
                }
            ],
            'approved' => [
                'path' => '/admin/comments.php?status=approved',
                'title' => 'Aprobados',
                'permission' => 'view_comments'
            ],
            'spam' => [
                'path' => '/admin/comments.php?status=spam',
                'title' => 'Spam',
                'permission' => 'moderate_comments'
            ],
            'trash' => [
                'path' => '/admin/comments.php?status=trash',
                'title' => 'Papelera',
                'permission' => 'delete_comments'
            ]
        ]
    ],
    'users' => [
        'path' => '/admin/users.php',
        'title' => 'Usuarios',
        'icon' => 'fas fa-users',
        'permission' => 'view_users',
        'children' => [
            'all_users' => [
                'path' => '/admin/users.php',
                'title' => 'Todos los Usuarios',
                'permission' => 'view_users'
            ],
            'add_user' => [
                'path' => '/admin/users.php?action=add',
                'title' => 'Añadir Nuevo',
                'permission' => 'add_users'
            ],
            'profile' => [
                'path' => '/admin/profile.php',
                'title' => 'Mi Perfil',
                'permission' => 'view_profile'
            ]
        ]
    ],
    'appearance' => [
        'path' => '/admin/appearance.php',
        'title' => 'Apariencia',
        'icon' => 'fas fa-paint-brush',
        'permission' => 'customize_appearance',
        'children' => [
            'themes' => [
                'path' => '/admin/appearance.php',
                'title' => 'Temas',
                'permission' => 'switch_themes'
            ],
            'menus' => [
                'path' => '/admin/appearance.php?tab=menus',
                'title' => 'Menús',
                'permission' => 'edit_theme_options'
            ],
            'widgets' => [
                'path' => '/admin/appearance.php?tab=widgets',
                'title' => 'Widgets',
                'permission' => 'edit_theme_options'
            ],
            'customize' => [
                'path' => '/admin/appearance.php?tab=customize',
                'title' => 'Personalizar',
                'permission' => 'customize'
            ]
        ]
    ],
    'plugins' => [
        'path' => '/admin/plugins.php',
        'title' => 'Complementos',
        'icon' => 'fas fa-plug',
        'permission' => 'activate_plugins',
        'children' => [
            'installed' => [
                'path' => '/admin/plugins.php',
                'title' => 'Complementos Instalados',
                'permission' => 'activate_plugins'
            ],
            'add_new' => [
                'path' => '/admin/plugins.php?action=add',
                'title' => 'Añadir Nuevo',
                'permission' => 'install_plugins'
            ],
            'editor' => [
                'path' => '/admin/plugins.php?action=editor',
                'title' => 'Editor',
                'permission' => 'edit_plugins'
            ]
        ]
    ],
    'settings' => [
        'path' => '/admin/settings.php',
        'title' => 'Ajustes',
        'icon' => 'fas fa-cog',
        'permission' => 'manage_options',
        'children' => [
            'general' => [
                'path' => '/admin/settings.php',
                'title' => 'Ajustes Generales',
                'permission' => 'manage_options'
            ],
            'writing' => [
                'path' => '/admin/settings.php?tab=writing',
                'title' => 'Escritura',
                'permission' => 'manage_options'
            ],
            'reading' => [
                'path' => '/admin/settings.php?tab=reading',
                'title' => 'Lectura',
                'permission' => 'manage_options'
            ],
            'discussion' => [
                'path' => '/admin/settings.php?tab=discussion',
                'title' => 'Comentarios',
                'permission' => 'manage_options'
            ],
            'media' => [
                'path' => '/admin/settings.php?tab=media',
                'title' => 'Medios',
                'permission' => 'manage_options'
            ],
            'permalinks' => [
                'path' => '/admin/settings.php?tab=permalinks',
                'title' => 'Enlaces Permanentes',
                'permission' => 'manage_options'
            ]
        ]
    ],
    'tools' => [
        'path' => '/admin/tools.php',
        'title' => 'Herramientas',
        'icon' => 'fas fa-tools',
        'permission' => 'manage_options',
        'children' => [
            'available_tools' => [
                'path' => '/admin/tools.php',
                'title' => 'Herramientas Disponibles',
                'permission' => 'manage_options'
            ],
            'import' => [
                'path' => '/admin/import.php',
                'title' => 'Importar',
                'permission' => 'import'
            ],
            'export' => [
                'path' => '/admin/export.php',
                'title' => 'Exportar',
                'permission' => 'export'
            ],
            'site_health' => [
                'path' => '/admin/site-health.php',
                'title' => 'Salud del Sitio',
                'permission' => 'view_site_health_checks'
            ],
            'export_personal_data' => [
                'path' => '/admin/export-personal-data.php',
                'title' => 'Exportar Datos Personales',
                'permission' => 'export_others_personal_data'
            ],
            'erase_personal_data' => [
                'path' => '/admin/erase-personal-data.php',
                'title' => 'Borrar Datos Personales',
                'permission' => 'erase_others_personal_data'
            ]
        ]
    ]
];

/**
 * Filtrar las rutas según los permisos del usuario
 * 
 * @param array $routes Rutas a filtrar
 * @return array Rutas filtradas
 */
function filterRoutesByPermission($routes) {
    global $adminRoutes;
    
    $filtered = [];
    
    foreach ($routes as $key => $route) {
        // Verificar si el usuario tiene permiso para ver esta ruta
        $hasPermission = true;
        
        if (isset($route['permission'])) {
            if (is_callable($route['permission'])) {
                $hasPermission = $route['permission']();
            } else {
                $hasPermission = hasPermission($route['permission']);
            }
        }
        
        if ($hasPermission) {
            // Filtrar subrutas
            if (!empty($route['children'])) {
                $route['children'] = filterRoutesByPermission($route['children']);
                
                // Si después de filtrar no hay subrutas, no mostrar el elemento principal
                if (empty($route['children'])) {
                    continue;
                }
            }
            
            // Calcular badge si es una función
            if (isset($route['badge']) && is_callable($route['badge'])) {
                $route['badge'] = $route['badge']();
            }
            
            $filtered[$key] = $route;
        }
    }
    
    return $filtered;
}

// Filtrar rutas según los permisos del usuario
$filteredAdminRoutes = isUserLoggedIn() ? filterRoutesByPermission($adminRoutes) : [];

/**
 * Obtener la ruta actual
 * 
 * @return string Ruta actual
 */
function getCurrentRoute() {
    $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $basePath = str_replace('/index.php', '', $_SERVER['SCRIPT_NAME']);
    $route = str_replace($basePath, '', $currentPath);
    
    // Eliminar parámetros de consulta
    $route = strtok($route, '?');
    
    // Asegurar que la ruta comience con /
    if (strpos($route, '/') !== 0) {
        $route = '/' . $route;
    }
    
    return $route;
}

/**
 * Verificar si una ruta está activa
 * 
 * @param string $path Ruta a verificar
 * @param bool $exact Si es true, la ruta debe coincidir exactamente
 * @return bool Verdadero si la ruta está activa, falso en caso contrario
 */
function isRouteActive($path, $exact = false) {
    $currentRoute = getCurrentRoute();
    
    if ($exact) {
        return $currentRoute === $path;
    }
    
    return strpos($currentRoute, $path) === 0;
}

/**
 * Redirigir a una URL
 * 
 * @param string $url URL a la que redirigir
 * @param int $statusCode Código de estado HTTP
 */
function redirect($url, $statusCode = 302) {
    header('Location: ' . $url, true, $statusCode);
    exit();
}

/**
 * Obtener la URL de una ruta
 * 
 * @param string $path Ruta relativa
 * @return string URL completa
 */
function route($path = '') {
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
    $basePath = str_replace('/index.php', '', $_SERVER['SCRIPT_NAME']);
    
    // Eliminar la parte de /admin si ya está en la ruta base
    if (strpos($path, '/admin/') === 0) {
        $path = substr($path, 6);
    }
    
    // Asegurar que la ruta comience con /
    if ($path !== '' && strpos($path, '/') !== 0) {
        $path = '/' . $path;
    }
    
    return $baseUrl . $basePath . $path;
}

/**
 * Obtener la URL de un archivo de activos
 * 
 * @param string $path Ruta relativa al archivo
 * @return string URL completa al archivo
 */
function asset($path) {
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
    $basePath = str_replace('/admin/index.php', '', $_SERVER['SCRIPT_NAME']);
    
    // Asegurar que la ruta comience con /
    if ($path !== '' && strpos($path, '/') !== 0) {
        $path = '/' . $path;
    }
    
    return $baseUrl . $basePath . '/assets' . $path;
}

// Definir constantes de rutas comunes
define('ADMIN_URL', rtrim(route('/admin'), '/'));
define('SITE_URL', rtrim(route(''), '/'));
define('ASSETS_URL', asset(''));

// Inicializar mensajes flash
if (!isset($_SESSION['flash_messages'])) {
    $_SESSION['flash_messages'] = [];
}

/**
 * Establecer un mensaje flash
 * 
 * @param string $message Mensaje a mostrar
 * @param string $type Tipo de mensaje (success, error, warning, info)
 * @param string $key Clave del mensaje (opcional)
 */
function setFlashMessage($message, $type = 'info', $key = 'default') {
    if (!isset($_SESSION['flash_messages'][$key])) {
        $_SESSION['flash_messages'][$key] = [];
    }
    
    $_SESSION['flash_messages'][$key][] = [
        'message' => $message,
        'type' => $type
    ];
}

/**
 * Obtener mensajes flash
 * 
 * @param string $key Clave del mensaje (opcional)
 * @param bool $clear Si es true, elimina los mensajes después de obtenerlos
 * @return array Mensajes flash
 */
function getFlashMessages($key = 'default', $clear = true) {
    $messages = [];
    
    if (isset($_SESSION['flash_messages'][$key])) {
        $messages = $_SESSION['flash_messages'][$key];
        
        if ($clear) {
            unset($_SESSION['flash_messages'][$key]);
        }
    }
    
    return $messages;
}

/**
 * Mostrar mensajes flash
 * 
 * @param string $key Clave del mensaje (opcional)
 * @param bool $clear Si es true, elimina los mensajes después de mostrarlos
 */
function displayFlashMessages($key = 'default', $clear = true) {
    $messages = getFlashMessages($key, $clear);
    
    if (!empty($messages)) {
        echo '<div class="flash-messages">';
        
        foreach ($messages as $message) {
            $type = htmlspecialchars($message['type'], ENT_QUOTES, 'UTF-8');
            $text = htmlspecialchars($message['message'], ENT_QUOTES, 'UTF-8');
            
            echo "<div class=\"alert alert-{$type} alert-dismissible fade show\" role=\"alert\">";
            echo $text;
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>';
            echo "</div>";
        }
        
        echo '</div>';
    }
}

/**
 * Verificar si hay mensajes flash
 * 
 * @param string $key Clave del mensaje (opcional)
 * @return bool Verdadero si hay mensajes, falso en caso contrario
 */
function hasFlashMessages($key = 'default') {
    return !empty($_SESSION['flash_messages'][$key]);
}

// Incluir el archivo de rutas personalizadas si existe
$customRoutesFile = __DIR__ . '/custom-routes.php';
if (file_exists($customRoutesFile)) {
    require_once $customRoutesFile;
}
