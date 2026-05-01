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

// Obtener el ID del usuario a editar
$usuario_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($usuario_id <= 0) {
    $_SESSION['error_message'] = 'ID de usuario no válido';
    header('Location: usuarios.php');
    exit();
}

try {
    // Obtener los datos del usuario
    $stmt = $pdo->prepare("
        SELECT 
            id, nombre, apellidos, email, rol, activo, 
            fecha_registro, fecha_verificacion, ultimo_aceso,
            CASE 
                WHEN fecha_verificacion IS NOT NULL THEN 1 
                ELSE 0 
            END as verificado
        FROM usuarios_registrados 
        WHERE id = ?
    ");
    
    $stmt->execute([$usuario_id]);
    
    if ($stmt->rowCount() === 0) {
        $_SESSION['error_message'] = 'Usuario no encontrado';
        header('Location: usuarios.php');
        exit();
    }
    
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Procesar el formulario cuando se envía
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validar y limpiar los datos de entrada
        $nombre = trim($_POST['nombre'] ?? '');
        $apellidos = trim($_POST['apellidos'] ?? '');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $rol = in_array($_POST['rol'] ?? '', ['administrador', 'editor', 'usuario', 'lector']) ? $_POST['rol'] : 'usuario';
        $activo = isset($_POST['activo']) ? 1 : 0;
        $cambiar_password = isset($_POST['cambiar_password']);
        $nueva_password = $cambiar_password ? trim($_POST['nueva_password'] ?? '') : '';
        
        // Validar campos requeridos
        $errores = [];
        
        if (empty($nombre)) {
            $errores[] = 'El nombre es obligatorio';
        }
        
        if (!$email) {
            $errores[] = 'El correo electrónico no es válido';
        } else if ($email !== $usuario['email']) {
            // Verificar si el correo ya está en uso
            $stmt = $pdo->prepare("SELECT id FROM usuarios_registrados WHERE email = ? AND id != ?");
            $stmt->execute([$email, $usuario_id]);
            
            if ($stmt->rowCount() > 0) {
                $errores[] = 'El correo electrónico ya está registrado';
            }
        }
        
        if ($cambiar_password && strlen($nueva_password) < 8) {
            $errores[] = 'La nueva contraseña debe tener al menos 8 caracteres';
        }
        
        // Si no hay errores, actualizar el usuario
        if (empty($errores)) {
            try {
                $pdo->beginTransaction();
                
                // Construir la consulta de actualización
                $params = [
                    'nombre' => $nombre,
                    'apellidos' => $apellidos,
                    'email' => $email,
                    'rol' => $rol,
                    'activo' => $activo,
                    'id' => $usuario_id
                ];
                
                $sql = "UPDATE usuarios_registrados SET 
                            nombre = :nombre,
                            apellidos = :apellidos,
                            email = :email,
                            rol = :rol,
                            activo = :activo,
                            fecha_actualizacion = NOW()";
                
                // Si se está cambiando la contraseña
                if ($cambiar_password && !empty($nueva_password)) {
                    $password_hash = password_hash($nueva_password, PASSWORD_DEFAULT);
                    $sql .= ", password = :password";
                    $params['password'] = $password_hash;
                }
                
                $sql .= " WHERE id = :id";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                
                // Registrar la acción
                logAccion('usuarios', 'editar', "Editó la información del usuario con ID $usuario_id");
                
                $pdo->commit();
                
                $_SESSION['success_message'] = 'Usuario actualizado correctamente';
                header("Location: usuario-editar.php?id=$usuario_id");
                exit();
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $errores[] = 'Error al actualizar el usuario: ' . $e->getMessage();
                error_log("Error al actualizar usuario: " . $e->getMessage());
            }
        }
    }
    
} catch (Exception $e) {
    $errores[] = 'Error al cargar los datos del usuario: ' . $e->getMessage();
    error_log("Error al cargar usuario para editar: " . $e->getMessage());
}

// Establecer la página actual
$current_page = 'usuarios';

