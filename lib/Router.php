<?php

// Router class
class Router {
    private $markdown;

    public function __construct() {
        $this->markdown = new SimpleMarkdown();
    }

    public function route() {
        $path = $this->getPath();

        // RSS feed
        if ($path === '/rss.xml') {
            return $this->handleRss();
        }

        // API routes
        if (strpos($path, '/api/') === 0) {
            return $this->handleAPI($path);
        }

        // Blog/markdown routes
        if (strpos($path, '/blog/') === 0) {
            return $this->handleBlog($path);
        }

        // Home page
        if ($path === '/' || $path === '') {
            return $this->handleHome();
        }

        // Generic pages (e.g. /about -> templates/about.php)
        if ($this->handleGenericPage($path)) {
            return;
        }

        // 404
        return $this->handle404();
    }

    private function handleGenericPage($path) {
        $slug = trim($path, '/');
        $slug = preg_replace('/[^a-zA-Z0-9_-]/', '', $slug); // Sanitize

        if (empty($slug)) return false;

        $templateFile = TEMPLATES_DIR . '/' . $slug . '.php';

        // Prevent path traversal
        $realPath = realpath($templateFile);
        $realTemplateDir = realpath(TEMPLATES_DIR);

        if (!$realPath || strpos($realPath, $realTemplateDir) !== 0) {
            return false;
        }

        if (file_exists($templateFile)) {
            $this->renderTemplate($slug);
            return true;
        }

        return false;
    }

    private function getPath() {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($path, PHP_URL_PATH);
        $path = rtrim($path, '/');
        return $path ?: '/';
    }

    private function handleAPI($path) {
        // Rate limiting check
        if ($this->checkRateLimit('api')) {
            return;
        }

        // Extract API endpoint: /api/daily-js-data -> daily-js-data
        $endpoint = substr($path, 5); // Remove '/api/'

        // Sanitize endpoint name
        $endpoint = preg_replace('/[^a-zA-Z0-9_-]/', '', $endpoint);

        // Validate endpoint is not empty after sanitization
        if (empty($endpoint)) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid endpoint']);
            return;
        }

        // Look for JSON file
        $jsonFile = API_DATA_DIR . '/' . $endpoint . '.json';

        // Prevent path traversal
        $realPath = realpath($jsonFile);
        $realApiDir = realpath(API_DATA_DIR);

