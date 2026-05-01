<?php
// Incluir archivos necesarios
define('ADMIN_PATH', dirname(__FILE__));
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Verificar permisos de administrador
if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'administrador') {
    $_SESSION['error_message'] = 'No tienes permiso para acceder a esta sección';
    header('Location: index.php');
    exit();
}

// Establecer la página actual
$current_page = 'usuarios';

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar y limpiar los datos de entrada
    $nombre = trim($_POST['nombre'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $rol = in_array($_POST['rol'] ?? '', ['administrador', 'editor', 'usuario', 'lector']) ? $_POST['rol'] : 'usuario';
    $enviar_correo = isset($_POST['enviar_correo']);
    
    // Validar campos requeridos
    $errores = [];
    
    if (empty($nombre)) {
        $errores[] = 'El nombre es obligatorio';
    }
    
    if (!$email) {
        $errores[] = 'El correo electrónico no es válido';
    } else {
        // Verificar si el correo ya está en uso
        $stmt = $pdo->prepare("SELECT id FROM usuarios_registrados WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $errores[] = 'El correo electrónico ya está registrado';
        }
    }
    
    // Si no hay errores, proceder con el registro
    if (empty($errores)) {
        try {
            // Generar una contraseña temporal
            $password_temporal = bin2hex(random_bytes(8));
            $password_hash = password_hash($password_temporal, PASSWORD_DEFAULT);
            
            // Generar token de verificación
            $token_verificacion = bin2hex(random_bytes(32));
            
            // Insertar el nuevo usuario
            $stmt = $pdo->prepare("
                INSERT INTO usuarios_registrados (
                    nombre, apellidos, email, password, rol, 
                    token_verificacion, activo, fecha_registro
                ) VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
            ");
            
            $stmt->execute([
                $nombre,
                $apellidos,
                $email,
                $password_hash,
                $rol,
                $token_verificacion
            ]);
            
            $usuario_id = $pdo->lastInsertId();
            
            // Registrar la acción
            logAccion('usuarios', 'crear', "Creó un nuevo usuario: $email");
            
            // Enviar correo de bienvenida si está habilitado
            if ($enviar_correo) {
                // Aquí iría el código para enviar el correo electrónico
                // con las instrucciones para establecer una contraseña
                // y verificar la cuenta
                
                // Por ahora, solo registramos que se intentó enviar el correo
                logAccion('usuarios', 'enviar_bienvenida', "Se envió correo de bienvenida a $email");
            }
            
            // Redirigir a la edición del usuario con mensaje de éxito
            $_SESSION['success_message'] = 'Usuario creado correctamente' . ($enviar_correo ? '. Se ha enviado un correo de bienvenida.' : '');
            header("Location: usuario-editar.php?id=$usuario_id");
            exit();
            
        } catch (Exception $e) {
            $errores[] = 'Error al crear el usuario: ' . $e->getMessage();
            error_log("Error al crear usuario: " . $e->getMessage());
        }
    }
}

// Incluir encabezado
require_once __DIR__ . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-user-plus me-2"></i>Nuevo Usuario
                </h1>
                <div>
                    <a href="usuarios.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Volver a la lista
                    </a>
                </div>
            </div>
            
            <?php if (!empty($errores)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errores as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <form method="POST" id="formNuevoUsuario">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" 
                                           value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="apellidos" class="form-label">Apellidos</label>
                                    <input type="text" class="form-control" id="apellidos" name="apellidos" 
                                           value="<?php echo htmlspecialchars($_POST['apellidos'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Correo electrónico <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="rol" class="form-label">Rol <span class="text-danger">*</span></label>
                                    <select class="form-select" id="rol" name="rol" required>
                                        <option value="usuario" <?php echo ($_POST['rol'] ?? '') === 'usuario' ? 'selected' : ''; ?>>Usuario</option>
                                        <option value="editor" <?php echo ($_POST['rol'] ?? '') === 'editor' ? 'selected' : ''; ?>>Editor</option>
                                        <option value="administrador" <?php echo ($_POST['rol'] ?? '') === 'administrador' ? 'selected' : ''; ?>>Administrador</option>
                                        <option value="lector" <?php echo ($_POST['rol'] ?? '') === 'lector' ? 'selected' : ''; ?>>Lector</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="enviar_correo" name="enviar_correo" 
                                   <?php echo isset($_POST['enviar_correo']) ? 'checked' : 'checked'; ?>>
                            <label class="form-check-label" for="enviar_correo">
                                Enviar correo de bienvenida con instrucciones para establecer contraseña
                            </label>
                        </div>
                        
                        <hr class="my-4">
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Se generará automáticamente una contraseña temporal para el nuevo usuario.
                            Si seleccionas la opción de enviar correo, el usuario recibirá instrucciones para establecer su propia contraseña.
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Guardar usuario
                            </button>
                            <a href="usuarios.php" class="btn btn-outline-secondary ms-2">
                                <i class="fas fa-times me-1"></i> Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Incluir pie de página
require_once __DIR__ . '/includes/footer.php';
?>

<!-- Scripts adicionales -->
<script>
$(document).ready(function() {
    // Validación del formulario
    $('#formNuevoUsuario').on('submit', function(e) {
        // Mostrar indicador de carga
        const submitBtn = $(this).find('button[type="submit"]');
        const originalBtnText = submitBtn.html();
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Guardando...');
        
        // Aquí podrías agregar validación adicional con JavaScript si es necesario
        
        // Si llegamos aquí, el formulario es válido y se enviará
        return true;
    });
    
    // Mostrar/ocultar campos adicionales según el rol seleccionado
    $('#rol').on('change', function() {
        const rol = $(this).val();
        // Aquí podrías agregar lógica adicional según el rol seleccionado
    });
});
</script>

<!-- Estilos adicionales -->
<style>
.form-label {
    font-weight: 500;
}

.form-label.required:after {
    content: ' *';
    color: #dc3545;
}

.card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    margin-bottom: 1.5rem;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    font-weight: 600;
}

hr {
    opacity: 0.1;
}

/* Estilos para los checkboxes personalizados */
.form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

/* Ajustes para dispositivos móviles */
@media (max-width: 768px) {
    .card-body {
        padding: 1rem;
    }
    
    .btn {
        padding: 0.4rem 0.75rem;
        font-size: 0.9rem;
    }
}
</style>
