<?php
/**
 * Funciones específicas para el panel de administración (Parte 2)
 */

// Incluir archivos necesarios
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

/**
 * Obtiene el menú de navegación del panel de administración
 * 
 * @return array Array con la estructura del menú
 */
function getAdminMenu() {
    $menu = [
        'dashboard' => [
            'title' => 'Dashboard',
            'url' => ADMIN_URL . '/index.php',
            'icon' => 'tachometer-alt',
            'permission' => 'view_dashboard',
            'active' => basename($_SERVER['PHP_SELF']) === 'index.php'
        ],
        'content' => [
            'title' => 'Contenido',
            'icon' => 'file-alt',
            'permission' => 'manage_articles',
            'items' => [
                'articles' => [
                    'title' => 'Artículos',
                    'url' => ADMIN_URL . '/articles/index.php',
                    'icon' => 'newspaper',
                    'permission' => 'manage_articles',
                    'active' => strpos($_SERVER['PHP_SELF'], '/articles/') !== false
                ],
                'categories' => [
                    'title' => 'Categorías',
                    'url' => ADMIN_URL . '/categories/index.php',
                    'icon' => 'folder',
                    'permission' => 'manage_categories',
                    'active' => strpos($_SERVER['PHP_SELF'], '/categories/') !== false
                ],
                'comments' => [
                    'title' => 'Comentarios',
                    'url' => ADMIN_URL . '/comments/index.php',
                    'icon' => 'comments',
                    'permission' => 'manage_comments',
                    'active' => strpos($_SERVER['PHP_SELF'], '/comments/') !== false,
                    'badge' => function() {
                        $count = DB::fetchColumn(
                            "SELECT COUNT(*) FROM " . DB::table('comentarios') . " 
                            WHERE estado = 'pendiente'"
                        );
                        return $count > 0 ? $count : null;
                    }
                ],
                'media' => [
                    'title' => 'Medios',
                    'url' => ADMIN_URL . '/media/index.php',
                    'icon' => 'images',
                    'permission' => 'manage_media',
                    'active' => strpos($_SERVER['PHP_SELF'], '/media/') !== false
                ]
            ]
        ],
        'users' => [
            'title' => 'Usuarios',
            'icon' => 'users',
            'permission' => 'manage_users',
            'active' => strpos($_SERVER['PHP_SELF'], '/users/') !== false,
            'items' => [
                'all_users' => [
                    'title' => 'Todos los usuarios',
                    'url' => ADMIN_URL . '/users/index.php',
                    'icon' => 'users',
                    'permission' => 'manage_users',
                    'active' => basename($_SERVER['PHP_SELF']) === 'index.php' && 
                                 strpos($_SERVER['PHP_SELF'], '/users/') !== false
                ],
                'add_user' => [
                    'title' => 'Añadir nuevo',
                    'url' => ADMIN_URL . '/users/add.php',
                    'icon' => 'user-plus',
                    'permission' => 'manage_users',
                    'active' => basename($_SERVER['PHP_SELF']) === 'add.php' && 
                                 strpos($_SERVER['PHP_SELF'], '/users/') !== false
                ],
                'roles' => [
                    'title' => 'Roles y permisos',
                    'url' => ADMIN_URL . '/users/roles.php',
                    'icon' => 'user-shield',
                    'permission' => 'manage_roles',
                    'active' => basename($_SERVER['PHP_SELF']) === 'roles.php' && 
                                 strpos($_SERVER['PHP_SELF'], '/users/') !== false
                ]
            ]
        ],
        'appearance' => [
            'title' => 'Apariencia',
            'icon' => 'paint-brush',
            'permission' => 'manage_appearance',
            'active' => strpos($_SERVER['PHP_SELF'], '/appearance/') !== false,
            'items' => [
                'themes' => [
                    'title' => 'Temas',
                    'url' => ADMIN_URL . '/appearance/themes.php',
                    'icon' => 'palette',
                    'permission' => 'manage_appearance'
                ],
                'menus' => [
                    'title' => 'Menús',
                    'url' => ADMIN_URL . '/appearance/menus.php',
                    'icon' => 'bars',
                    'permission' => 'manage_appearance'
                ],
                'widgets' => [
                    'title' => 'Widgets',
                    'url' => ADMIN_URL . '/appearance/widgets.php',
                    'icon' => 'th-large',
                    'permission' => 'manage_appearance'
                ]
            ]
        ],
        'settings' => [
            'title' => 'Ajustes',
            'url' => ADMIN_URL . '/settings/general.php',
            'icon' => 'cog',
            'permission' => 'manage_settings',
            'active' => strpos($_SERVER['PHP_SELF'], '/settings/') !== false
        ],
        'tools' => [
            'title' => 'Herramientas',
            'icon' => 'tools',
            'permission' => 'manage_tools',
            'active' => strpos($_SERVER['PHP_SELF'], '/tools/') !== false,
            'items' => [
                'import' => [
                    'title' => 'Importar',
                    'url' => ADMIN_URL . '/tools/import.php',
                    'icon' => 'file-import',
                    'permission' => 'manage_tools'
                ],
                'export' => [
                    'title' => 'Exportar',
                    'url' => ADMIN_URL . '/tools/export.php',
                    'icon' => 'file-export',
                    'permission' => 'export_data'
                ],
                'backup' => [
                    'title' => 'Copia de seguridad',
                    'url' => ADMIN_URL . '/tools/backup.php',
                    'icon' => 'database',
                    'permission' => 'manage_tools'
                ],
                'logs' => [
                    'title' => 'Registros',
                    'url' => ADMIN_URL . '/tools/logs.php',
                    'icon' => 'clipboard-list',
                    'permission' => 'view_reports'
                ]
            ]
        ]
    ];
    
    // Filtrar menús según permisos
    foreach ($menu as $key => $item) {
        // Verificar si el ítem principal tiene permiso
        if (isset($item['permission']) && !hasPermission($item['permission'])) {
            unset($menu[$key]);
            continue;
        }
        
        // Filtrar submenús
        if (isset($item['items']) && is_array($item['items'])) {
            foreach ($item['items'] as $subKey => $subItem) {
                if (isset($subItem['permission']) && !hasPermission($subItem['permission'])) {
                    unset($menu[$key]['items'][$subKey]);
                }
            }
            
            // Si no hay submenús después de filtrar, eliminar el ítem principal
            if (empty($menu[$key]['items'])) {
                unset($menu[$key]);
            }
        }
    }
    
    return $menu;
}

