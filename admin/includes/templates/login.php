<?php
/**
 * Plantilla de inicio de sesión
 */

// Si el usuario ya está autenticado, redirigir al dashboard
if (isUserLoggedIn()) {
    redirect(ADMIN_URL . '/index.php');
}

// Procesar el formulario de inicio de sesión
$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar token CSRF
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de seguridad inválido. Por favor, intente de nuevo.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        // Validar campos
        if (empty($email) || empty($password)) {
            $error = 'Por favor, complete todos los campos.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Por favor, ingrese un correo electrónico válido.';
        } else {
            // Intentar autenticar al usuario
            $user = authenticateUser($email, $password);
            
            if ($user) {
                // Iniciar sesión
                loginUser($user, $remember);
                
                // Registrar el inicio de sesión
                logActivity($user['id'], 'Inicio de sesión exitoso', 'login');
                
                // Redirigir a la página solicitada o al dashboard
                $redirectUrl = $_SESSION['redirect_url'] ?? ADMIN_URL . '/index.php';
                unset($_SESSION['redirect_url']);
                
                // Establecer mensaje de bienvenida
                setFlashMessage(
                    '¡Bienvenido/a ' . htmlspecialchars($user['name']) . '!', 
                    'success'
                );
                
                redirect($redirectUrl);
            } else {
                $error = 'Correo electrónico o contraseña incorrectos.';
                
                // Registrar intento fallido
                logFailedLoginAttempt($email, $_SERVER['REMOTE_ADDR']);
            }
        }
    }
}

