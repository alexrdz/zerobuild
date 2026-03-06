<?php

/**
 * RSS 2.0 Feed Generator
 *
 * Scans the blog directory for markdown posts, parses their frontmatter,
 * and produces a valid RSS 2.0 XML document. No external dependencies.
 */
class RssFeed
{
    /** @var int Maximum items to include in the feed */
    private $maxItems;

    /** @var string Absolute path to the blog directory */
    private $blogDir;

    /** @var string Site base URL (no trailing slash) */
    private $siteUrl;

    /** @var string Site / channel title */
    private $siteTitle;

    /** @var string Short description of the blog */
    private $siteDescription;

    /** @var string Language code (e.g. en-us) */
    private $language;

    public function __construct()
    {
        $this->blogDir         = defined('BLOG_DIR') ? BLOG_DIR : __DIR__ . '/../blog';
        $this->maxItems        = defined('RSS_MAX_ITEMS') ? RSS_MAX_ITEMS : 20;
        $this->siteUrl         = rtrim(getenv('SITE_URL') ?: 'http://localhost', '/');
        $this->siteTitle       = getenv('SITE_TITLE') ?: 'My Site';
        $this->siteDescription = getenv('SITE_DESCRIPTION') ?: '';
        $this->language        = getenv('SITE_LANGUAGE') ?: 'en-us';
    }

    /**
     * Generate the full RSS 2.0 XML string.
     */
    public function generate(): string
    {
        $posts = $this->collectPosts();

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $rss = $dom->createElement('rss');
        $rss->setAttribute('version', '2.0');
        $rss->setAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');
        $dom->appendChild($rss);

        $channel = $dom->createElement('channel');
        $rss->appendChild($channel);

        // Channel metadata
        $channel->appendChild($dom->createElement('title', $this->xmlSafe($this->siteTitle)));
        $channel->appendChild($dom->createElement('link', $this->xmlSafe($this->siteUrl)));
        $channel->appendChild($dom->createElement('description', $this->xmlSafe($this->siteDescription)));
        $channel->appendChild($dom->createElement('language', $this->xmlSafe($this->language)));

        // Atom self-link (recommended for valid feeds)
        $atomLink = $dom->createElement('atom:link');
        $atomLink->setAttribute('href', $this->siteUrl . '/rss.xml');
        $atomLink->setAttribute('rel', 'self');
        $atomLink->setAttribute('type', 'application/rss+xml');
        $channel->appendChild($atomLink);

        // Last build date
        $channel->appendChild($dom->createElement('lastBuildDate', date(DATE_RFC2822)));

        // Items
        foreach ($posts as $post) {
            $item = $dom->createElement('item');

            $item->appendChild($dom->createElement('title', $this->xmlSafe($post['title'])));

            $postLink = $this->siteUrl . '/blog/' . rawurlencode($post['slug']);
            $item->appendChild($dom->createElement('link', $this->xmlSafe($postLink)));

            // Use the link as a permalink GUID
            $guid = $dom->createElement('guid', $this->xmlSafe($postLink));
            $guid->setAttribute('isPermaLink', 'true');
            $item->appendChild($guid);

            if (!empty($post['date'])) {
                $timestamp = strtotime($post['date']);
                if ($timestamp !== false) {
                    $item->appendChild($dom->createElement('pubDate', date(DATE_RFC2822, $timestamp)));
                }
            }

            if (!empty($post['description'])) {
                $desc = $dom->createElement('description');
                $desc->appendChild($dom->createCDATASection($post['description']));
                $item->appendChild($desc);
            }

            $channel->appendChild($item);
        }

        return $dom->saveXML();
    }

    // ------------------------------------------------------------------
    // Internal helpers
    // ------------------------------------------------------------------

    /**
     * Collect blog posts, sorted newest-first, limited to $maxItems.
     *
     * @return array<int, array{slug: string, title: string, date: string, description: string}>
     */
    private function collectPosts(): array
    {
        if (!is_dir($this->blogDir)) {
            return [];
        }

        $files = glob($this->blogDir . '/*.md');
        if ($files === false) {
            return [];
        }

        $posts = [];

        foreach ($files as $file) {
            $slug    = basename($file, '.md');
            $raw     = file_get_contents($file);
            if ($raw === false) {
                continue;
            }

            $post = $this->parseFrontmatter($raw, $slug);
            $posts[] = $post;
        }

        // Sort by date descending
        usort($posts, function ($a, $b) {
            return strcmp($b['date'], $a['date']);
        });

        return array_slice($posts, 0, $this->maxItems);
    }

    /**
     * Parse YAML-like frontmatter and extract a short description.
     *
     * @return array{slug: string, title: string, date: string, description: string}
     */
    private function parseFrontmatter(string $raw, string $slug): array
    {
        $title       = ucwords(str_replace('-', ' ', $slug));
        $date        = '';
        $description = '';
        $content     = $raw;

        if (preg_match('/^---\s*\n(.*?)\n---\s*\n(.*)$/s', $raw, $matches)) {
            $frontmatterText = $matches[1];
            $content         = $matches[2];

            foreach (explode("\n", $frontmatterText) as $line) {
                if (preg_match('/^(\w+):\s*(.+)$/', $line, $m)) {
                    $key   = trim($m[1]);
                    $value = trim($m[2]);
                    switch ($key) {
                        case 'title':
                            $title = $value;
                            break;
                        case 'date':
                            $date = $value;
                            break;
                        case 'description':
                            $description = $value;
                            break;
                    }
                }
            }
        }

        // If no explicit description, derive one from the content body.
        if (empty($description)) {
            $description = $this->makeExcerpt($content);
        }

        return [
            'slug'        => $slug,
            'title'       => $title,
            'date'        => $date,
            'description' => $description,
        ];
    }

    /**
     * Build a plain-text excerpt (~200 chars) from markdown content.
     */
    private function makeExcerpt(string $markdown, int $maxLength = 200): string
    {
        // Strip common markdown syntax for a rough plain-text version.
        $text = $markdown;

        // Remove headings markers
        $text = preg_replace('/^#{1,6}\s+/m', '', $text);
        // Remove images
        $text = preg_replace('/!\[.*?\]\(.*?\)/', '', $text);
        // Collapse links to their text
        $text = preg_replace('/\[([^\]]+)\]\([^\)]+\)/', '$1', $text);
        // Remove bold/italic markers
        $text = preg_replace('/[*_]{1,3}/', '', $text);
        // Remove code fences and inline code
        $text = preg_replace('/```[\s\S]*?```/', '', $text);
        $text = preg_replace('/`[^`]+`/', '', $text);
        // Remove horizontal rules
        $text = preg_replace('/^[-*_]{3,}\s*$/m', '', $text);
        // Remove list markers
        $text = preg_replace('/^[\s]*[-*+]\s+/m', '', $text);
        $text = preg_replace('/^[\s]*\d+\.\s+/m', '', $text);
        // Collapse whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        if (mb_strlen($text) > $maxLength) {
            $text = mb_substr($text, 0, $maxLength);
            // Try to break at a word boundary
            $lastSpace = mb_strrpos($text, ' ');
            if ($lastSpace !== false && $lastSpace > $maxLength * 0.6) {
                $text = mb_substr($text, 0, $lastSpace);
            }
            $text .= '…';
        }

        return $text;
    }

    /**
     * Escape a string for safe inclusion as XML text content.
     */
    private function xmlSafe(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
