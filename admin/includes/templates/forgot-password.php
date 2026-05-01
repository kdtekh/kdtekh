<?php
/**
 * Plantilla de recuperación de contraseña
 */

// Si el usuario ya está autenticado, redirigir al dashboard
if (isUserLoggedIn()) {
    redirect(ADMIN_URL . '/index.php');
}

// Variables
$error = '';
$success = false;
$email = '';

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar token CSRF
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de seguridad inválido. Por favor, intente de nuevo.';
    } else {
        $email = trim($_POST['email'] ?? '');
        
        // Validar correo electrónico
        if (empty($email)) {
            $error = 'Por favor, ingrese su dirección de correo electrónico.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Por favor, ingrese una dirección de correo electrónico válida.';
        } else {
            // Verificar si el correo existe en la base de datos
            $user = DB::fetch("SELECT id, name FROM users WHERE email = :email", [':email' => $email]);
            
            if (!$user) {
                // Por seguridad, no revelamos si el correo existe o no
                $success = true;
            } else {
                // Generar token de restablecimiento
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                try {
                    DB::beginTransaction();
                    
                    // Eliminar tokens anteriores para este correo
                    DB::query("DELETE FROM password_resets WHERE email = :email", [':email' => $email]);
                    
                    // Insertar nuevo token
                    DB::query(
                        "INSERT INTO password_resets (email, token, expires_at) VALUES (:email, :token, :expires_at)",
                        [
                            ':email' => $email,
                            ':token' => $token,
                            ':expires_at' => $expires
                        ]
                    );
                    
                    DB::commit();
                    
                    // Enviar correo electrónico con el enlace de restablecimiento
                    $resetLink = ADMIN_URL . "/reset-password.php?token=" . $token;
                    $subject = "Restablecer su contraseña - " . SITE_NAME;
                    
                    // Plantilla de correo electrónico
                    $message = "
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <meta charset='UTF-8'>
                        <title>Restablecer contraseña</title>
                        <style>
                            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                            .header { background-color: #4e73df; padding: 20px; text-align: center; color: white; }
                            .content { padding: 20px; background-color: #f9f9f9; }
                            .button { 
                                display: inline-block; 
                                padding: 10px 20px; 
                                background-color: #4e73df; 
                                color: white !important; 
                                text-decoration: none; 
                                border-radius: 4px; 
                                margin: 15px 0; 
                            }
                            .footer { 
                                margin-top: 20px; 
                                font-size: 12px; 
                                text-align: center; 
                                color: #777; 
                            }
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <div class='header'>
                                <h1>Restablecer contraseña</h1>
                            </div>
                            <div class='content'>
                                <p>Hola " . htmlspecialchars($user['name']) . ",</p>
                                <p>Hemos recibido una solicitud para restablecer la contraseña de su cuenta. Si no realizó esta solicitud, puede ignorar este correo electrónico.</p>
                                <p>Para restablecer su contraseña, haga clic en el siguiente botón:</p>
                                <p style='text-align: center;'>
                                    <a href='" . $resetLink . "' class='button'>Restablecer contraseña</a>
                                </p>
                                <p>O copie y pegue el siguiente enlace en su navegador:</p>
                                <p><a href='" . $resetLink . "'>" . $resetLink . "</a></p>
                                <p>Este enlace expirará en 1 hora por motivos de seguridad.</p>
                                <p>Si tiene alguna pregunta, no dude en contactarnos.</p>
                                <p>Saludos,<br>El equipo de " . SITE_NAME . "</p>
                            </div>
                            <div class='footer'>
                                <p>© " . date('Y') . " " . SITE_NAME . ". Todos los derechos reservados.</p>
                                <p>Este es un correo electrónico automático, por favor no lo responda.</p>
                            </div>
                        </div>
                    </body>
                    </html>
                    ";
                    
                    // Configurar cabeceras para el correo HTML
                    $headers = "MIME-Version: 1.0" . "\r\n";
                    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                    $headers .= 'From: ' . SITE_NAME . ' <' . ADMIN_EMAIL . '>' . "\r\n";
                    $headers .= 'Reply-To: ' . ADMIN_EMAIL . "\r\n";
                    $headers .= 'X-Mailer: PHP/' . phpversion();
                    
                    // Enviar el correo
                    if (mail($email, $subject, $message, $headers)) {
                        $success = true;
                        
                        // Registrar la actividad
                        logActivity(
                            $user['id'],
                            'Solicitud de restablecimiento de contraseña',
                            'auth'
                        );
                    } else {
                        throw new Exception('No se pudo enviar el correo electrónico. Por favor, inténtelo de nuevo más tarde.');
                    }
                    
                } catch (Exception $e) {
                    DB::rollBack();
                    $error = 'Ocurrió un error al procesar su solicitud. Por favor, inténtelo de nuevo más tarde.';
                    error_log('Error en recuperación de contraseña: ' . $e->getMessage());
                }
            }
        }
    }
}

// Configurar el título de la página
$pageTitle = 'Recuperar contraseña';
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
        .forgot-password-page {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .forgot-password-box {
            width: 100%;
            max-width: 480px;
            margin: 0 auto;
        }
        .forgot-password-logo {
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .forgot-password-logo a {
            color: #fff;
            font-size: 2rem;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        .forgot-password-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }
        .forgot-password-card-body {
            padding: 2rem;
            background-color: #fff;
        }
        .input-group-text {
            background-color: transparent;
        }
        .btn-submit {
            padding: 0.6rem 2rem;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body class="hold-transition forgot-password-page">
<div class="forgot-password-box">
    <!-- Logo -->
    <div class="forgot-password-logo">
        <a href="<?php echo SITE_URL; ?>">
            <img src="<?php echo ASSETS_URL; ?>/img/logo-white.png" alt="Logo" style="max-height: 60px; margin-bottom: 10px;">
            <div><?php echo SITE_NAME; ?></div>
        </a>
    </div>
    
    <!-- Forgot Password Card -->
    <div class="card forgot-password-card">
        <div class="card-body forgot-password-card-body">
            <?php if (!$success): ?>
                <h2 class="text-center mb-4">Recuperar contraseña</h2>
                <p class="text-muted text-center mb-4">Ingrese su dirección de correo electrónico y le enviaremos un enlace para restablecer su contraseña.</p>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle mr-2"></i> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>
                
                <form action="" method="post" id="forgotPasswordForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    
                    <div class="form-group">
                        <label for="email">Correo electrónico</label>
                        <div class="input-group">
                            <input type="email" 
                                   name="email" 
                                   id="email"
                                   class="form-control form-control-lg" 
                                   placeholder="Ingrese su correo electrónico"
                                   value="<?php echo htmlspecialchars($email); ?>"
                                   required
                                   autofocus>
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <span class="fas fa-envelope"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-primary btn-block btn-submit">
                            <i class="fas fa-paper-plane mr-2"></i> Enviar enlace de recuperación
                        </button>
                    </div>
                </form>
                
                <div class="text-center mt-3">
                    <a href="<?php echo ADMIN_URL; ?>/login.php">
                        <i class="fas fa-arrow-left mr-1"></i> Volver al inicio de sesión
                    </a>
                </div>
                
            <?php else: ?>
                <!-- Mensaje de éxito -->
                <div class="text-center">
                    <div class="mb-4">
                        <i class="fas fa-envelope-open-text text-primary" style="font-size: 5rem;"></i>
                    </div>
                    <h3>¡Revisa tu correo!</h3>
                    <p class="text-muted mb-4">
                        Hemos enviado un enlace de recuperación a <strong><?php echo htmlspecialchars($email); ?></strong>.
                        <br>
                        Por favor, revisa tu bandeja de entrada y haz clic en el enlace para restablecer tu contraseña.
                    </p>
                    <div class="alert alert-info text-left">
                        <h5><i class="icon fas fa-info-circle"></i> ¿No recibiste el correo?</h5>
                        <ul class="mb-0">
                            <li>Revisa tu carpeta de correo no deseado o spam.</li>
                            <li>Verifica que hayas ingresado la dirección de correo correctamente.</li>
                            <li>Si aún no lo recibes, <a href="" id="resendLink">haz clic aquí para reenviar</a>.</li>
                        </ul>
                    </div>
                    <a href="<?php echo ADMIN_URL; ?>/login.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left mr-2"></i> Volver al inicio de sesión
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <!-- /.forgot-password-card-body -->
        
        <div class="card-footer text-center">
            <small class="text-muted">
                &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Todos los derechos reservados.
                <br>
                <small>Versión <?php echo APP_VERSION; ?></small>
            </small>
        </div>
    </div>
</div>
<!-- /.forgot-password-box -->

<!-- jQuery -->
<script src="<?php echo ASSETS_URL; ?>/plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="<?php echo ASSETS_URL; ?>/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- Toastr -->
<script src="<?php echo ASSETS_URL; ?>/plugins/toastr/toastr.min.js"></script>

<script>
$(function () {
    // Validar el formulario antes de enviar
    $('#forgotPasswordForm').on('submit', function(e) {
        const email = $('#email').val().trim();
        let isValid = true;
        
        // Validar correo electrónico
        if (!email) {
            toastr.error('Por favor, ingrese su dirección de correo electrónico.');
            isValid = false;
        } else if (!isValidEmail(email)) {
            toastr.error('Por favor, ingrese una dirección de correo electrónico válida.');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            return false;
        }
        
        // Mostrar indicador de carga
        $('button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Enviando...');
    });
    
    // Función para validar email
    function isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    // Reenviar correo de recuperación
    $('#resendLink').on('click', function(e) {
        e.preventDefault();
        
        const email = '<?php echo htmlspecialchars($email); ?>';
        const $btn = $(this);
        
        $btn.html('<i class="fas fa-spinner fa-spin mr-1"></i> Enviando...').prop('disabled', true);
        
        // Enviar solicitud AJAX para reenviar el correo
        $.ajax({
            url: '<?php echo ADMIN_URL; ?>/ajax-handler.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'resend_reset_email',
                email: email,
                csrf_token: '<?php echo generateCsrfToken(); ?>'
            },
            success: function(response) {
                if (response.success) {
                    toastr.success('Se ha enviado un nuevo correo de recuperación a ' + email);
                    $btn.html('Reenviar enlace').prop('disabled', false);
                } else {
                    toastr.error(response.message || 'Ocurrió un error al reenviar el correo. Por favor, inténtelo de nuevo.');
                    $btn.html('Reenviar enlace').prop('disabled', false);
                }
            },
            error: function() {
                toastr.error('Ocurrió un error al procesar su solicitud. Por favor, inténtelo de nuevo.');
                $btn.html('Reenviar enlace').prop('disabled', false);
            }
        });
    });
    
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
