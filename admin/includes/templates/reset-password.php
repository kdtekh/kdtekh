<?php
/**
 * Plantilla de restablecimiento de contraseña
 */

// Verificar si el token es válido
$token = $_GET['token'] ?? '';
$error = '';
$success = false;
$email = '';
$tokenValid = false;

// Verificar si el token es válido
if (!empty($token)) {
    $user = DB::fetch("SELECT email FROM password_resets WHERE token = :token AND expires_at > NOW()", [':token' => $token]);
    
    if ($user) {
        $tokenValid = true;
        $email = $user['email'];
    } else {
        $error = 'El enlace de restablecimiento de contraseña no es válido o ha expirado.';
    }
} else {
    $error = 'No se proporcionó un token de restablecimiento de contraseña.';
}

// Procesar el formulario de restablecimiento de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValid) {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validar contraseña
    if (empty($password) || empty($confirmPassword)) {
        $error = 'Por favor, complete todos los campos.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Las contraseñas no coinciden.';
    } elseif (strlen($password) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres.';
    } else {
        // Actualizar la contraseña del usuario
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            DB::beginTransaction();
            
            // Actualizar la contraseña del usuario
            DB::query(
                "UPDATE users SET password = :password WHERE email = :email",
                [
                    ':password' => $hashedPassword,
                    ':email' => $email
                ]
            );
            
            // Eliminar el token de restablecimiento
            DB::query(
                "DELETE FROM password_resets WHERE email = :email",
                [':email' => $email]
            );
            
            DB::commit();
            
            // Registrar la actividad
            logActivity(
                DB::fetchColumn("SELECT id FROM users WHERE email = :email", [':email' => $email]),
                'Contraseña restablecida exitosamente',
                'auth'
            );
            
            $success = true;
            
        } catch (Exception $e) {
            DB::rollBack();
            $error = 'Ocurrió un error al restablecer la contraseña. Por favor, inténtelo de nuevo más tarde.';
            error_log('Error al restablecer la contraseña: ' . $e->getMessage());
        }
    }
}

