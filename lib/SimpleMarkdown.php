<?php

// Simple Parsedown class (inline to avoid dependencies)
class SimpleMarkdown {
    private $maxLength = 100000; // 100KB limit

    public function text($text) {
        // Input size limit to prevent DoS
        if (strlen($text) > $this->maxLength) {
            throw new Exception('Markdown content exceeds maximum allowed size');
        }

        return $this->processMarkdown($text);
    }

    private function processMarkdown($text) {
        // Escape ALL text first to prevent XSS.
        // Markdown syntax characters (*, #, [, ], (, ), `, .) are unaffected
        // by htmlspecialchars, so patterns still match after escaping.
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        // Headers (content already escaped)
        $text = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $text);
        $text = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $text);
        $text = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $text);

        // Code blocks (content already escaped)
        $text = preg_replace('/```(.+?)```/s', '<pre><code>$1</code></pre>', $text);
        $text = preg_replace('/`(.+?)`/', '<code>$1</code>', $text);

        // Links with XSS protection
        $text = preg_replace_callback('/\[(.+?)\]\((.+?)\)/', function($m) {
            // Decode pre-escaped URL for protocol validation, sanitizeUrl re-escapes
            $rawUrl = html_entity_decode($m[2], ENT_QUOTES, 'UTF-8');
            $url = $this->sanitizeUrl($rawUrl);
            return '<a href="' . $url . '" rel="noopener noreferrer">' . $m[1] . '</a>';
        }, $text);

        // Bold and italic (content already escaped)
        $text = preg_replace('/\*\*(.+?)\*\*/U', '<strong>$1</strong>', $text);
        $text = preg_replace('/\*(.+?)\*/U', '<em>$1</em>', $text);

        // Horizontal rules
        $text = preg_replace('/^---+$/m', '<hr>', $text);

        // Unordered lists (temporary markers to distinguish from ordered)
        $text = preg_replace('/^\* (.+)$/m', '<uli>$1</uli>', $text);
        $text = preg_replace('/(<uli>.*<\/uli>)/sU', '<ul>$1</ul>', $text);
        $text = str_replace(['<uli>', '</uli>'], ['<li>', '</li>'], $text);

        // Ordered lists
        $text = preg_replace('/^\d+\. (.+)$/m', '<oli>$1</oli>', $text);
        $text = preg_replace('/(<oli>.*<\/oli>)/sU', '<ol>$1</ol>', $text);
        $text = str_replace(['<oli>', '</oli>'], ['<li>', '</li>'], $text);

        // Paragraphs
        $text = preg_replace('/\n\n/', '</p><p>', $text);
        $text = '<p>' . $text . '</p>';

        // Clean up empty paragraphs and misplaced tags
        $text = preg_replace('/<p>\s*<\/p>/', '', $text);
        $text = preg_replace('/<p>(<h[1-6]>)/', '$1', $text);
        $text = preg_replace('/(<\/h[1-6]>)<\/p>/', '$1', $text);
        $text = preg_replace('/<p>(<ul>)/', '$1', $text);
        $text = preg_replace('/(<\/ul>)<\/p>/', '$1', $text);
        $text = preg_replace('/<p>(<ol>)/', '$1', $text);
        $text = preg_replace('/(<\/ol>)<\/p>/', '$1', $text);
        $text = preg_replace('/<p>(<pre>)/', '$1', $text);
        $text = preg_replace('/(<\/pre>)<\/p>/', '$1', $text);
        $text = preg_replace('/<p>(<hr>)/', '$1', $text);
        $text = preg_replace('/(<hr>)<\/p>/', '$1', $text);

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
