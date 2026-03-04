<?php

// Simple Parsedown class (inline to avoid dependencies)
class SimpleMarkdown {
    private $maxLength = 100000; // 100KB limit

    public function text($text) {
        // Input size limit to prevent DoS
        if (strlen($text) > $this->maxLength) {
            throw new Exception('Markdown content exceeds maximum allowed size');
        }

        // Process markdown BEFORE escaping to preserve structure
        $text = $this->processMarkdown($text);

        return $text;
    }

    private function processMarkdown($text) {
        // Headers (process before escaping)
        $text = preg_replace_callback('/^### (.+)$/m', function($m) {
            return '<h3>' . htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8') . '</h3>';
        }, $text);
        $text = preg_replace_callback('/^## (.+)$/m', function($m) {
            return '<h2>' . htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8') . '</h2>';
        }, $text);
        $text = preg_replace_callback('/^# (.+)$/m', function($m) {
            return '<h1>' . htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8') . '</h1>';
        }, $text);

        // Code blocks (preserve literal content)
        $text = preg_replace_callback('/```(.+?)```/s', function($m) {
            return '<pre><code>' . htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8') . '</code></pre>';
        }, $text);
        $text = preg_replace_callback('/`(.+?)`/', function($m) {
            return '<code>' . htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8') . '</code>';
        }, $text);

        // Links with XSS protection
        $text = preg_replace_callback('/\[(.+?)\]\((.+?)\)/', function($m) {
            $linkText = htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8');
            $url = $this->sanitizeUrl($m[2]);
            return '<a href="' . $url . '" rel="noopener noreferrer">' . $linkText . '</a>';
        }, $text);

        // Bold and italic
        $text = preg_replace_callback('/\*\*(.+?)\*\*/U', function($m) {
            return '<strong>' . htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8') . '</strong>';
        }, $text);
        $text = preg_replace_callback('/\*(.+?)\*/U', function($m) {
            return '<em>' . htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8') . '</em>';
        }, $text);

        // Lists
        $text = preg_replace_callback('/^\* (.+)$/m', function($m) {
            return '<li>' . htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8') . '</li>';
        }, $text);
        $text = preg_replace('/(<li>.*<\/li>)/sU', '<ul>$1</ul>', $text);

        // Escape remaining text and wrap in paragraphs
        $text = preg_replace_callback('/([^<>]+)(?=<|$)/', function($m) {
            // Don't escape if it's already part of a tag
            if (preg_match('/<\/?(h[1-6]|ul|li|pre|code|strong|em|a)/', $m[0])) {
                return $m[0];
            }
            return htmlspecialchars($m[0], ENT_QUOTES, 'UTF-8');
        }, $text);

        // Paragraphs
        $text = preg_replace('/\n\n/', '</p><p>', $text);
        $text = '<p>' . $text . '</p>';

        // Clean up empty paragraphs and misplaced tags
        $text = preg_replace('/<p>\s*<\/p>/', '', $text);
        $text = preg_replace('/<p>(<h[1-6]>)/', '$1', $text);
        $text = preg_replace('/(<\/h[1-6]>)<\/p>/', '$1', $text);
        $text = preg_replace('/<p>(<ul>)/', '$1', $text);
        $text = preg_replace('/(<\/ul>)<\/p>/', '$1', $text);
        $text = preg_replace('/<p>(<pre>)/', '$1', $text);
        $text = preg_replace('/(<\/pre>)<\/p>/', '$1', $text);

        return $text;
    }

    private function sanitizeUrl($url) {
        $url = trim($url);

        // Block dangerous protocols
        $dangerousProtocols = ['javascript:', 'data:', 'vbscript:', 'file:', 'about:'];
        foreach ($dangerousProtocols as $protocol) {
            if (stripos($url, $protocol) === 0) {
                return '#';
            }
        }

        // Only allow http, https, mailto, and relative URLs
        if (!preg_match('/^(https?:\/\/|mailto:|\/|#)/', $url)) {
            return '#';
        }

        return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    }
}
