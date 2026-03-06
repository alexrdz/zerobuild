<?php
/**
 * Simple PHP Static Site Generator & Router
 * Handles markdown pages, JSON APIs, and static routing
 */

// Error handling - hide errors in production
if (getenv('ENVIRONMENT') === 'production') {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

// Autoload libraries
require_once __DIR__ . '/lib/Config.php';
require_once __DIR__ . '/lib/SimpleMarkdown.php';
require_once __DIR__ . '/lib/Router.php';
require_once __DIR__ . '/lib/RssFeed.php';

// Load environment configuration first so .env values are available
Config::load();

// Configuration (uses .env values when available, otherwise defaults)
define('TEMPLATES_DIR', __DIR__ . '/templates');
define('BLOG_DIR', __DIR__ . '/blog');
define('API_DATA_DIR', __DIR__ . '/api-data');
define('CACHE_ENABLED', getenv('CACHE_ENABLED') !== false
    ? filter_var(getenv('CACHE_ENABLED'), FILTER_VALIDATE_BOOLEAN)
    : true);
define('CACHE_TIME', getenv('CACHE_TIME') !== false
    ? (int) getenv('CACHE_TIME')
    : 3600);
define('RSS_MAX_ITEMS', getenv('RSS_MAX_ITEMS') !== false
    ? (int) getenv('RSS_MAX_ITEMS')
    : 20);

// Rate limiting configuration
define('RATE_LIMIT_ENABLED', getenv('RATE_LIMIT_ENABLED') !== false
    ? filter_var(getenv('RATE_LIMIT_ENABLED'), FILTER_VALIDATE_BOOLEAN)
    : true);
define('RATE_LIMIT_REQUESTS', getenv('RATE_LIMIT_REQUESTS') !== false
    ? (int) getenv('RATE_LIMIT_REQUESTS')
    : 60);
define('RATE_LIMIT_WINDOW', getenv('RATE_LIMIT_WINDOW') !== false
    ? (int) getenv('RATE_LIMIT_WINDOW')
    : 60);

// CORS configuration - parse comma-separated origins from .env or use empty array
$_origins = getenv('ALLOWED_ORIGINS');
define('ALLOWED_ORIGINS', !empty($_origins)
    ? array_map('trim', explode(',', $_origins))
    : []);
unset($_origins);

// Proxy configuration - only enable if behind trusted proxy
define('TRUST_PROXY', getenv('TRUST_PROXY') !== false
    ? filter_var(getenv('TRUST_PROXY'), FILTER_VALIDATE_BOOLEAN)
    : false);

// Handle PHP built-in server routing
if (php_sapi_name() === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($file)) {
        return false;
    }
}

// Global Security Headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("X-XSS-Protection: 1; mode=block");

// Content Security Policy - adjust as needed for your site
$csp = "default-src 'self'; " .
       "script-src 'self' 'unsafe-inline'; " .
       "style-src 'self' 'unsafe-inline'; " .
       "img-src 'self' data: https:; " .
       "font-src 'self'; " .
       "connect-src 'self'; " .
       "frame-ancestors 'none'; " .
       "base-uri 'self'; " .
       "form-action 'self' https://formspree.io;";
header("Content-Security-Policy: " . $csp);

// HSTS - only enable if using HTTPS
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
}

// Permissions Policy
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");

// Initialize and route
try {
    $router = new Router();
    $router->route();
} catch (Exception $e) {
    error_log('Router error: ' . $e->getMessage());
    http_response_code(500);
    if (getenv('ENVIRONMENT') !== 'production') {
        echo 'Error: ' . htmlspecialchars($e->getMessage());
    } else {
        echo 'An error occurred. Please try again later.';
    }
}
