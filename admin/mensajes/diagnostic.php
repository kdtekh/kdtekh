<?php
// Script de diagnóstico para problemas de acceso

// Habilitar visualización de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/access_diagnostic.log');

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Función para verificar permisos de archivos
function checkFilePermissions($path) {
    $permissions = fileperms($path);
    return [
        'readable' => is_readable($path),
        'writable' => is_writable($path),
        'executable' => is_executable($path),
        'permissions' => substr(sprintf('%o', $permissions), -4)
    ];
}

// Recolectar información del sistema
$systemInfo = [
    'php_version' => phpversion(),
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
    'session_status' => session_status(),
    'session_id' => session_id(),
    'session_data' => $_SESSION ?? [],
    'cookies' => $_COOKIE ?? [],
    'headers' => getallheaders(),
    'file_permissions' => [
        'index.php' => checkFilePermissions(__DIR__ . '/index.php'),
        'auth_check.php' => checkFilePermissions(__DIR__ . '/auth_check.php'),
        '_config.php' => checkFilePermissions(__DIR__ . '/_config.php'),
        'session_dir' => checkFilePermissions(session_save_path())
    ]
];

// Mostrar información
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Diagnóstico de Acceso - Panel de Administración</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 20px; }
        .section { margin-bottom: 30px; border: 1px solid #ddd; padding: 15px; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Diagnóstico de Acceso</h1>
    
    <div class="section">
        <h2>Información del Sistema</h2>
        <p><strong>PHP Version:</strong> <?= htmlspecialchars($systemInfo['php_version']) ?></p>
        <p><strong>Servidor:</strong> <?= htmlspecialchars($systemInfo['server_software']) ?></p>
    </div>
    
    <div class="section">
        <h2>Estado de la Sesión</h2>
        <p><strong>ID de Sesión:</strong> <?= htmlspecialchars($systemInfo['session_id']) ?></p>
        <p><strong>Estado de Sesión:</strong> 
            <?php 
            switch($systemInfo['session_status']) {
                case PHP_SESSION_DISABLED: echo 'Deshabilitado'; break;
                case PHP_SESSION_NONE: echo 'No iniciada'; break;
                case PHP_SESSION_ACTIVE: echo 'Activa'; break;
                default: echo 'Desconocido';
            }
            ?>
        </p>
        <h3>Datos de Sesión:</h3>
        <pre><?= htmlspecialchars(print_r($systemInfo['session_data'], true)) ?></pre>
    </div>
    
    <div class="section">
        <h2>Permisos de Archivos</h2>
        <table border="1" cellpadding="5" cellspacing="0">
            <tr>
                <th>Archivo</th>
                <th>Legible</th>
                <th>Escribible</th>
                <th>Ejecutable</th>
                <th>Permisos</th>
            </tr>
            <?php foreach ($systemInfo['file_permissions'] as $file => $perms): ?>
            <tr>
                <td><?= htmlspecialchars($file) ?></td>
                <td class="<?= $perms['readable'] ? 'success' : 'error' ?>">
                    <?= $perms['readable'] ? 'Sí' : 'No' ?>
                </td>
                <td class="<?= $perms['writable'] ? 'success' : 'error' ?>">
                    <?= $perms['writable'] ? 'Sí' : 'No' ?>
                </td>
                <td class="<?= $perms['executable'] ? 'success' : 'error' ?>">
                    <?= $perms['executable'] ? 'Sí' : 'No' ?>
                </td>
                <td><code><?= htmlspecialchars($perms['permissions']) ?></code></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <div class="section">
        <h2>Acceso Temporal a Mensajes</h2>
        <p>Si necesitas acceder a los mensajes de forma temporal, puedes usar el siguiente enlace:</p>
        <p><a href="temporary_access.php" class="button">Acceso Temporal a Mensajes</a></p>
        <p class="note">Nota: Este enlace debe usarse solo para diagnóstico y debe eliminarse después de su uso.</p>
    </div>
    
    <div class="section">
        <h2>Información de Depuración</h2>
        <h3>Cookies:</h3>
        <pre><?= htmlspecialchars(print_r($systemInfo['cookies'], true)) ?></pre>
        
        <h3>Headers HTTP:</h3>
        <pre><?= htmlspecialchars(print_r($systemInfo['headers'], true)) ?></pre>
    </div>
</body>
</html>
