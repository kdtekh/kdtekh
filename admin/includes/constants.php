<?php
// Prevenir acceso directo
if (!defined('SITE_PATH')) {
    die('Acceso denegado');
}

// Definir la ruta base del sitio
define('BASE_PATH', dirname(dirname(__DIR__)));

// Definir rutas de directorios
define('INCLUDES_PATH', __DIR__ . '/');
define('CLASSES_PATH', __DIR__ . '/classes/');
define('TEMPLATES_PATH', __DIR__ . '/../templates/');
define('UPLOADS_PATH', __DIR__ . '/../uploads/');
define('LOGS_PATH', __DIR__ . '/../logs/');

// Definir rutas URL
define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/periodico-digital');
define('ADMIN_URL', BASE_URL . '/admin');
define('UPLOADS_URL', BASE_URL . '/uploads');

// Definir constantes de la aplicación
define('APP_NAME', 'KDTekh');
define('APP_VERSION', '1.0.0');
define('APP_AUTHOR', 'Tu Nombre');
define('APP_EMAIL', 'contacto@kdt.com');

// Definir constantes de base de datos
define('DB_PREFIX', 'kd_');

define('TABLE_USERS', DB_PREFIX . 'users');
define('TABLE_ROLES', DB_PREFIX . 'roles');
define('TABLE_PERMISSIONS', DB_PREFIX . 'permissions');
define('TABLE_ROLE_PERMISSIONS', DB_PREFIX . 'role_permissions');
define('TABLE_USER_ROLES', DB_PREFIX . 'user_roles');
define('TABLE_LOGIN_ATTEMPTS', DB_PREFIX . 'login_attempts');
define('TABLE_SESSIONS', DB_PREFIX . 'sessions');
define('TABLE_LOGS', DB_PREFIX . 'logs');
define('TABLE_SETTINGS', DB_PREFIX . 'settings');
define('TABLE_CONTACT_MESSAGES', DB_PREFIX . 'contact_messages');
define('TABLE_NEWSLETTER', DB_PREFIX . 'newsletter');

// Definir constantes de roles
define('ROLE_SUPER_ADMIN', 1);
define('ROLE_ADMIN', 2);
define('ROLE_EDITOR', 3);
define('ROLE_AUTHOR', 4);
define('ROLE_SUBSCRIBER', 5);

// Definir constantes de estado
define('STATUS_ACTIVE', 1);
define('STATUS_INACTIVE', 0);
define('STATUS_PENDING', 2);
define('STATUS_BANNED', 3);

// Definir constantes para mensajes
define('MESSAGE_SUCCESS', 'success');
define('MESSAGE_ERROR', 'danger');
define('MESSAGE_WARNING', 'warning');
define('MESSAGE_INFO', 'info');

// Definir constantes para tipos de archivo
define('FILE_TYPE_IMAGE', 1);
define('FILE_TYPE_DOCUMENT', 2);
define('FILE_TYPE_ARCHIVE', 3);

// Definir constantes para configuraciones
define('CONFIG_SITE', 'site');
define('CONFIG_EMAIL', 'email');
define('CONFIG_SOCIAL', 'social');
define('CONFIG_SEO', 'seo');

// Definir constantes para logs
define('LOG_LOGIN', 'login');
define('LOG_LOGOUT', 'logout');
define('LOG_CREATE', 'create');
define('LOG_UPDATE', 'update');
define('LOG_DELETE', 'delete');
define('LOG_ERROR', 'error');

// Definir constantes para notificaciones
define('NOTIFICATION_INFO', 'info');
define('NOTIFICATION_SUCCESS', 'success');
define('NOTIFICATION_WARNING', 'warning');
define('NOTIFICATION_ERROR', 'danger');

// Definir constantes para ordenación
define('SORT_ASC', 'ASC');
define('SORT_DESC', 'DESC');

// Definir constantes para paginación
define('ITEMS_PER_PAGE', 10);
define('ITEMS_PER_PAGE_OPTIONS', [10, 25, 50, 100]);

// Definir constantes para formatos de fecha
define('DATE_FORMAT', 'd/m/Y');
define('DATETIME_FORMAT', 'd/m/Y H:i:s');
define('TIME_FORMAT', 'H:i:s');

// Definir constantes para tamaños de archivo
define('KB', 1024);
define('MB', 1048576);
define('GB', 1073741824);

// Definir constantes para validación
define('MIN_PASSWORD_LENGTH', 8);
define('MAX_PASSWORD_LENGTH', 72);

// Definir constantes para tokens
define('TOKEN_EXPIRY', 3600); // 1 hora
define('REMEMBER_ME_EXPIRY', 2592000); // 30 días

// Definir constantes para caché
define('CACHE_TIME_SHORT', 300); // 5 minutos
define('CACHE_TIME_MEDIUM', 3600); // 1 hora
define('CACHE_TIME_LONG', 86400); // 1 día

// Definir constantes para idiomas
define('DEFAULT_LANGUAGE', 'es');
define('AVAILABLE_LANGUAGES', [
    'es' => 'Español',
    'en' => 'English',
    'pt' => 'Português'
]);

// Definir constantes para temas
define('DEFAULT_THEME', 'default');
define('AVAILABLE_THEMES', [
    'default' => 'Tema Predeterminado',
    'dark' => 'Tema Oscuro',
    'light' => 'Tema Claro'
]);

// Definir constantes para formatos de imagen permitidos
define('ALLOWED_IMAGE_TYPES', [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
    'image/webp' => 'webp'
]);

