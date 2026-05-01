<?php
// Verificar si la variable $current_page está definida, de lo contrario, establecer un valor predeterminado
$current_page = $current_page ?? 'dashboard';
?>
<!-- Sidebar -->
<div class="col-md-3 sidebar">
    <div class="text-center py-4">
        <div class="d-flex justify-content-center mb-2">
            <img src="../img/logo_small.png" alt="KDTekh Logo" style="max-height: 40px;">
        </div>
        <p class="text-muted small mb-0">Panel de Administración</p>
    </div>
    
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>" href="index.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'mensajes' ? 'active' : ''; ?>" href="contacto-admin.php">
                    <i class="fas fa-envelope"></i> Mensajes
                    <?php if (isset($mensajesNoLeidos) && $mensajesNoLeidos > 0): ?>
                        <span class="badge bg-danger rounded-pill float-end"><?php echo $mensajesNoLeidos; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'suscriptores' ? 'active' : ''; ?>" href="newsletter-admin.php">
                    <i class="fas fa-users"></i> Suscriptores
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'articulos' ? 'active' : ''; ?>" href="#">
                    <i class="fas fa-file-alt"></i> Artículos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'usuarios' ? 'active' : ''; ?>" href="#">
                    <i class="fas fa-users"></i> Usuarios
                </a>
            </li>
            <li class="nav-item mt-4">
                <a class="nav-link text-danger" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </li>
        </ul>
    </div>
</div>
