<?php
require_once 'admin/config.php';
require_once 'admin/db_connection.php';
require_once 'admin/blog_functions_new.php';

// Initialize blog manager
$blogManager = new BlogManager($pdo);

// Get current page number
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 6; // Posts per page

// Get paginated posts
$blog_data = $blogManager->getPublishedPosts($page, $per_page);
$posts = $blog_data['posts'];
$total_posts = $blog_data['total_posts'];
$total_pages = $blog_data['total_pages'];

// Get categories for sidebar
$categories = $blogManager->getAllCategories();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog - <?= SITE_NAME ?></title>
    <meta name="description" content="Nuestro blog con artículos sobre tecnología, desarrollo web y más.">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
        .blog-post {
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #eee;
        }
        .blog-post:last-child {
            border-bottom: none;
        }
        .post-meta {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        .post-excerpt {
            color: #495057;
            margin: 1rem 0;
        }
        .read-more {
            font-weight: 500;
        }
        .sidebar-widget {
            margin-bottom: 2rem;
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 0.5rem;
        }
        .widget-title {
            font-size: 1.25rem;
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #e9ecef;
        }
        .category-list {
            list-style: none;
            padding: 0;
        }
        .category-list li {
            padding: 0.5rem 0;
            border-bottom: 1px solid #e9ecef;
        }
        .category-list li:last-child {
            border-bottom: none;
        }
        .category-list a {
            color: #212529;
            text-decoration: none;
            display: block;
            transition: color 0.2s;
        }
        .category-list a:hover {
            color: #0d6efd;
            text-decoration: none;
        }
        .post-thumbnail {
            max-width: 100%;
            height: auto;
            border-radius: 0.25rem;
            margin-bottom: 1rem;
        }
        .pagination {
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include 'includes/header.php'; ?>

    <!-- Page Header -->
    <header class="bg-light py-5 mb-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 col-md-10 mx-auto text-center">
                    <h1 class="display-4">Nuestro Blog</h1>
                    <p class="lead">Artículos sobre tecnología, desarrollo web y más</p>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container">
        <div class="row">
            <!-- Blog Entries -->
            <div class="col-lg-8">
                <?php if (!empty($posts)): ?>
                    <?php foreach ($posts as $post): ?>
                        <article class="blog-post">
                            <?php if (!empty($post['featured_image'])): ?>
                                <img src="<?= htmlspecialchars($post['featured_image']) ?>" alt="<?= htmlspecialchars($post['title']) ?>" class="img-fluid post-thumbnail">
                            <?php endif; ?>
                            
                            <h2 class="h3">
                                <a href="post.php?slug=<?= urlencode($post['slug']) ?>" class="text-decoration-none">
                                    <?= htmlspecialchars($post['title']) ?>
                                </a>
                            </h2>
                            
                            <div class="post-meta">
                                <i class="far fa-calendar-alt me-1"></i> 
                                <?= date('d/m/Y', strtotime($post['published_at'] ?? $post['created_at'])) ?>
                                
                                <?php if (!empty($post['category_name'])): ?>
                                    <span class="mx-2">•</span>
                                    <i class="far fa-folder me-1"></i>
                                    <a href="category.php?slug=<?= urlencode($post['category_slug']) ?>" class="text-decoration-none">
                                        <?= htmlspecialchars($post['category_name']) ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                            
                            <div class="post-excerpt">
                                <?= nl2br(htmlspecialchars(mb_substr(strip_tags($post['excerpt'] ?: $post['content']), 0, 250) . '...')) ?>
                            </div>
                            
                            <a href="post.php?slug=<?= urlencode($post['slug']) ?>" class="btn btn-outline-primary">
                                Leer más <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </article>
                    <?php endforeach; ?>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Paginación de artículos" class="mt-5">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= ($page - 1) ?>">
                                            <span aria-hidden="true">&laquo; Anterior</span>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= ($page + 1) ?>">
                                            <span aria-hidden="true">Siguiente &raquo;</span>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="alert alert-info">No hay publicaciones disponibles en este momento.</div>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Search Widget -->
                <div class="sidebar-widget">
                    <h3 class="widget-title">Buscar</h3>
                    <form action="search.php" method="get" class="d-flex">
                        <input type="text" name="q" class="form-control me-2" placeholder="Buscar...">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
                    </form>
                </div>
                
                <!-- Categories Widget -->
                <?php if (!empty($categories)): ?>
                    <div class="sidebar-widget">
                        <h3 class="widget-title">Categorías</h3>
                        <ul class="category-list">
                            <?php foreach ($categories as $category): ?>
                                <li>
                                    <a href="category.php?slug=<?= urlencode($category['slug']) ?>">
                                        <?= htmlspecialchars($category['name']) ?>
                                        <span class="badge bg-secondary float-end"><?= $category['post_count'] ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
