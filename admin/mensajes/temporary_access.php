<?php
// Archivo temporal para diagnóstico de acceso
// Este archivo debe ser eliminado después de su uso

// Habilitar visualización de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/temp_access.log');

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Forzar datos de sesión (solo para diagnóstico)
$_SESSION['usuario_id'] = 1;
$_SESSION['usuario_nombre'] = 'Admin Temporal';
$_SESSION['usuario_email'] = 'temp@kdtekh.com';
$_SESSION['usuario_rol'] = 'admin';

// Mostrar información de sesión
echo "<h2>Sesión temporal iniciada</h2>";
echo "<p>Ahora deberías poder acceder a los mensajes.</p>";
echo "<p><a href='index.php'>Ir a los mensajes</a></p>";

// Mostrar información de depuración
echo "<h3>Información de sesión:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Redirigir automáticamente después de 3 segundos
echo "<script>
    setTimeout(function() {
        window.location.href = 'index.php';
    }, 3000);
</script>";

// Nota: Este archivo debe ser eliminado después de su uso
?>
