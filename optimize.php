<?php
/**
 * Asset Optimization Script
 *
 * Run from the command line or visit via the browser to minify CSS/JS
 * and convert images to WebP.
 *
 * CLI:   php optimize.php
 * Web:   https://yoursite.com/optimize.php  (restrict access in production!)
 */

// Only allow CLI or local/dev access
$isCli = (php_sapi_name() === 'cli');
if (!$isCli) {
    // In a web context, restrict to localhost or development environments
    require_once __DIR__ . '/lib/Config.php';
    Config::load();

    $isLocal = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']);
    $isDev   = getenv('ENVIRONMENT') !== 'production';

    if (!$isLocal && !$isDev) {
        http_response_code(403);
        echo 'Access denied. Run this script from the CLI or in a development environment.';
        exit(1);
    }
}

require_once __DIR__ . '/lib/AssetOptimizer.php';

// Run optimization
$optimizer = new AssetOptimizer(__DIR__);
$summary   = $optimizer->optimize();

// Output results
if ($isCli) {
    echo "=== Asset Optimization Results ===\n\n";

    if (!empty($summary['css'])) {
        echo "CSS Minified:\n";
        foreach ($summary['css'] as $entry) {
            echo "  ✓ {$entry}\n";
        }
        echo "\n";
    }

    if (!empty($summary['js'])) {
        echo "JS Minified:\n";
        foreach ($summary['js'] as $entry) {
            echo "  ✓ {$entry}\n";
        }
        echo "\n";
    }

    if (!empty($summary['webp'])) {
        echo "WebP Converted:\n";
        foreach ($summary['webp'] as $entry) {
            echo "  ✓ {$entry}\n";
        }
        echo "\n";
    }

    if ($optimizer->canConvertWebp()) {
        if (empty($summary['webp'])) {
            echo "WebP: No images found to convert.\n\n";
        }
    } else {
        echo "WebP: Skipped (GD with WebP support or Imagick not available).\n\n";
    }

    if (!empty($summary['errors'])) {
        echo "Errors:\n";
        foreach ($summary['errors'] as $error) {
            echo "  ✗ {$error}\n";
        }
        echo "\n";
    }

    $total = count($summary['css']) + count($summary['js']) + count($summary['webp']);
    echo "Done. {$total} file(s) optimized.\n";
} else {
    // HTML output for browser
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html><head><title>Asset Optimization</title>';
    echo '<style>body{font-family:monospace;max-width:700px;margin:2rem auto;padding:1rem}';
    echo '.ok{color:green}.err{color:red}h1{font-size:1.2rem}</style></head><body>';
    echo '<h1>Asset Optimization Results</h1>';

    if (!empty($summary['css'])) {
        echo '<h2>CSS Minified</h2><ul>';
        foreach ($summary['css'] as $entry) {
            echo '<li class="ok">' . htmlspecialchars($entry) . '</li>';
        }
        echo '</ul>';
    }

    if (!empty($summary['js'])) {
        echo '<h2>JS Minified</h2><ul>';
        foreach ($summary['js'] as $entry) {
            echo '<li class="ok">' . htmlspecialchars($entry) . '</li>';
        }
        echo '</ul>';
    }

    if (!empty($summary['webp'])) {
        echo '<h2>WebP Converted</h2><ul>';
        foreach ($summary['webp'] as $entry) {
            echo '<li class="ok">' . htmlspecialchars($entry) . '</li>';
        }
        echo '</ul>';
    }

    if (!$optimizer->canConvertWebp()) {
        echo '<p>WebP conversion skipped (GD with WebP support or Imagick not available).</p>';
    } elseif (empty($summary['webp'])) {
        echo '<p>No images found to convert to WebP.</p>';
    }

    if (!empty($summary['errors'])) {
        echo '<h2>Errors</h2><ul>';
        foreach ($summary['errors'] as $error) {
            echo '<li class="err">' . htmlspecialchars($error) . '</li>';
        }
        echo '</ul>';
    }

    $total = count($summary['css']) + count($summary['js']) + count($summary['webp']);
    echo "<p><strong>Done.</strong> {$total} file(s) optimized.</p>";
    echo '</body></html>';
}