        if (!$realPath || strpos($realPath, $realApiDir) !== 0) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Endpoint not found']);
            return;
        }

        if (!file_exists($jsonFile)) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Endpoint not found']);
            return;
        }

        // File size limit (1MB)
        if (filesize($jsonFile) > 1048576) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Data file too large']);
            return;
        }

        // Read and serve JSON
        $jsonData = file_get_contents($jsonFile);

        // Validate JSON
        json_decode($jsonData);
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid JSON data']);
            return;
        }

        // Set headers
        header('Content-Type: application/json');
        header('X-Content-Type-Options: nosniff');

        // CORS - Restrict to same origin by default (configure as needed)
        $allowedOrigins = defined('ALLOWED_ORIGINS') ? ALLOWED_ORIGINS : [];
        if (!empty($allowedOrigins) && isset($_SERVER['HTTP_ORIGIN'])) {
            if (in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins)) {
                header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
            }
        }

        if (CACHE_ENABLED) {
            $lastModified = filemtime($jsonFile);
            $etag = md5_file($jsonFile);

            header('Cache-Control: public, max-age=' . CACHE_TIME);
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');
            header('ETag: "' . $etag . '"');

            // Check if client has cached version
            if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === '"' . $etag . '"') {
                http_response_code(304);
                return;
            }
        }

        echo $jsonData;
    }

    private function handleBlog($path) {
        // Rate limiting check
        if ($this->checkRateLimit('blog')) {
            return;
        }

        // Extract slug: /blog/my-post -> my-post
        $slug = substr($path, 6); // Remove '/blog/'

        // Sanitize slug
        $slug = preg_replace('/[^a-zA-Z0-9_-]/', '', $slug);

        // Validate slug is not empty after sanitization
        if (empty($slug)) {
            return $this->handle404();
        }

        // Look for markdown file
        $mdFile = BLOG_DIR . '/' . $slug . '.md';

        // Prevent path traversal
        $realPath = realpath($mdFile);
        $realBlogDir = realpath(BLOG_DIR);

        if (!$realPath || strpos($realPath, $realBlogDir) !== 0) {
            return $this->handle404();
        }

        if (!file_exists($mdFile)) {
            return $this->handle404();
        }

        // File size limit (500KB)
        if (filesize($mdFile) > 512000) {
            http_response_code(500);
            $this->renderTemplate('404', ['title' => 'Content Too Large']);
            return;
        }

        // Parse markdown
        $markdown = file_get_contents($mdFile);

        // Extract frontmatter (optional)
        $frontmatter = [];
        $content = $markdown;

        if (preg_match('/^---\s*\n(.*?)\n---\s*\n(.*)$/s', $markdown, $matches)) {
            // Parse YAML-like frontmatter
            $frontmatterText = $matches[1];
            $content = $matches[2];

            foreach (explode("\n", $frontmatterText) as $line) {
                if (preg_match('/^(\w+):\s*(.+)$/', $line, $m)) {
                    $key = trim($m[1]);
                    $value = trim($m[2]);
                    // Only allow safe frontmatter keys
                    if (in_array($key, ['title', 'date', 'author', 'description'])) {
                        $frontmatter[$key] = $value;
                    }
                }
            }
        }

        // Convert markdown to HTML
        try {
            $html = $this->markdown->text($content);
        } catch (Exception $e) {
            error_log('Markdown parsing error: ' . $e->getMessage());
            http_response_code(500);
            $this->renderTemplate('404', ['title' => 'Error Processing Content']);
            return;
        }

        // Load template
        $this->renderTemplate('blog', [
            'title' => $frontmatter['title'] ?? ucwords(str_replace('-', ' ', $slug)),
            'content' => $html,
            'date' => $frontmatter['date'] ?? '',
            'author' => $frontmatter['author'] ?? '',
            'slug' => $slug
        ]);
    }

    private function handleHome() {
        // Get list of blog posts
        $posts = [];
        if (is_dir(BLOG_DIR)) {
            $files = glob(BLOG_DIR . '/*.md');
            foreach ($files as $file) {
                $slug = basename($file, '.md');
                $content = file_get_contents($file);

                // Extract frontmatter
                $title = ucwords(str_replace('-', ' ', $slug));
                $date = '';

                if (preg_match('/^---\s*\n(.*?)\n---/s', $content, $matches)) {
                    if (preg_match('/title:\s*(.+)$/m', $matches[1], $m)) {
                        $title = trim($m[1]);
                    }
                    if (preg_match('/date:\s*(.+)$/m', $matches[1], $m)) {
                        $date = trim($m[1]);
                    }
                }

                $posts[] = [
                    'slug' => $slug,
                    'title' => $title,
                    'date' => $date
                ];
            }

            // Sort by date (newest first)
            usort($posts, function($a, $b) {
                return strcmp($b['date'], $a['date']);
            });
        }

        $this->renderTemplate('home', ['posts' => $posts]);
    }

    private function handleRss() {
        // Rate limiting check
        if ($this->checkRateLimit('rss')) {
            return;
        }

        header('Content-Type: application/rss+xml; charset=UTF-8');

        if (CACHE_ENABLED) {
            header('Cache-Control: public, max-age=' . CACHE_TIME);
        }

        $feed = new RssFeed();
        echo $feed->generate();
    }

    private function handle404() {
        http_response_code(404);
        $this->renderTemplate('404', []);
    }

    private function renderTemplate($template, $data = []) {
        // Sanitize template name to prevent path traversal
        $template = preg_replace('/[^a-zA-Z0-9_-]/', '', $template);

        if (empty($template)) {
            echo "Invalid template";
            return;
        }

        $templateFile = TEMPLATES_DIR . '/' . $template . '.php';

        // Prevent path traversal
        $realPath = realpath($templateFile);
        $realTemplateDir = realpath(TEMPLATES_DIR);

        if (!$realPath || strpos($realPath, $realTemplateDir) !== 0) {
            echo "Template not found";
            return;
        }

        if (!file_exists($templateFile)) {
            echo "Template not found: " . htmlspecialchars($template);
            return;
        }

        // Use safer variable passing instead of extract()
        $pageTitle = $data['title'] ?? 'My Site';
        $content = '';
        $blogContent = $data['content'] ?? '';
        $posts = $data['posts'] ?? [];
        $title = $data['title'] ?? '';
        $date = $data['date'] ?? '';
        $author = $data['author'] ?? '';
        $slug = $data['slug'] ?? '';
        $bodyClass = $data['bodyClass'] ?? '';

        // Start output buffering to capture template content
        ob_start();
        require $templateFile;
        $content = ob_get_clean();

        // Determine layout (default to _layout.php)
        $layout = '_layout';
        $layoutFile = TEMPLATES_DIR . '/' . $layout . '.php';

        if (file_exists($layoutFile)) {
            require $layoutFile;
        } else {
            // If no layout exists, just echo content
            echo $content;
        }
    }

    private function checkRateLimit($type) {
        if (!defined('RATE_LIMIT_ENABLED') || !RATE_LIMIT_ENABLED) {
            return false;
        }

        $ip = $this->getClientIP();
        $key = 'ratelimit_' . $type . '_' . $ip;
        $limit = defined('RATE_LIMIT_REQUESTS') ? RATE_LIMIT_REQUESTS : 60;
        $window = defined('RATE_LIMIT_WINDOW') ? RATE_LIMIT_WINDOW : 60;

        // Simple file-based rate limiting
        $cacheDir = sys_get_temp_dir() . '/php_router_cache';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0700, true);
        }

        $cacheFile = $cacheDir . '/' . md5($key) . '.txt';

        $requests = [];
        if (file_exists($cacheFile)) {
            $data = file_get_contents($cacheFile);
            $requests = json_decode($data, true) ?: [];
        }

        // Remove old requests outside the window
        $now = time();
        $requests = array_filter($requests, function($timestamp) use ($now, $window) {
            return ($now - $timestamp) < $window;
        });

        // Check if limit exceeded
        if (count($requests) >= $limit) {
            http_response_code(429);
            header('Content-Type: application/json');
            header('Retry-After: ' . $window);
            echo json_encode(['error' => 'Rate limit exceeded. Please try again later.']);
            return true;
        }

        // Add current request
        $requests[] = $now;
        file_put_contents($cacheFile, json_encode($requests), LOCK_EX);

        return false;
    }

    private function getClientIP() {
        // Get real IP address (considering proxies)
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        // Only trust proxy headers if explicitly configured
        if (defined('TRUST_PROXY') && TRUST_PROXY) {
            if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $ip = trim($ips[0]);
            } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
                $ip = $_SERVER['HTTP_X_REAL_IP'];
            }
        }

        return $ip;
    }
}
