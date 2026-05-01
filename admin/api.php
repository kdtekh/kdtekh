<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once 'config.php';
require_once 'auth.php';

// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/admin/api.php', '', $path);
$path_parts = explode('/', trim($path, '/'));

// Get request data
$data = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    $data = [];
}

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

// API Authentication
$headers = getallheaders();
$api_key = $headers['Authorization'] ?? ($_GET['api_key'] ?? '');

// Validate API key (you should implement proper authentication)
function validateApiKey($api_key) {
    // TODO: Implement proper API key validation
    // For now, we'll just check if it's not empty
    return !empty($api_key);
}

// Main API router
try {
    // Public endpoints (no auth required)
    if ($method === 'GET' && $path_parts[0] === 'posts') {
        require_once 'blog_functions_new.php';
        $blogManager = new BlogManager($pdo);
        
        // Get all published posts
        if (count($path_parts) === 1) {
            $page = $_GET['page'] ?? 1;
            $per_page = $_GET['per_page'] ?? 10;
            $category = $_GET['category'] ?? null;
            $tag = $_GET['tag'] ?? null;
            
            $posts = $blogManager->getPublishedPosts($page, $per_page, $category, $tag);
            $response['success'] = true;
            $response['data'] = $posts;
        } 
        // Get single post by slug
        elseif (count($path_parts) === 2 && is_numeric($path_parts[1])) {
            $post = $blogManager->getPostById($path_parts[1]);
            if ($post) {
                $response['success'] = true;
                $response['data'] = $post;
            } else {
                http_response_code(404);
                $response['message'] = 'Post not found';
            }
        }
    }
    
    // Protected endpoints (require valid API key)
    elseif (validateApiKey($api_key)) {
        require_once 'blog_functions_new.php';
        $blogManager = new BlogManager($pdo);
        
        // Posts collection
        if ($path_parts[0] === 'posts') {
            // Create new post
            if ($method === 'POST' && count($path_parts) === 1) {
                $post_data = [
                    'title' => $data['title'] ?? '',
                    'content' => $data['content'] ?? '',
                    'excerpt' => $data['excerpt'] ?? '',
                    'category_id' => $data['category_id'] ?? null,
                    'featured_image' => $data['featured_image'] ?? null,
                    'meta_title' => $data['meta_title'] ?? '',
                    'meta_description' => $data['meta_description'] ?? '',
                    'meta_keywords' => $data['meta_keywords'] ?? '',
                    'status' => $data['status'] ?? 'draft',
                    'tags' => $data['tags'] ?? []
                ];
                
                $post_id = $blogManager->addPost($post_data);
                if ($post_id) {
                    $response['success'] = true;
                    $response['message'] = 'Post created successfully';
                    $response['data'] = ['id' => $post_id];
                    http_response_code(201);
                } else {
                    throw new Exception('Failed to create post');
                }
            }
            // Update post
            elseif ($method === 'PUT' && count($path_parts) === 2 && is_numeric($path_parts[1])) {
                $post_id = $path_parts[1];
                $post_data = [
                    'id' => $post_id,
                    'title' => $data['title'] ?? '',
                    'content' => $data['content'] ?? '',
                    'excerpt' => $data['excerpt'] ?? '',
                    'category_id' => $data['category_id'] ?? null,
                    'featured_image' => $data['featured_image'] ?? null,
                    'meta_title' => $data['meta_title'] ?? '',
                    'meta_description' => $data['meta_description'] ?? '',
                    'meta_keywords' => $data['meta_keywords'] ?? '',
                    'status' => $data['status'] ?? 'draft',
                    'tags' => $data['tags'] ?? []
                ];
                
                if ($blogManager->updatePost($post_data)) {
                    $response['success'] = true;
                    $response['message'] = 'Post updated successfully';
                } else {
                    throw new Exception('Failed to update post');
                }
            }
            // Delete post
            elseif ($method === 'DELETE' && count($path_parts) === 2 && is_numeric($path_parts[1])) {
                if ($blogManager->deletePost($path_parts[1])) {
                    $response['success'] = true;
                    $response['message'] = 'Post deleted successfully';
                } else {
                    throw new Exception('Failed to delete post');
                }
            } else {
                http_response_code(405);
                $response['message'] = 'Method not allowed';
            }
        } 
        // Categories
        elseif ($path_parts[0] === 'categories') {
            // List all categories
            if ($method === 'GET' && count($path_parts) === 1) {
                $categories = $blogManager->getCategories();
                $response['success'] = true;
                $response['data'] = $categories;
            }
            // Create category
            elseif ($method === 'POST' && count($path_parts) === 1) {
                $category_data = [
                    'name' => $data['name'] ?? '',
                    'description' => $data['description'] ?? ''
                ];
                
                $category_id = $blogManager->addCategory($category_data);
                if ($category_id) {
                    $response['success'] = true;
                    $response['message'] = 'Category created successfully';
                    $response['data'] = ['id' => $category_id];
                    http_response_code(201);
                } else {
                    throw new Exception('Failed to create category');
                }
            } else {
                http_response_code(405);
                $response['message'] = 'Method not allowed';
            }
        } else {
            http_response_code(404);
            $response['message'] = 'Endpoint not found';
        }
    } else {
        http_response_code(401);
        $response['message'] = 'Unauthorized';
    }
} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = $e->getMessage();
}

// Send JSON response
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
