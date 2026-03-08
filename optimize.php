<?php
/**
 * Asset Optimization Script (CLI only)
 *
 * Minify CSS/JS and convert images to WebP.
 *
 * Usage:  php optimize.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit;
}

require_once __DIR__ . '/lib/AssetOptimizer.php';

// run optimization
$optimizer = new AssetOptimizer(__DIR__);
$summary   = $optimizer->optimize();

// output results
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