/**
 * Obtiene el título de la página actual
 * 
 * @return string Título de la página
 */
function getPageTitle() {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $segments = explode('/', trim($path, '/'));
    
    // Eliminar el directorio 'admin' de los segmentos
    if (($key = array_search('admin', $segments)) !== false) {
        unset($segments[$key]);
        $segments = array_values($segments);
    }
    
    // Si no hay segmentos, es el dashboard
    if (empty($segments) || (count($segments) === 1 && $segments[0] === 'index.php')) {
        return 'Dashboard';
    }
    
    // Obtener el nombre del archivo sin la extensión
    $file = basename($segments[count($segments) - 1], '.php');
    
    // Mapear nombres de archivo a títulos
    $titles = [
        'index' => 'Listado',
        'add' => 'Añadir nuevo',
        'edit' => 'Editar',
        'view' => 'Ver',
        'categories' => 'Categorías',
        'comments' => 'Comentarios',
        'media' => 'Medios',
        'users' => 'Usuarios',
        'roles' => 'Roles y permisos',
        'settings' => 'Ajustes',
        'general' => 'Generales',
        'reading' => 'Lectura',
        'writing' => 'Escritura',
        'discussion' => 'Comentarios',
        'media' => 'Medios',
        'permalinks' => 'Enlaces permanentes',
        'themes' => 'Temas',
        'menus' => 'Menús',
        'widgets' => 'Widgets',
        'import' => 'Importar',
        'export' => 'Exportar',
        'backup' => 'Copia de seguridad',
        'logs' => 'Registros',
        'profile' => 'Perfil',
        'account' => 'Mi cuenta',
        'password' => 'Cambiar contraseña'
    ];
    
    // Si el archivo está en el mapeo, devolver el título correspondiente
    if (isset($titles[$file])) {
        return $titles[$file];
    }
    
    // Si no, devolver el nombre del archivo con la primera letra en mayúscula
    return ucfirst(str_replace(['-', '_'], ' ', $file));
}

/**
 * Obtiene las migas de pan (breadcrumbs) para la página actual
 * 
 * @return array Array con las migas de pan
 */
function getBreadcrumbs() {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $segments = explode('/', trim($path, '/'));
    
    // Eliminar el directorio 'admin' de los segmentos
    if (($key = array_search('admin', $segments)) !== false) {
        unset($segments[$key]);
        $segments = array_values($segments);
    }
    
    $breadcrumbs = [];
    $url = ADMIN_URL . '/';
    
    // Añadir enlace al dashboard
    $breadcrumbs[] = [
        'title' => 'Dashboard',
        'url' => $url,
        'active' => empty($segments) || (count($segments) === 1 && $segments[0] === 'index.php')
    ];
    
    // Si no hay más segmentos, devolver solo el dashboard
    if (empty($segments) || (count($segments) === 1 && $segments[0] === 'index.php')) {
        return $breadcrumbs;
    }
    
    // Procesar cada segmento
    $currentUrl = $url;
    
    foreach ($segments as $i => $segment) {
        // Saltar el archivo index.php
        if ($segment === 'index.php') {
            continue;
        }
        
        // Obtener el nombre del segmento sin la extensión
        $name = basename($segment, '.php');
        
        // Si es el último segmento, no es un enlace
        $isLast = ($i === count($segments) - 1);
        
        // Construir la URL
        $currentUrl .= $segment . '/';
        
        // Añadir miga de pan
        $breadcrumbs[] = [
            'title' => getPageTitle(),
            'url' => $isLast ? '' : rtrim($currentUrl, '/'),
            'active' => $isLast
        ];
        
        // Si es el último segmento, salir del bucle
        if ($isLast) {
            break;
        }
    }
    
    return $breadcrumbs;
}