// Configurar el título de la página
$pageTitle = 'Iniciar sesión';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/plugins/fontawesome-free/css/all.min.css">
    <!-- icheck bootstrap -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/plugins/icheck-bootstrap/icheck-bootstrap.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/adminlte.min.css">
    <!-- Toastr -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/plugins/toastr/toastr.min.css">
    <!-- Custom styles -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/custom.css">
    
    <style>
        .login-page {
            background: linear-gradient(135deg, #007bff 0%, #00f2fe 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-box {
            width: 100%;
            max-width: 480px;
            margin: 0 auto;
        }
        .login-logo {
            margin-bottom: 1.5rem;
        }
        .login-logo a {
            color: #fff;
            font-size: 2.5rem;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        .login-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }
        .login-card-body {
            padding: 2.5rem;
            background-color: #fff;
        }
        .input-group-text {
            background-color: transparent;
        }
        .btn-login {
            padding: 0.6rem 2rem;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .social-auth-links {
            margin: 1.5rem 0;
        }
        .social-auth-links a {
            margin: 0 0.5rem;
            color: #6c757d;
            font-size: 1.5rem;
            transition: all 0.3s;
        }
        .social-auth-links a:hover {
            color: #007bff;
            transform: translateY(-3px);
        }
    </style>
</head>
<body class="hold-transition login-page">
<div class="login-box">
    <!-- Login Logo -->
    <div class="login-logo text-center">
        <a href="<?php echo SITE_URL; ?>">
            <img src="<?php echo ASSETS_URL; ?>/img/logo.png" alt="Logo" style="max-height: 80px; margin-bottom: 15px;">
            <div><?php echo SITE_NAME; ?></div>
        </a>
    </div>
    
    <!-- Login Card -->
    <div class="card login-card">
        <div class="card-body login-card-body">
            <p class="login-box-msg">Inicia sesión para acceder al panel de administración</p>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle mr-2"></i> <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['expired'])): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle mr-2"></i> Su sesión ha expirado. Por favor, inicie sesión nuevamente.
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['registered'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle mr-2"></i> ¡Registro exitoso! Por favor, inicie sesión con sus credenciales.
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['reset'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle mr-2"></i> Su contraseña ha sido restablecida. Por favor, inicie sesión con su nueva contraseña.
                </div>
            <?php endif; ?>
            
            <form action="<?php echo ADMIN_URL; ?>/login.php" method="post" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                
                <div class="input-group mb-3">
                    <input type="email" 
                           name="email" 
                           class="form-control form-control-lg" 
                           placeholder="Correo electrónico"
                           value="<?php echo htmlspecialchars($email); ?>"
                           required
                           autofocus>
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-envelope"></span>
                        </div>
                    </div>
                </div>
                
                <div class="input-group mb-3">
                    <input type="password" 
                           name="password" 
                           class="form-control form-control-lg" 
                           placeholder="Contraseña"
                           required>
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-lock"></span>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-8">
                        <div class="icheck-primary">
                            <input type="checkbox" id="remember" name="remember">
                            <label for="remember">
                                Recordarme
                            </label>
                        </div>
                    </div>
                    <!-- /.col -->
                    <div class="col-4">
                        <button type="submit" class="btn btn-primary btn-block btn-login">
                            <i class="fas fa-sign-in-alt mr-2"></i> Entrar
                        </button>
                    </div>
                    <!-- /.col -->
                </div>
            </form>
            
            <div class="social-auth-links text-center mb-3">
                <p>- O -</p>
                <a href="#" class="btn btn-block btn-danger" onclick="alert('Función de inicio de sesión con Google en desarrollo'); return false;">
                    <i class="fab fa-google-plus mr-2"></i> Iniciar con Google
                </a>
                <a href="#" class="btn btn-block btn-primary" onclick="alert('Función de inicio de sesión con Facebook en desarrollo'); return false;">
                    <i class="fab fa-facebook mr-2"></i> Iniciar con Facebook
                </a>
            </div>
            <!-- /.social-auth-links -->
            
            <p class="mb-1">
                <a href="<?php echo ADMIN_URL; ?>/forgot-password.php">¿Olvidaste tu contraseña?</a>
            </p>
            <p class="mb-0">
                <a href="<?php echo SITE_URL; ?>/register.php" class="text-center">Registrar una nueva membresía</a>
            </p>
        </div>
        <!-- /.login-card-body -->
        
        <div class="card-footer text-center">
            <small class="text-muted">
                &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Todos los derechos reservados.
                <br>
                <small>Versión <?php echo APP_VERSION; ?></small>
            </small>
        </div>
    </div>
</div>
<!-- /.login-box -->

<!-- jQuery -->
<script src="<?php echo ASSETS_URL; ?>/plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="<?php echo ASSETS_URL; ?>/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="<?php echo ASSETS_URL; ?>/js/adminlte.min.js"></script>
<!-- Toastr -->
<script src="<?php echo ASSETS_URL; ?>/plugins/toastr/toastr.min.js"></script>

<script>
$(function () {
    // Mostrar mensajes flash
    <?php if (hasFlashMessages()): ?>
        <?php foreach (getFlashMessages() as $message): ?>
            toastr.<?php echo $message['type']; ?>('<?php echo addslashes($message['message']); ?>');
        <?php endforeach; ?>
    <?php endif; ?>
    
    // Validación del formulario
    $('#loginForm').on('submit', function(e) {
        const email = $('input[name="email"]').val().trim();
        const password = $('input[name="password"]').val();
        
        if (!email) {
            e.preventDefault();
            toastr.error('Por favor, ingrese su correo electrónico.');
            $('input[name="email"]').focus();
            return false;
        }
        
        if (!isValidEmail(email)) {
            e.preventDefault();
            toastr.error('Por favor, ingrese un correo electrónico válido.');
            $('input[name="email"]').focus();
            return false;
        }
        
        if (!password) {
            e.preventDefault();
            toastr.error('Por favor, ingrese su contraseña.');
            $('input[name="password"]').focus();
            return false;
        }
        
        // Mostrar indicador de carga
        $('button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Iniciando sesión...');
    });
    
    // Función para validar email
    function isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    // Configuración de toastr
    toastr.options = {
        "closeButton": true,
        "debug": false,
        "newestOnTop": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "preventDuplicates": false,
        "onclick": null,
        "showDuration": "300",
        "hideDuration": "1000",
        "timeOut": "5000",
        "extendedTimeOut": "1000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    };
});
</script>

</body>
</html>
<?php
// Limpiar el buffer de salida y mostrarlo
ob_end_flush();
?>