// Incluir encabezado
require_once __DIR__ . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-user-edit me-2"></i>Editar Usuario
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
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?php 
                    echo htmlspecialchars($_SESSION['success_message']); 
                    unset($_SESSION['success_message']);
                    ?>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <form method="POST" id="formEditarUsuario">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nombre" class="form-label">Nombre</label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" 
                                           value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="apellidos" class="form-label">Apellidos</label>
                                    <input type="text" class="form-control" id="apellidos" name="apellidos" 
                                           value="<?php echo htmlspecialchars($usuario['apellidos']); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Correo electrónico</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="rol" class="form-label">Rol</label>
                                    <select class="form-select" id="rol" name="rol" required>
                                        <option value="usuario" <?php echo $usuario['rol'] === 'usuario' ? 'selected' : ''; ?>>Usuario</option>
                                        <option value="editor" <?php echo $usuario['rol'] === 'editor' ? 'selected' : ''; ?>>Editor</option>
                                        <option value="administrador" <?php echo $usuario['rol'] === 'administrador' ? 'selected' : ''; ?>>Administrador</option>
                                        <option value="lector" <?php echo $usuario['rol'] === 'lector' ? 'selected' : ''; ?>>Lector</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="activo" name="activo" 
                                   <?php echo $usuario['activo'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="activo">Usuario activo</label>
                        </div>
                        
                        <hr class="my-4">
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="cambiar_password" name="cambiar_password">
                                <label class="form-check-label fw-bold" for="cambiar_password">Cambiar contraseña</label>
                            </div>
                            <div id="passwordFields" class="mt-3" style="display: none;">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i> 
                                    Deja este campo en blanco si no deseas cambiar la contraseña.
                                </div>
                                <div class="mb-3">
                                    <label for="nueva_password" class="form-label">Nueva contraseña</label>
                                    <input type="password" class="form-control" id="nueva_password" name="nueva_password">
                                    <div class="form-text">La contraseña debe tener al menos 8 caracteres.</div>
                                </div>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Información adicional</h5>
                            </div>
                            <div class="card-body">
                                <dl class="row mb-0">
                                    <dt class="col-sm-4">Fecha de registro:</dt>
                                    <dd class="col-sm-8">
                                        <?php echo date('d/m/Y H:i', strtotime($usuario['fecha_registro'])); ?>
                                    </dd>
                                    
                                    <dt class="col-sm-4">Último acceso:</dt>
                                    <dd class="col-sm-8">
                                        <?php 
                                        echo $usuario['ultimo_aceso'] 
                                            ? date('d/m/Y H:i', strtotime($usuario['ultimo_acceso'])) 
                                            : 'Nunca'; 
                                        ?>
                                    </dd>
                                    
                                    <dt class="col-sm-4">Estado de verificación:</dt>
                                    <dd class="col-sm-8">
                                        <?php if ($usuario['verificado']): ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check-circle me-1"></i> Verificado
                                            </span>
                                            <div class="text-muted small mt-1">
                                                <?php echo date('d/m/Y H:i', strtotime($usuario['fecha_verificacion'])); ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">
                                                <i class="fas fa-exclamation-circle me-1"></i> No verificado
                                            </span>
                                        <?php endif; ?>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Guardar cambios
                                </button>
                                <a href="usuarios.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i> Cancelar
                                </a>
                            </div>
                            <div>
                                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#confirmarEliminarModal">
                                    <i class="fas fa-trash-alt me-1"></i> Eliminar usuario
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmación para eliminar usuario -->
<div class="modal fade" id="confirmarEliminarModal" tabindex="-1" aria-labelledby="confirmarEliminarModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="confirmarEliminarModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i> Confirmar eliminación
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas eliminar este usuario? Esta acción no se puede deshacer.</p>
                <p class="mb-0"><strong>Nota:</strong> El usuario no será eliminado físicamente, solo se marcará como inactivo.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cancelar
                </button>
                <form id="formEliminarUsuario" action="api/usuarios/eliminar.php" method="POST" class="d-inline">
                    <input type="hidden" name="id" value="<?php echo $usuario_id; ?>">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash-alt me-1"></i> Eliminar
                    </button>
                </form>
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
    // Mostrar/ocultar campos de contraseña
    $('#cambiar_password').on('change', function() {
        if ($(this).is(':checked')) {
            $('#passwordFields').slideDown();
            $('#nueva_password').prop('required', true);
        } else {
            $('#passwordFields').slideUp();
            $('#nueva_password').prop('required', false).val('');
        }
    });
    
    // Confirmación antes de eliminar
    $('#formEliminarUsuario').on('submit', function(e) {
        if (!confirm('¿Estás seguro de que deseas eliminar este usuario? Esta acción no se puede deshacer.')) {
            e.preventDefault();
            return false;
        }
        return true;
    });
    
    // Manejar el envío del formulario con AJAX
    $('#formEditarUsuario').on('submit', function(e) {
        e.preventDefault();
        
        // Mostrar indicador de carga
        const submitBtn = $(this).find('button[type="submit"]');
        const originalBtnText = submitBtn.html();
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Guardando...');
        
        // Enviar datos con AJAX
        $.ajax({
            url: 'api/usuarios/actualizar.php',
            method: 'POST',
            data: $(this).serialize() + '&id=<?php echo $usuario_id; ?>',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Mostrar mensaje de éxito
                    const alertHtml = `
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            ${response.message || 'Usuario actualizado correctamente'}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `;
                    
                    $('.container-fluid').prepend(alertHtml);
                    
                    // Desplazarse al principio de la página
                    window.scrollTo(0, 0);
                    
                    // Recargar la página después de 1.5 segundos
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    // Mostrar mensaje de error
                    let errorMessage = 'Error al actualizar el usuario';
                    if (response.message) {
                        errorMessage = response.message;
                    } else if (response.errors && response.errors.length > 0) {
                        errorMessage = response.errors.join('<br>');
                    }
                    
                    const alertHtml = `
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            ${errorMessage}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `;
                    
                    $('.container-fluid').prepend(alertHtml);
                    window.scrollTo(0, 0);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                alert('Error de conexión. Por favor, inténtalo de nuevo.');
            },
            complete: function() {
                // Restaurar el botón
                submitBtn.prop('disabled', false).html(originalBtnText);
            }
        });
    });
});
</script>

<!-- Estilos adicionales -->
<style>
.form-label {
    font-weight: 500;
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

.badge {
    font-weight: 500;
    padding: 0.4em 0.6em;
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
