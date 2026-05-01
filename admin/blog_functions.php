<?php
session_start();
require_once 'db_connection.php';

class BlogManager {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // Create all necessary blog tables if they don't exist
    public function createTables() {
        $tables = [
            'blog_categories' => "
                CREATE TABLE IF NOT EXISTS `blog_categories` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `name` varchar(100) NOT NULL,
                    `slug` varchar(100) NOT NULL,
                    `description` text DEFAULT NULL,
                    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `slug` (`slug`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            'blog_posts' => "
                CREATE TABLE IF NOT EXISTS `blog_posts` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `title` varchar(255) NOT NULL,
                    `slug` varchar(255) NOT NULL,
                    `content` longtext NOT NULL,
                    `excerpt` text DEFAULT NULL,
                    `category_id` int(11) DEFAULT NULL,
                    `featured_image` varchar(255) DEFAULT NULL,
                    `meta_title` varchar(255) DEFAULT NULL,
                    `meta_description` text DEFAULT NULL,
                    `meta_keywords` text DEFAULT NULL,
                    `status` enum('draft','published') DEFAULT 'draft',
                    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                    `published_at` timestamp NULL DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `slug` (`slug`),
                    KEY `category_id` (`category_id`),
                    CONSTRAINT `blog_posts_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `blog_categories` (`id`) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            'blog_tags' => "
                CREATE TABLE IF NOT EXISTS `blog_tags` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `name` varchar(50) NOT NULL,
                    `slug` varchar(100) NOT NULL,
                    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `slug` (`slug`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
            'blog_post_tags' => "
                CREATE TABLE IF NOT EXISTS `blog_post_tags` (
                    `post_id` int(11) NOT NULL,
                    `tag_id` int(11) NOT NULL,
                    PRIMARY KEY (`post_id`,`tag_id`),
                    KEY `tag_id` (`tag_id`),
                    CONSTRAINT `blog_post_tags_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `blog_posts` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `blog_post_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `blog_tags` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        ];

        foreach ($tables as $table => $sql) {
            try {
                $this->conn->exec($sql);
            } catch (PDOException $e) {
                error_log("Error creating table {$table}: " . $e->getMessage());
                throw $e;
            }
        }

        // Add default category if none exists
        $stmt = $this->conn->query("SELECT COUNT(*) FROM blog_categories");
        if ($stmt->fetchColumn() == 0) {
            $this->conn->exec("INSERT INTO blog_categories (name, slug, description) VALUES ('General', 'general', 'General blog posts')");
        }
    }

    // Add a new blog post
    public function addPost($data) {
        $slug = $this->createSlug($data['title']);
        $published_at = ($data['status'] === 'published') ? date('Y-m-d H:i:s') : null;
        
        $sql = "INSERT INTO blog_posts 
                (title, slug, content, excerpt, category_id, featured_image, 
                 meta_title, meta_description, meta_keywords, status, published_at) 
                VALUES 
                (:title, :slug, :content, :excerpt, :category_id, :featured_image, 
                 :meta_title, :meta_description, :meta_keywords, :status, :published_at)";
        
        $stmt = $this->conn->prepare($sql);
        
        $result = $stmt->execute([
            ':title' => $data['title'],
            ':slug' => $slug,
            ':content' => $data['content'],
            ':excerpt' => $data['excerpt'] ?? '',
            ':category_id' => $data['category_id'] ?? null,
            ':featured_image' => $data['featured_image'] ?? null,
            ':meta_title' => $data['meta_title'] ?? '',
            ':meta_description' => $data['meta_description'] ?? '',
            ':meta_keywords' => $data['meta_keywords'] ?? '',
            ':status' => $data['status'] ?? 'draft',
            ':published_at' => $published_at
        ]);
        
        if ($result && !empty($data['tags'])) {
            $post_id = $this->conn->lastInsertId();
            $this->addPostTags($post_id, $data['tags']);
        }
        
        return $result ? $this->conn->lastInsertId() : false;
    }

    
    public function getPosts($limit = 10, $offset = 0, $status = 'published') {
        $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug 
                FROM blog_posts p 
                LEFT JOIN blog_categories c ON p.category_id = c.id 
                WHERE p.status = :status 
                ORDER BY p.created_at DESC 
                LIMIT :limit OFFSET :offset";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getPostBySlug($slug) {
        $stmt = $this->conn->prepare("SELECT p.*, c.name as category_name, c.slug as category_slug 
                                    FROM blog_posts p 
                                    LEFT JOIN blog_categories c ON p.category_id = c.id 
                                    WHERE p.slug = :slug");
        $stmt->execute([':slug' => $slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function createSlug($text) {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);
        return $text;
    }
}

// Initialize blog manager
try {
    $blogManager = new BlogManager($conn);
    $blogManager->createTables();
} catch(PDOException $e) {
    error_log("Blog Manager Error: " . $e->getMessage());
}
?>
