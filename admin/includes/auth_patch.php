<?php
/**
 * Parche temporal para la autenticación
 * Este archivo debe ser incluido después de auth.php
 */

if (!function_exists('checkAuth')) {
    // Añadir método isLoggedIn a la clase Auth si no existe
    if (class_exists('Auth') && !method_exists('Auth', 'isLoggedIn')) {
        eval('class AuthExtension extends Auth {
            public static function isLoggedIn() {
                return !empty($_SESSION["usuario_id"]);
            }
        }');
        
        // Reemplazar la clase original con la extendida
        if (!class_exists('Auth', false)) {
            class_alias('AuthExtension', 'Auth');
        }
    }

    // Función para verificar la autenticación
    function checkAuth() {
        if (empty($_SESSION['usuario_id'])) {
            header('Location: /admin/login.php');
            exit;
        }
    }
}
?>