// Definir constantes para tamaños de imagen
define('IMAGE_THUMBNAIL_WIDTH', 150);
define('IMAGE_THUMBNAIL_HEIGHT', 150);
define('IMAGE_MEDIUM_WIDTH', 600);
define('IMAGE_MEDIUM_HEIGHT', 400);

// Definir constantes para roles de usuario
define('USER_ROLES', [
    ROLE_SUPER_ADMIN => 'Super Administrador',
    ROLE_ADMIN => 'Administrador',
    ROLE_EDITOR => 'Editor',
    ROLE_AUTHOR => 'Autor',
    ROLE_SUBSCRIBER => 'Suscriptor'
]);

// Definir constantes para estados de usuario
define('USER_STATUSES', [
    STATUS_ACTIVE => 'Activo',
    STATUS_INACTIVE => 'Inactivo',
    STATUS_PENDING => 'Pendiente',
    STATUS_BANNED => 'Bloqueado'
]);

// Definir constantes para tipos de contenido
define('CONTENT_TYPE_PAGE', 'page');
define('CONTENT_TYPE_POST', 'post');
define('CONTENT_TYPE_NEWS', 'news');

// Definir constantes para estados de contenido
define('CONTENT_STATUS_DRAFT', 'draft');
define('CONTENT_STATUS_PENDING', 'pending');
define('CONTENT_STATUS_PUBLISHED', 'published');
define('CONTENT_STATUS_ARCHIVED', 'archived');

// Definir constantes para comentarios
define('COMMENT_STATUS_APPROVED', 'approved');
define('COMMENT_STATUS_PENDING', 'pending');
define('COMMENT_STATUS_SPAM', 'spam');
define('COMMENT_STATUS_TRASH', 'trash');

// Definir constantes para medios
define('MEDIA_TYPE_IMAGE', 'image');
define('MEDIA_TYPE_DOCUMENT', 'document');
define('MEDIA_TYPE_VIDEO', 'video');
define('MEDIA_TYPE_AUDIO', 'audio');

// Definir constantes para configuraciones del sitio
define('SITE_TITLE', 'KDTekh - Periódico Digital');
define('SITE_DESCRIPTION', 'Tu fuente confiable de noticias y artículos');
define('SITE_KEYWORDS', 'noticias, artículos, periódico, digital, actualidad');

// Definir constantes para configuraciones de correo
define('MAIL_DRIVER', 'smtp'); // smtp, sendmail, mail
define('MAIL_ENCRYPTION', 'tls'); // tls o ssl
define('MAIL_PORT', 587);

// Definir constantes para redes sociales
define('SOCIAL_FACEBOOK', 'https://facebook.com/kdtekh');
define('SOCIAL_TWITTER', 'https://twitter.com/kdtekh');
define('SOCIAL_INSTAGRAM', 'https://instagram.com/kdtekh');
define('SOCIAL_LINKEDIN', 'https://linkedin.com/company/kdtekh');

// Definir constantes para SEO
define('META_DESCRIPTION_LENGTH', 160);
define('META_TITLE_LENGTH', 60);
define('META_KEYWORDS_COUNT', 10);

// Definir constantes para caché
define('CACHE_ENABLED', true);
define('CACHE_DIR', BASE_PATH . '/cache/');

// Definir constantes para depuración
define('DEBUG_MODE', true);
define('LOG_QUERIES', true);

// Definir constantes para seguridad
define('CSRF_PROTECTION', true);
define('XSS_PROTECTION', true);
define('PASSWORD_BCRYPT_COST', 12);

// Definir constantes para subida de archivos
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_FILE_TYPES', [
    'image/jpeg',
    'image/png',
    'image/gif',
    'image/webp',
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'text/plain',
    'application/zip',
    'application/x-rar-compressed'
]);

// Definir constantes para formatos de fecha y hora
define('DATE_FORMAT_MYSQL', 'Y-m-d');
define('DATETIME_FORMAT_MYSQL', 'Y-m-d H:i:s');
define('TIME_FORMAT_MYSQL', 'H:i:s');

// Definir constantes para formatos de visualización
define('DATE_FORMAT_DISPLAY', 'd/m/Y');
define('DATETIME_FORMAT_DISPLAY', 'd/m/Y H:i:s');
define('TIME_FORMAT_DISPLAY', 'H:i:s');

// Definir constantes para formatos de moneda
define('CURRENCY_SYMBOL', '$');
define('CURRENCY_CODE', 'COP');
define('DECIMAL_SEPARATOR', ',');
define('THOUSANDS_SEPARATOR', '.');

// Definir constantes para paginación
define('PAGINATION_RANGE', 5);

// Definir constantes para ordenamiento
define('DEFAULT_SORT_FIELD', 'created_at');
define('DEFAULT_SORT_ORDER', 'DESC');

// Definir constantes para caché de consultas
define('QUERY_CACHE_ENABLED', true);
define('QUERY_CACHE_LIFETIME', 300); // 5 minutos

// Definir constantes para caché de vistas
define('VIEW_CACHE_ENABLED', true);
define('VIEW_CACHE_LIFETIME', 3600); // 1 hora

// Definir constantes para caché de rutas
define('ROUTE_CACHE_ENABLED', true);
define('ROUTE_CACHE_LIFETIME', 86400); // 1 día

// Definir constantes para caché de configuraciones
define('CONFIG_CACHE_ENABLED', true);
define('CONFIG_CACHE_LIFETIME', 3600); // 1 hora

// Definir constantes para caché de traducciones
define('TRANSLATION_CACHE_ENABLED', true);
define('TRANSLATION_CACHE_LIFETIME', 86400); // 1 día
