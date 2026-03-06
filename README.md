# Simple PHP Static Site Generator

A no-build, dependency-free PHP static site generator with markdown support and JSON API capabilities. Perfect for cheap shared hosting!

## Features

-   ✅ **Zero build step** - just edit and refresh
-   ✅ **Markdown blog posts** with frontmatter support
-   ✅ **JSON REST API** endpoints
-   ✅ **Clean URLs** via `.htaccess` or built-in Router
-   ✅ **Layout & Template system** for easy customization
-   ✅ **Production-Ready Security** (XSS protection, CSP, Rate limiting, CORS control)
-   ✅ **RSS 2.0 feed** with auto-discovery
-   ✅ **No dependencies** - minimal implementation

## Security Features

This application includes comprehensive security hardening:

- **XSS Protection** - Sanitized markdown parsing with dangerous protocol blocking
- **Path Traversal Prevention** - Strict file access validation
- **Content Security Policy** - Prevents injection attacks
- **Rate Limiting** - Built-in request throttling (60 req/min default)
- **CORS Control** - Configurable origin whitelist (no wildcard)
- **Security Headers** - HSTS, X-Frame-Options, X-Content-Type-Options, etc.
- **Input Validation** - File size limits and content validation
- **File Access Protection** - .htaccess rules block direct access to data files

See [SECURITY.md](SECURITY.md) for complete security documentation.

## Directory Structure

```
/
├── index.php             # Main entry point (Config & Headers)
├── .htaccess             # Apache routing rules
├── lib/                  # Core library code
│   ├── Router.php        # Routing & Templating logic
│   ├── RssFeed.php       # RSS 2.0 feed generator
│   └── SimpleMarkdown.php # Markdown parser
├── assets/               # Static assets
│   └── style.css         # Global styles
├── templates/            # HTML templates
│   ├── _layout.php       # Master layout wrapper
│   ├── home.php          # Home page content
│   ├── blog.php          # Blog post content
│   ├── 404.php           # Error content
│   └── *.php             # Generic pages (e.g. about.php)
├── blog/                 # Markdown blog posts
│   └── *.md
└── api-data/             # JSON API files
    └── *.json
```

## Installation

1.  **Clone/Upload** files to your web server root **or any subdirectory**.
2.  **Ensure mod_rewrite is enabled** (if using Apache).
3.  **Permissions**: Ensure `blog`, `api-data`, and `templates` are readable.
4.  **Configuration**: Copy `.env.example` to `.env` and configure for your environment.
5.  **Security**: Review [SECURITY.md](SECURITY.md) before deploying to production.

### Subdirectory Installation

The app auto-detects its base path, so it works whether deployed at the domain root (`https://example.com/`) or in a subdirectory (`https://example.com/site/`). No extra configuration is needed — just upload the files and go.

All internal links and asset URLs are built using the `base_url()` helper, which prepends the detected `BASE_PATH` automatically. If you add new templates, use `base_url('path/to/resource')` for all internal links and asset references.

## Usage

### Development (Local Server)
To run locally using PHP's built-in server, **you must route through index.php**:

```bash
php -S localhost:8000 index.php
```

### Creating Pages

**Blog Posts:**
Create `.md` files in `/blog/`.
-   Example: `/blog/my-post.md` -> Available at `/blog/my-post`

**Standard Pages:**
Create `.php` files in `/templates/`.
-   Example: `/templates/about.php` -> Available at `/about`
-   Example: `/templates/contact.php` -> Available at `/contact`

**API Endpoints:**
Create `.json` files in `/api-data/`.
-   Example: `/api-data/users.json` -> Available at `/api/users`

### Customizing Design

-   **Global Layout:** Edit `templates/_layout.php` to change the HTML skeleton (header/footer).
-   **Styles:** Edit `assets/style.css` for global CSS.
-   **Page Content:** Edit specific files in `templates/`.

## Security

This application implements production-grade security:

-   **XSS Prevention:** Markdown parser sanitizes all content and blocks dangerous protocols
-   **Path Traversal Protection:** Strict validation prevents directory traversal attacks
-   **Rate Limiting:** Built-in throttling prevents abuse (configurable)
-   **CORS Control:** Whitelist-based origin control (no wildcard access)
-   **Security Headers:** CSP, HSTS, X-Frame-Options, X-Content-Type-Options, etc.
-   **Input Validation:** File size limits (500KB markdown, 1MB JSON)
-   **File Protection:** .htaccess blocks direct access to .md, .json, and hidden files
-   **Error Handling:** Production mode hides sensitive error details

For complete security documentation, see [SECURITY.md](SECURITY.md).

## Configuration

### Environment Variables

Copy `.env.example` to `.env` and configure:

```bash
# Environment: development or production
ENVIRONMENT=production

# Rate Limiting
RATE_LIMIT_ENABLED=true
RATE_LIMIT_REQUESTS=60
RATE_LIMIT_WINDOW=60

# CORS Configuration (comma-separated origins)
ALLOWED_ORIGINS=https://yourdomain.com

# Proxy Configuration (only if behind trusted proxy)
TRUST_PROXY=false
```

### RSS Feed

An RSS 2.0 feed is automatically available at `/rss.xml`. It lists recent blog posts and is generated on the fly from the markdown files in `/blog/`. Feed readers will auto-discover it via the `<link>` tag in `<head>`.

Configure the feed via `.env`:

```bash
SITE_TITLE=My Site
SITE_URL=https://example.com
SITE_DESCRIPTION=A simple PHP-powered blog
SITE_LANGUAGE=en-us
RSS_MAX_ITEMS=20
```

### Legacy Configuration

You can also edit constants in `index.php` directly:

```php
define('CACHE_ENABLED', true);
define('CACHE_TIME', 3600); // 1 hour
define('RATE_LIMIT_ENABLED', true);
define('ALLOWED_ORIGINS', []); // Empty = no CORS
```

## Production Deployment

Before going live:

1. Set `ENVIRONMENT=production` in `.env`
2. Enable HTTPS (required for HSTS)
3. Configure rate limits for your traffic
4. Set up error logging and monitoring
5. Review CSP headers and adjust for your needs
6. Test security headers at securityheaders.com
7. Run a security scan (OWASP ZAP recommended)

See [SECURITY.md](SECURITY.md) for complete production checklist.

## License

Free to use however you want. No attribution required.