// Configurar el título de la página
$pageTitle = $tokenValid ? 'Restablecer contraseña' : 'Enlace inválido';
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
        .reset-password-page {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .reset-password-box {
            width: 100%;
            max-width: 480px;
            margin: 0 auto;
        }
        .reset-password-logo {
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .reset-password-logo a {
            color: #fff;
            font-size: 2rem;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        .reset-password-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }
        .reset-password-card-body {
            padding: 2rem;
            background-color: #fff;
        }
        .input-group-text {
            background-color: transparent;
        }
        .btn-reset {
            padding: 0.6rem 2rem;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .password-strength {
            height: 5px;
            margin-top: 0.5rem;
            margin-bottom: 1rem;
            background-color: #e9ecef;
            border-radius: 3px;
            overflow: hidden;
        }
        .password-strength-bar {
            height: 100%;
            width: 0;
            background-color: #dc3545;
            transition: width 0.3s ease, background-color 0.3s ease;
        }
        .password-requirements {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 1rem;
        }
        .password-requirements ul {
            padding-left: 1.5rem;
            margin-bottom: 0;
        }
        .password-requirements li {
            margin-bottom: 0.25rem;
        }
        .password-requirements .valid {
            color: #28a745;
        }
        .password-requirements .valid:before {
            content: "✓ ";
        }
    </style>
</head>
<body class="hold-transition reset-password-page">
<div class="reset-password-box">
    <!-- Logo -->
    <div class="reset-password-logo">
        <a href="<?php echo SITE_URL; ?>">
            <img src="<?php echo ASSETS_URL; ?>/img/logo-white.png" alt="Logo" style="max-height: 60px; margin-bottom: 10px;">
            <div><?php echo SITE_NAME; ?></div>
        </a>
    </div>
    
    <!-- Reset Password Card -->
    <div class="card reset-password-card">
        <div class="card-body reset-password-card-body">
            <?php if ($tokenValid && !$success): ?>
                <h2 class="text-center mb-4">Restablecer contraseña</h2>
                <p class="text-muted text-center mb-4">Ingrese su nueva contraseña a continuación.</p>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle mr-2"></i> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>
                
                <form action="" method="post" id="resetPasswordForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                    
                    <div class="form-group">
                        <label for="password">Nueva contraseña</label>
                        <div class="input-group">
                            <input type="password" 
                                   name="password" 
                                   id="password"
                                   class="form-control form-control-lg" 
                                   placeholder="Ingrese su nueva contraseña"
                                   required
                                   autofocus>
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary toggle-password" type="button">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="password-strength">
                            <div class="password-strength-bar" id="passwordStrengthBar"></div>
                        </div>
                        <div class="password-requirements">
                            <p class="mb-1">La contraseña debe cumplir con los siguientes requisitos:</p>
                            <ul class="pl-3">
                                <li id="req-length" class="text-danger">Al menos 8 caracteres</li>
                                <li id="req-uppercase" class="text-danger">Al menos una letra mayúscula</li>
                                <li id="req-lowercase" class="text-danger">Al menos una letra minúscula</li>
                                <li id="req-number" class="text-danger">Al menos un número</li>
                                <li id="req-special" class="text-danger">Al menos un carácter especial</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirmar contraseña</label>
                        <div class="input-group">
                            <input type="password" 
                                   name="confirm_password" 
                                   id="confirm_password"
                                   class="form-control form-control-lg" 
                                   placeholder="Confirme su nueva contraseña"
                                   required>
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary toggle-password" type="button">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="invalid-feedback" id="passwordMatchFeedback">
                            Las contraseñas no coinciden.
                        </div>
                    </div>
                    
                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-primary btn-block btn-reset">
                            <i class="fas fa-key mr-2"></i> Restablecer contraseña
                        </button>
                    </div>
                </form>
                
                <div class="text-center mt-3">
                    <a href="<?php echo ADMIN_URL; ?>/login.php">
                        <i class="fas fa-arrow-left mr-1"></i> Volver al inicio de sesión
                    </a>
                </div>
                
            <?php elseif ($success): ?>
                <!-- Mensaje de éxito -->
                <div class="text-center">
                    <div class="mb-4">
                        <i class="fas fa-check-circle text-success" style="font-size: 5rem;"></i>
                    </div>
                    <h3>¡Contraseña restablecida!</h3>
                    <p class="text-muted mb-4">Tu contraseña ha sido actualizada correctamente.</p>
                    <a href="<?php echo ADMIN_URL; ?>/login.php" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt mr-2"></i> Iniciar sesión
                    </a>
                </div>
                
            <?php else: ?>
                <!-- Token inválido o expirado -->
                <div class="text-center">
                    <div class="mb-4">
                        <i class="fas fa-exclamation-triangle text-warning" style="font-size: 5rem;"></i>
                    </div>
                    <h3>Enlace inválido o expirado</h3>
                    <p class="text-muted mb-4">
                        <?php echo htmlspecialchars($error); ?>
                        <br>
                        Por favor, solicite un nuevo enlace para restablecer su contraseña.
                    </p>
                    <a href="<?php echo ADMIN_URL; ?>/forgot-password.php" class="btn btn-primary">
                        <i class="fas fa-redo mr-2"></i> Solicitar nuevo enlace
                    </a>
                    <div class="mt-3">
                        <a href="<?php echo ADMIN_URL; ?>/login.php">
                            <i class="fas fa-arrow-left mr-1"></i> Volver al inicio de sesión
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <!-- /.reset-password-card-body -->
        
        <div class="card-footer text-center">
            <small class="text-muted">
                &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Todos los derechos reservados.
                <br>
                <small>Versión <?php echo APP_VERSION; ?></small>
            </small>
        </div>
    </div>
</div>
<!-- /.reset-password-box -->

<!-- jQuery -->
<script src="<?php echo ASSETS_URL; ?>/plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="<?php echo ASSETS_URL; ?>/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- Toastr -->
<script src="<?php echo ASSETS_URL; ?>/plugins/toastr/toastr.min.js"></script>

<script>
$(function () {
    // Mostrar/ocultar contraseña
    $('.toggle-password').on('click', function() {
        const input = $(this).closest('.input-group').find('input');
        const icon = $(this).find('i');
        
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });
    
    // Validar fortaleza de la contraseña
    $('#password').on('input', function() {
        const password = $(this).val();
        let strength = 0;
        
        // Verificar longitud mínima
        const hasMinLength = password.length >= 8;
        $('#req-length').toggleClass('valid text-success', hasMinLength).toggleClass('text-danger', !hasMinLength);
        if (hasMinLength) strength += 20;
        
        // Verificar mayúsculas
        const hasUppercase = /[A-Z]/.test(password);
        $('#req-uppercase').toggleClass('valid text-success', hasUppercase).toggleClass('text-danger', !hasUppercase);
        if (hasUppercase) strength += 20;
        
        // Verificar minúsculas
        const hasLowercase = /[a-z]/.test(password);
        $('#req-lowercase').toggleClass('valid text-success', hasLowercase).toggleClass('text-danger', !hasLowercase);
        if (hasLowercase) strength += 20;
        
        // Verificar números
        const hasNumber = /\d/.test(password);
        $('#req-number').toggleClass('valid text-success', hasNumber).toggleClass('text-danger', !hasNumber);
        if (hasNumber) strength += 20;
        
        // Verificar caracteres especiales
        const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);
        $('#req-special').toggleClass('valid text-success', hasSpecial).toggleClass('text-danger', !hasSpecial);
        if (hasSpecial) strength += 20;
        
        // Actualizar la barra de fortaleza
        const $bar = $('#passwordStrengthBar');
        $bar.css('width', strength + '%');
        
        // Cambiar el color según la fortaleza
        if (strength < 40) {
            $bar.css('background-color', '#dc3545'); // Rojo
        } else if (strength < 80) {
            $bar.css('background-color', '#ffc107'); // Amarillo
        } else {
            $bar.css('background-color', '#28a745'); // Verde
        }
    });
    
    // Validar que las contraseñas coincidan
    $('#confirm_password').on('input', function() {
        const password = $('#password').val();
        const confirmPassword = $(this).val();
        
        if (confirmPassword.length > 0) {
            if (password !== confirmPassword) {
                $(this).addClass('is-invalid');
                $('#passwordMatchFeedback').show();
            } else {
                $(this).removeClass('is-invalid').addClass('is-valid');
                $('#passwordMatchFeedback').hide();
            }
        } else {
            $(this).removeClass('is-invalid is-valid');
            $('#passwordMatchFeedback').hide();
        }
    });
    
    // Validar el formulario antes de enviar
    $('#resetPasswordForm').on('submit', function(e) {
        const password = $('#password').val();
        const confirmPassword = $('#confirm_password').val();
        let isValid = true;
        
        // Validar que la contraseña cumpla con los requisitos
        if (password.length < 8) {
            toastr.error('La contraseña debe tener al menos 8 caracteres.');
            isValid = false;
        }
        
        if (!/[A-Z]/.test(password)) {
            toastr.error('La contraseña debe contener al menos una letra mayúscula.');
            isValid = false;
        }
        
        if (!/[a-z]/.test(password)) {
            toastr.error('La contraseña debe contener al menos una letra minúscula.');
            isValid = false;
        }
        
        if (!/\d/.test(password)) {
            toastr.error('La contraseña debe contener al menos un número.');
            isValid = false;
        }
        
        if (!/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
            toastr.error('La contraseña debe contener al menos un carácter especial.');
            isValid = false;
        }
        
        // Validar que las contraseñas coincidan
        if (password !== confirmPassword) {
            toastr.error('Las contraseñas no coinciden.');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            return false;
        }
        
        // Mostrar indicador de carga
        $('button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Procesando...');
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
