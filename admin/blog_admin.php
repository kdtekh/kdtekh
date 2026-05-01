<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once 'config.php';
require_once 'auth.php';
require_once 'blog_functions_new.php';

// Check if user is logged in
requireAuth();

// Initialize blog manager
try {
    $blogManager = new BlogManager($pdo);
} catch (Exception $e) {
    die('Error initializing blog manager: ' . $e->getMessage());
}

// Set default timezone
date_default_timezone_set('Europe/Madrid');

// Initialize messages
$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === 'add_post') {
            // Process tags
            $tags = [];
            if (!empty($_POST['tags'])) {
                $tags = array_map('trim', explode(',', $_POST['tags']));
                $tags = array_filter($tags); // Remove empty tags
            }
            
            // Prepare post data
            $post_data = [
                'title' => trim($_POST['title'] ?? ''),
                'content' => $_POST['content'] ?? '',
                'excerpt' => trim($_POST['excerpt'] ?? ''),
                'category_id' => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
                'featured_image' => !empty($_POST['featured_image']) ? trim($_POST['featured_image']) : null,
                'meta_title' => trim($_POST['meta_title'] ?? ''),
                'meta_description' => trim($_POST['meta_description'] ?? ''),
                'meta_keywords' => trim($_POST['meta_keywords'] ?? ''),
                'status' => ($_POST['status'] === 'published') ? 'published' : 'draft',
                'tags' => $tags
            ];
            
            // Validate required fields
            if (empty($post_data['title'])) {
                throw new Exception('El título es obligatorio');
            }
            
            if (empty($post_data['content'])) {
                throw new Exception('El contenido es obligatorio');
            }
            
            // Add the post
            $post_id = $blogManager->addPost($post_data);
            
            if ($post_id) {
                $message = '¡Post creado exitosamente!';
                // Clear form
                $_POST = [];
            } else {
                throw new Exception('No se pudo crear el post. Por favor, inténtalo de nuevo.');
            }
        }
    } catch (Exception $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

// Get all posts with categories
try {
    $posts = $blogManager->getAllPosts();
} catch (Exception $e) {
    $error = "Error al cargar los posts: " . $e->getMessage();
    $posts = [];
}

// Get categories for the dropdown
try {
    $categories = $blogManager->getAllCategories();
} catch (Exception $e) {
    $error = "Error al cargar las categorías: " . $e->getMessage();
    $categories = [];
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog Admin - KDTech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #f8f9fa;
            padding: 20px 0;
        }
        .main-content {
            padding: 20px;
        }
        .post-image-preview {
            max-width: 100%;
            height: auto;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar">
                <h4 class="mb-4">KDTech Admin</h4>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="#" id="posts-tab">Posts</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" id="add-post-tab">Add New Post</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" id="categories-tab">Categories</a>
                    </li>
                    <li class="nav-item mt-4">
                        <a class="nav-link text-danger" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <!-- Posts List -->
                <div id="postsList">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Publicaciones</h2>
                        <button class="btn btn-primary" id="addNewPost">
                            <i class="fas fa-plus me-1"></i> Nuevo Post
                        </button>
                    </div>
                    
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    
                    <div class="card shadow-sm">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="40%">Título</th>
                                            <th width="15%">Categoría</th>
                                            <th width="15%">Estado</th>
                                            <th width="15%">Fecha</th>
                                            <th width="15%" class="text-end">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($posts)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-4">
                                                    <div class="text-muted">No hay publicaciones aún</div>
                                                    <button class="btn btn-primary mt-2" id="addFirstPost">
                                                        <i class="fas fa-plus me-1"></i> Crear primera publicación
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($posts as $post): ?>
                                                <tr>
                                                    <td class="align-middle">
                                                        <div class="fw-medium"><?= htmlspecialchars($post['title']) ?></div>
                                                        <?php if (!empty($post['excerpt'])): ?>
                                                            <div class="text-muted small mt-1"><?= mb_substr(strip_tags($post['excerpt']), 0, 60) ?>...</div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="align-middle">
                                                        <?= !empty($post['category_name']) ? htmlspecialchars($post['category_name']) : '<span class="text-muted">Sin categoría</span>' ?>
                                                    </td>
                                                    <td class="align-middle">
                                                        <span class="badge rounded-pill bg-<?= $post['status'] === 'published' ? 'success' : 'secondary' ?>">
                                                            <?= $post['status'] === 'published' ? 'Publicado' : 'Borrador' ?>
                                                        </span>
                                                    </td>
                                                    <td class="align-middle">
                                                        <div class="small"><?= date('d/m/Y', strtotime($post['created_at'])) ?></div>
                                                        <div class="text-muted small"><?= date('H:i', strtotime($post['created_at'])) ?></div>
                                                    </td>
                                                    <td class="text-end align-middle">
                                                        <div class="btn-group btn-group-sm" role="group">
                                                            <a href="edit_post.php?id=<?= $post['id'] ?>" class="btn btn-outline-primary" title="Editar">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <button type="button" class="btn btn-outline-danger delete-post" data-id="<?= $post['id'] ?>" title="Eliminar">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                            <?php if ($post['status'] === 'published'): ?>
                                                                <a href="../blog/<?= urlencode($post['slug']) ?>" class="btn btn-outline-success" target="_blank" title="Ver">
                                                                    <i class="fas fa-eye"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add New Post Form (initially hidden) -->
                <div id="newPostForm" style="display: none;">
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h2>Add New Post</h2>
                        <button class="btn btn-secondary" id="back-to-posts">Back to Posts</button>
                    </div>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="add_post">
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="title" class="form-label">Título</label>
                            <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="excerpt" class="form-label">Resumen</label>
                            <textarea class="form-control" id="excerpt" name="excerpt" rows="3"><?= htmlspecialchars($_POST['excerpt'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="content" class="form-label">Contenido</label>
                            <textarea class="form-control tinymce" id="content" name="content" rows="10"><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="category_id" class="form-label">Categoría</label>
                            <select class="form-select" id="category_id" name="category_id">
                                <option value="">Sin categoría</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>" <?= (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="featured_image" class="form-label">Imagen destacada (URL)</label>
                            <input type="text" class="form-control" id="featured_image" name="featured_image" value="<?= htmlspecialchars($_POST['featured_image'] ?? '') ?>">
                            <div class="form-text">Introduce la URL completa de la imagen</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Estado</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="status" id="status_draft" value="draft" <?= (!isset($_POST['status']) || $_POST['status'] === 'draft') ? 'checked' : '' ?>>
                                <label class="form-check-label" for="status_draft">
                                    Borrador
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="status" id="status_published" value="published" <?= (isset($_POST['status']) && $_POST['status'] === 'published') ? 'checked' : '' ?>>
                                <label class="form-check-label" for="status_published">
                                    Publicado
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="tags" class="form-label">Etiquetas (separadas por comas)</label>
                            <input type="text" class="form-control" id="tags" name="tags" 
                                   value="<?= isset($_POST['tags']) ? htmlspecialchars(implode(', ', $_POST['tags'])) : '' ?>" 
                                   placeholder="ejemplo: php, programación, web">
                        </div>
                        
                        <div class="card mb-3">
                            <div class="card-header">
                                <h5 class="mb-0">Configuración SEO</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="meta_title" class="form-label">Meta Título</label>
                                    <input type="text" class="form-control" id="meta_title" name="meta_title" value="<?= htmlspecialchars($_POST['meta_title'] ?? '') ?>">
                                    <div class="form-text">Si se deja vacío, se usará el título del post</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="meta_description" class="form-label">Meta Descripción</label>
                                    <textarea class="form-control" id="meta_description" name="meta_description" rows="2"><?= htmlspecialchars($_POST['meta_description'] ?? '') ?></textarea>
                                    <div class="form-text">Máximo 160 caracteres</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="meta_keywords" class="form-label">Palabras Clave (separadas por comas)</label>
                                    <input type="text" class="form-control" id="meta_keywords" name="meta_keywords" value="<?= htmlspecialchars($_POST['meta_keywords'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="reset" class="btn btn-outline-secondary me-md-2">Limpiar</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Guardar Post
                            </button>
                        </div>
                    </form>

                    <!-- TinyMCE Script -->
                    <!-- TinyMCE from CDN (No API key required) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/5.10.5/tinymce.min.js" integrity="sha512-JqJ8KfT6aI6f8k2Cp8wzl5RxPTF4KwdVbqaaUvZLagtN6Z2QOK5M8sq+LgA6FypU2DdWQfqQr+8RqjN2Zt1mQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            // Initialize TinyMCE with free configuration
                            tinymce.init({
                                selector: 'textarea.tinymce',
                                plugins: [
                                    'advlist autolink lists link image charmap print preview anchor',
                                    'searchreplace visualblocks code fullscreen',
                                    'insertdatetime media table paste code help wordcount'
                                ],
                                toolbar: 'undo redo | formatselect | ' +
                                'bold italic backcolor | alignleft aligncenter ' +
                                'alignright alignjustify | bullist numlist outdent indent | ' +
                                'removeformat | help',
                                height: 400,
                                menubar: false,
                                content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; font-size: 14px; }',
                                setup: function(editor) {
                                    editor.on('change', function() {
                                        editor.save();
                                    });
                                },
                                // Disable API key warning
                                branding: false,
                                // Enable browser spellcheck
                                browser_spellcheck: true,
                                // Enable image upload
                                images_upload_url: 'postAcceptor.php',
                                // Enable automatic uploads of images represented by blob or data URIs
                                automatic_uploads: true,
                                // Custom file picker
                                file_picker_types: 'image',
                                // Custom file picker callback
                                file_picker_callback: function (cb, value, meta) {
                                    var input = document.createElement('input');
                                    input.setAttribute('type', 'file');
                                    input.setAttribute('accept', 'image/*');

                                    input.onchange = function () {
                                        var file = this.files[0];
                                        var reader = new FileReader();
                                        
                                        reader.onload = function () {
                                            var id = 'blobid' + (new Date()).getTime();
                                            var blobCache = tinymce.activeEditor.editorUpload.blobCache;
                                            var base64 = reader.result.split(',')[1];
                                            var blobInfo = blobCache.create(id, file, base64);
                                            blobCache.add(blobInfo);
                                            cb(blobInfo.blobUri(), { title: file.name });
                                        };
                                        reader.readAsDataURL(file);
                                    };
                                    
                                    input.click();
                                }
                            });

                            // Toggle between posts list and new post form
                            const postsList = document.getElementById('postsList');
                            const newPostForm = document.getElementById('newPostForm');
                            
                            // Debug: Mostrar en consola si los elementos se encontraron
                            console.log('postsList:', postsList);
                            console.log('newPostForm:', newPostForm);
                            const addNewPostBtn = document.getElementById('addNewPost');
                            const addFirstPostBtn = document.getElementById('addFirstPost');
                            const backToPostsBtn = document.getElementById('backToPosts');

                            function showNewPostForm() {
                                if (postsList) postsList.style.display = 'none';
                                if (newPostForm) newPostForm.style.display = 'block';
                                window.scrollTo(0, 0);
                            }

                            function showPostsList() {
                                if (newPostForm) newPostForm.style.display = 'none';
                                if (postsList) postsList.style.display = 'block';
                                window.scrollTo(0, 0);
                            }

                            // Add event listeners
                            if (addNewPostBtn) {
                                addNewPostBtn.addEventListener('click', function(e) {
                                    e.preventDefault();
                                    showNewPostForm();
                                });
                            }

                            if (addFirstPostBtn) {
                                addFirstPostBtn.addEventListener('click', function(e) {
                                    e.preventDefault();
                                    showNewPostForm();
                                });
                            }

                            if (backToPostsBtn) {
                                backToPostsBtn.addEventListener('click', function(e) {
                                    e.preventDefault();
                                    showPostsList();
                                });
                            }

                            // Handle delete post buttons
                            document.querySelectorAll('.delete-post').forEach(button => {
                                button.addEventListener('click', function() {
                                    if (confirm('¿Estás seguro de que deseas eliminar este post? Esta acción no se puede deshacer.')) {
                                        const postId = this.getAttribute('data-id');
                                        const form = document.createElement('form');
                                        form.method = 'POST';
                                        form.action = 'delete_post.php';
                                        
                                        const input = document.createElement('input');
                                        input.type = 'hidden';
                                        input.name = 'post_id';
                                        input.value = postId;
                                        
                                        form.appendChild(input);
                                        document.body.appendChild(form);
                                        form.submit();
                                    }
                                });
                            });

                            // Auto-hide alerts after 5 seconds
                            setTimeout(() => {
                                const alerts = document.querySelectorAll('.alert');
                                alerts.forEach(alert => {
                                    const fadeEffect = setInterval(() => {
                                        if (!alert.style.opacity) {
                                            alert.style.opacity = 1;
                                        }
                                        if (alert.style.opacity > 0) {
                                            alert.style.opacity -= 0.1;
                                        } else {
                                            clearInterval(fadeEffect);
                                            alert.style.display = 'none';
                                        }
                                    }, 50);
                                });
                            }, 5000);

                            // Show posts list by default
                            if (window.location.hash !== '#new') {
                                showPostsList();
                            } else {
                                showNewPostForm();
                            }
                        });
                    </script>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.tiny.cloud/1/YOUR_TINYMCE_API_KEY/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        // Initialize TinyMCE
        tinymce.init({
            selector: '#content',
            plugins: 'advlist autolink lists link image charmap preview anchor',
            toolbar_mode: 'floating',
            height: 400
        });

        // Toggle between posts list and add post form
        document.getElementById('add-new-post').addEventListener('click', function() {
            document.getElementById('posts-section').style.display = 'none';
            document.getElementById('add-post-section').style.display = 'block';
        });

        document.getElementById('back-to-posts').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('posts-section').style.display = 'block';
            document.getElementById('add-post-section').style.display = 'none';
        });

        // Image preview
        document.getElementById('featured_image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('image-preview');
                    preview.innerHTML = `<img src="${e.target.result}" class="post-image-preview" alt="Preview">`;
                }
                reader.readAsDataURL(file);
            }
        });

        // Handle delete post
        document.querySelectorAll('.delete-post').forEach(button => {
            button.addEventListener('click', function() {
                if (confirm('Are you sure you want to delete this post?')) {
                    const postId = this.getAttribute('data-id');
                    // Add AJAX call to delete post
                    fetch('delete_post.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `id=${postId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.closest('tr').remove();
                        } else {
                            alert('Error deleting post');
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>
