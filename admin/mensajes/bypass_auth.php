<?php
// Habilitar visualización de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Forzar la autenticación
$_SESSION['usuario_id'] = 1;
$_SESSION['usuario_nombre'] = 'Administrador';
$_SESSION['usuario_email'] = 'admin@kdt.com';
$_SESSION['usuario_rol'] = 'admin';
$_SESSION['is_logged_in'] = true;
$_SESSION['last_activity'] = time();

// Mostrar estado de la sesión
echo "<h2>✅ Sesión forzada correctamente</h2>";
echo "<p>Ahora deberías poder acceder a la sección de mensajes.</p>";

echo "<h3>Estado de la sesión:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Redirigir al listado de mensajes después de 3 segundos
header("refresh:3;url=index.php");
echo "<p>Redirigiendo al listado de mensajes en 3 segundos...</p>";
?>

<style>
    body {
        font-family: Arial, sans-serif;
        line-height: 1.6;
        margin: 40px;
        color: #333;
    }
    pre {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 4px;
        border: 1px solid #ddd;
        overflow-x: auto;
    }
    h2, h3 {
        color: #2c3e50;
    }
    .success {
        color: #155724;
        background-color: #d4edda;
        border-color: #c3e6cb;
        padding: 10px 15px;
        border-radius: 4px;
        margin-bottom: 20px;
    }
</style>
