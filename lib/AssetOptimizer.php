<?php

/**
 * Lightweight asset optimizer for shared hosting environments.
 *
 * Provides CSS/JS minification and image-to-WebP conversion using only
 * built-in PHP functions (GD or Imagick for images). No external tools
 * or Composer packages are required.
 */
class AssetOptimizer
{
    /** @var string Absolute path to the project root */
    private $basePath;

    /** @var string Absolute path to the assets source directory */
    private $assetsDir;

    /** @var string Absolute path to the optimized output directory */
    private $distDir;

    /** @var int WebP quality (0-100) */
    private $webpQuality = 80;

    public function __construct(string $basePath)
    {
        $this->basePath  = rtrim($basePath, '/');
        $this->assetsDir = $this->basePath . '/assets';
        $this->distDir   = $this->assetsDir . '/dist';
    }

    // ------------------------------------------------------------------
    //  Public API
    // ------------------------------------------------------------------

    /**
     * Run the full optimization pipeline and return a summary.
     *
     * @return array{css: string[], js: string[], webp: string[], errors: string[]}
     */
    public function optimize(): array
    {
        $summary = ['css' => [], 'js' => [], 'webp' => [], 'errors' => []];

        $this->ensureDistDir();

        // Minify CSS
        foreach ($this->findFiles($this->assetsDir, 'css') as $file) {
            try {
                $relative  = $this->relativePath($file);
                $minified  = $this->minifyCss(file_get_contents($file));
                $outFile   = $this->distPath($relative, 'min.css');
                $this->writeFile($outFile, $minified);
                $summary['css'][] = $relative . ' → ' . $this->relativePath($outFile);
            } catch (\Exception $e) {
                $summary['errors'][] = "CSS [{$file}]: " . $e->getMessage();
            }
        }

        // Minify JS
        foreach ($this->findFiles($this->assetsDir, 'js') as $file) {
            try {
                $relative  = $this->relativePath($file);
                $minified  = $this->minifyJs(file_get_contents($file));
                $outFile   = $this->distPath($relative, 'min.js');
                $this->writeFile($outFile, $minified);
                $summary['js'][] = $relative . ' → ' . $this->relativePath($outFile);
            } catch (\Exception $e) {
                $summary['errors'][] = "JS [{$file}]: " . $e->getMessage();
            }
        }

        // Convert images to WebP
        if ($this->canConvertWebp()) {
            foreach ($this->findImages($this->assetsDir) as $file) {
                try {
                    $relative = $this->relativePath($file);
                    $outFile  = $this->webpPath($relative);
                    $this->convertToWebp($file, $outFile);
                    $summary['webp'][] = $relative . ' → ' . $this->relativePath($outFile);
                } catch (\Exception $e) {
                    $summary['errors'][] = "WebP [{$file}]: " . $e->getMessage();
                }
            }
        }

        return $summary;
    }

    // ------------------------------------------------------------------
    //  CSS Minification
    // ------------------------------------------------------------------

    /**
     * Minify a CSS string by stripping comments and collapsing whitespace.
     */
    public function minifyCss(string $css): string
    {
        // Remove multi-line comments
        $css = preg_replace('!/\*.*?\*/!s', '', $css);

        // Collapse whitespace
        $css = preg_replace('/\s+/', ' ', $css);

        // Remove spaces around structural characters
        $css = preg_replace('/\s*([{}:;,>~+])\s*/', '$1', $css);

        // Remove trailing semicolons before closing braces
        $css = str_replace(';}', '}', $css);

        return trim($css);
    }

    // ------------------------------------------------------------------
    //  JS Minification
    // ------------------------------------------------------------------

    /**
     * Minify a JavaScript string.
     *
     * This is a lightweight minifier that strips comments and collapses
     * whitespace.  It is intentionally conservative to avoid breaking
     * code — it does NOT rename variables or perform advanced transforms.
     */
    public function minifyJs(string $js): string
    {
        // Remove multi-line comments (but not URLs like http://)
        $js = preg_replace('#/\*[\s\S]*?\*/#', '', $js);

        // Remove single-line comments (// ...) but not URLs (http://, https://)
        // Match // comments only when preceded by start-of-line or whitespace/semicolons
        $js = preg_replace('#(?<=^|[\s;,{}()\[\]])//[^\n]*#m', '', $js);

        // Collapse multiple blank lines and trim lines
        $lines = explode("\n", $js);
        $result = [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed !== '') {
                $result[] = $trimmed;
            }
        }
        $js = implode("\n", $result);

        // Collapse runs of whitespace (spaces/tabs) within each line
        $js = preg_replace('/[ \t]+/', ' ', $js);

