<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/config_common.php';

// Obtener el ID del mensaje
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    $_SESSION['error'] = 'ID de mensaje no válido';
    header('Location: index.php');
    exit;
}

try {
    // Obtener el mensaje
    $stmt = $pdo->prepare("SELECT * FROM mensajes_contacto WHERE id = ?");
    $stmt->execute([$id]);
    $mensaje = $stmt->fetch();
    
    if (!$mensaje) {
        $_SESSION['error'] = 'El mensaje solicitado no existe';
        header('Location: index.php');
        exit;
    }
    
    // Marcar como leído
    if (!$mensaje['leido']) {
        $pdo->prepare("UPDATE mensajes_contacto SET leido = 1, fecha_leido = NOW() WHERE id = ?")->execute([$id]);
    }
    
} catch (PDOException $e) {
    error_log('Error al obtener el mensaje: ' . $e->getMessage());
    $_SESSION['error'] = 'Error al cargar el mensaje';
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Mensaje - Panel de Control</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main class="container">
        <div class="page-actions">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver al listado
            </a>
            <div class="action-buttons">
                <a href="responder.php?id=<?= $mensaje['id'] ?>" class="btn btn-primary">
                    <i class="fas fa-reply"></i> Responder
                </a>
                <a href="eliminar.php?id=<?= $mensaje['id'] ?>" class="btn btn-danger" 
                   onclick="return confirm('¿Estás seguro de eliminar este mensaje?')">
                    <i class="fas fa-trash"></i> Eliminar
                </a>
            </div>
        </div>
        
        <div class="message-detail">
            <div class="message-header">
                <h1><?= htmlspecialchars($mensaje['asunto']) ?></h1>
                <div class="message-meta">
                    <div class="meta-item">
                        <i class="fas fa-user"></i>
                        <span><?= htmlspecialchars($mensaje['nombre']) ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-envelope"></i>
                        <a href="mailto:<?= htmlspecialchars($mensaje['email']) ?>"><?= htmlspecialchars($mensaje['email']) ?></a>
                    </div>
                    <?php if (!empty($mensaje['telefono'])): ?>
                    <div class="meta-item">
                        <i class="fas fa-phone"></i>
                        <a href="tel:<?= htmlspecialchars($mensaje['telefono']) ?>"><?= htmlspecialchars($mensaje['telefono']) ?></a>
                    </div>
                    <?php endif; ?>
                    <div class="meta-item">
                        <i class="fas fa-clock"></i>
                        <span>Enviado el <?= date('d/m/Y \a \l\a\s H:i', strtotime($mensaje['fecha_envio'])) ?></span>
                    </div>
                    <?php if ($mensaje['leido'] && !empty($mensaje['fecha_leido'])): ?>
                    <div class="meta-item">
                        <i class="fas fa-check-double"></i>
                        <span>Leído el <?= date('d/m/Y \a \l\a\s H:i', strtotime($mensaje['fecha_leido'])) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="message-content">
                <?= nl2br(htmlspecialchars($mensaje['mensaje'])) ?>
            </div>
            
            <?php if (!empty($mensaje['adjunto'])): ?>
            <div class="message-attachments">
                <h3>Archivo adjunto</h3>
                <a href="../uploads/<?= htmlspecialchars($mensaje['adjunto']) ?>" class="btn btn-secondary" download>
                    <i class="fas fa-paperclip"></i> Descargar archivo adjunto
                </a>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="message-actions">
            <a href="responder.php?id=<?= $mensaje['id'] ?>" class="btn btn-primary">
                <i class="fas fa-reply"></i> Responder
            </a>
            <a href="#" class="btn btn-secondary" onclick="window.print()">
                <i class="fas fa-print"></i> Imprimir
            </a>
            <a href="eliminar.php?id=<?= $mensaje['id'] ?>" class="btn btn-danger" 
               onclick="return confirm('¿Estás seguro de eliminar este mensaje?')">
                <i class="fas fa-trash"></i> Eliminar
            </a>
        </div>
    </main>
    
    <?php include '../includes/footer.php'; ?>
    
    <style>
    .message-detail {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 2rem;
        margin: 1.5rem 0;
    }
    
    .message-header {
        border-bottom: 1px solid #eee;
        padding-bottom: 1.5rem;
        margin-bottom: 1.5rem;
    }
    
    .message-header h1 {
        margin: 0 0 1rem 0;
        color: var(--dark-color);
    }
    
    .message-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 1.5rem;
        color: var(--gray-700);
        font-size: 0.9rem;
    }
    
    .meta-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .meta-item i {
        color: var(--primary-color);
        width: 16px;
        text-align: center;
    }
    
    .message-content {
        line-height: 1.7;
        font-size: 1.05rem;
        color: var(--gray-900);
        white-space: pre-line;
    }
    
    .message-attachments {
        margin-top: 2rem;
        padding-top: 1.5rem;
        border-top: 1px solid #eee;
    }
    
    .message-actions {
        display: flex;
        gap: 0.75rem;
        margin-top: 2rem;
        padding-top: 1.5rem;
        border-top: 1px solid #eee;
    }
    
    @media print {
        .page-actions, .message-actions {
            display: none;
        }
        
        .message-detail {
            padding: 0;
            box-shadow: none;
        }
    }
    </style>
</body>
</html>
