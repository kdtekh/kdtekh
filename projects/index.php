<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = "Nuestros Proyectos | KDTekh";
$pageDescription = "Explora nuestros proyectos de tecnología, desarrollo web y soluciones digitales innovadoras.";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="<?php echo $pageDescription; ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/img/favicon.png">
    
    <!-- CSS -->
    <link rel="stylesheet" href="/css/main.css">
    <link rel="stylesheet" href="/css/projects.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <!-- Hero Section -->
    <section class="hero projects-hero">
        <div class="container">
            <div class="hero-content">
                <h1>Nuestros Proyectos</h1>
                <p>Descubre las soluciones tecnológicas innovadoras que hemos desarrollado para nuestros clientes.</p>
            </div>
        </div>
    </section>

    <!-- Projects Grid -->
    <section class="projects-section">
        <div class="container">
            <div class="section-header">
                <h2>Explora Nuestro Portafolio</h2>
                <p>Soluciones personalizadas que impulsan el éxito de nuestros clientes</p>
            </div>
            
            <div class="projects-grid">
                <!-- Project 1 -->
                <div class="project-card">
                    <div class="project-image">
                        <img src="/img/projects/project1.jpg" alt="Proyecto 1">
                        <div class="project-overlay">
                            <a href="/projects/project1" class="btn btn-primary">Ver Proyecto</a>
                        </div>
                    </div>
                    <div class="project-content">
                        <h3>Plataforma E-learning</h3>
                        <p>Solución integral para la gestión de cursos en línea con seguimiento de progreso.</p>
                        <div class="project-tags">
                            <span>Desarrollo Web</span>
                            <span>Laravel</span>
                            <span>Vue.js</span>
                        </div>
                    </div>
                </div>

                <!-- Project 2 -->
                <div class="project-card">
                    <div class="project-image">
                        <img src="/img/projects/project2.jpg" alt="Proyecto 2">
                        <div class="project-overlay">
                            <a href="/projects/project2" class="btn btn-primary">Ver Proyecto</a>
                        </div>
                    </div>
                    <div class="project-content">
                        <h3>Aplicación Móvil de Salud</h3>
                        <p>Monitoreo de salud y bienestar con integración de wearables.</p>
                        <div class="project-tags">
                            <span>React Native</span>
                            <span>Node.js</span>
                            <span>MongoDB</span>
                        </div>
                    </div>
                </div>

                <!-- Project 3 -->
                <div class="project-card">
                    <div class="project-image">
                        <img src="/img/projects/project3.jpg" alt="Proyecto 3">
                        <div class="project-overlay">
                            <a href="/projects/project3" class="btn btn-primary">Ver Proyecto</a>
                        </div>
                    </div>
                    <div class="project-content">
                        <h3>Sistema de Gestión Empresarial</h3>
                        <p>Plataforma todo en uno para la gestión de negocios.</p>
                        <div class="project-tags">
                            <span>PHP</span>
                            <span>MySQL</span>
                            <span>Bootstrap</span>
                        </div>
                    </div>
                </div>

                <!-- Add more project cards as needed -->
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <h2>¿Tienes un proyecto en mente?</h2>
            <p>Contáctanos y convierte tus ideas en realidad con nuestro equipo de expertos.</p>
            <a href="/contacto" class="btn btn-primary btn-lg">Hablemos</a>
        </div>
    </section>

    <!-- Footer -->
    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <!-- JavaScript -->
    <script src="/js/main.js"></script>
    <script src="/js/projects.js"></script>
</body>
</html>