        return trim($js);
    }

    // ------------------------------------------------------------------
    //  WebP Conversion
    // ------------------------------------------------------------------

    /**
     * Check whether the server can convert images to WebP.
     */
    public function canConvertWebp(): bool
    {
        // Check GD
        if (function_exists('imagewebp') && function_exists('imagecreatefromjpeg')) {
            return true;
        }

        // Check Imagick
        if (class_exists('Imagick')) {
            try {
                $formats = \Imagick::queryFormats('WEBP');
                if (!empty($formats)) {
                    return true;
                }
            } catch (\Exception $e) {
                // Imagick available but WebP not supported
            }
        }

        return false;
    }

    /**
     * Convert a JPEG or PNG image to WebP.
     */
    public function convertToWebp(string $sourcePath, string $destPath): void
    {
        if (!file_exists($sourcePath)) {
            throw new \RuntimeException("Source image not found: {$sourcePath}");
        }

        $ext = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));

        // Try GD first
        if (function_exists('imagewebp')) {
            $image = null;

            if (in_array($ext, ['jpg', 'jpeg']) && function_exists('imagecreatefromjpeg')) {
                $image = @imagecreatefromjpeg($sourcePath);
            } elseif ($ext === 'png' && function_exists('imagecreatefrompng')) {
                $image = @imagecreatefrompng($sourcePath);
                if ($image) {
                    // Preserve transparency by converting to true-color with alpha
                    imagepalettetotruecolor($image);
                    imagealphablending($image, true);
                    imagesavealpha($image, true);
                }
            }

            if ($image) {
                $dir = dirname($destPath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                $result = imagewebp($image, $destPath, $this->webpQuality);
                imagedestroy($image);

                if (!$result) {
                    throw new \RuntimeException("GD imagewebp() failed for: {$sourcePath}");
                }
                return;
            }
        }

        // Fall back to Imagick
        if (class_exists('Imagick')) {
            $imagick = new \Imagick($sourcePath);
            $imagick->setImageFormat('webp');
            $imagick->setImageCompressionQuality($this->webpQuality);

            $dir = dirname($destPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $imagick->writeImage($destPath);
            $imagick->clear();
            $imagick->destroy();
            return;
        }

        throw new \RuntimeException("No suitable image library available for WebP conversion.");
    }

    // ------------------------------------------------------------------
    //  File helpers
    // ------------------------------------------------------------------

    /**
     * Recursively find files by extension, excluding the dist/ directory.
     *
     * @return string[]
     */
    private function findFiles(string $dir, string $extension): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            // Skip anything inside the dist directory
            if (strpos($file->getPathname(), $this->distDir) === 0) {
                continue;
            }
            if ($file->isFile() && strtolower($file->getExtension()) === $extension) {
                $files[] = $file->getPathname();
            }
        }
        sort($files);
        return $files;
    }

    /**
     * Find JPEG and PNG images, excluding the dist/ directory.
     *
     * @return string[]
     */
    private function findImages(string $dir): array
    {
        $exts = ['jpg', 'jpeg', 'png'];
        $files = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (strpos($file->getPathname(), $this->distDir) === 0) {
                continue;
            }
            if ($file->isFile() && in_array(strtolower($file->getExtension()), $exts)) {
                $files[] = $file->getPathname();
            }
        }
        sort($files);
        return $files;
    }

    /**
     * Get a path relative to the project root.
     */
    private function relativePath(string $absolute): string
    {
        return ltrim(str_replace($this->basePath, '', $absolute), '/');
    }

    /**
     * Build the dist output path for a minified file.
     * e.g. assets/style.css → assets/dist/style.min.css
     */
    private function distPath(string $relativeSrc, string $newExt): string
    {
        $info     = pathinfo($relativeSrc);
        $basename = $info['filename'] . '.' . $newExt;
        // Place output flat in dist/ preserving any subdirectory under assets/
        $subDir   = str_replace('assets/', '', $info['dirname']);
        $subDir   = ($subDir === 'assets' || $subDir === '.') ? '' : $subDir;

        $outDir = $this->distDir . ($subDir ? '/' . $subDir : '');
        return $outDir . '/' . $basename;
    }

    /**
     * Build the dist output path for a WebP file.
     * e.g. assets/images/photo.jpg → assets/dist/images/photo.webp
     */
    private function webpPath(string $relativeSrc): string
    {
        $info   = pathinfo($relativeSrc);
        $subDir = str_replace('assets/', '', $info['dirname']);
        $subDir = ($subDir === 'assets' || $subDir === '.') ? '' : $subDir;

        $outDir = $this->distDir . ($subDir ? '/' . $subDir : '');
        return $outDir . '/' . $info['filename'] . '.webp';
    }

    private function ensureDistDir(): void
    {
        if (!is_dir($this->distDir)) {
            mkdir($this->distDir, 0755, true);
        }
    }

    private function writeFile(string $path, string $content): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($path, $content, LOCK_EX);
    }
}
