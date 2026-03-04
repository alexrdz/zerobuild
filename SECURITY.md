# Security Documentation

## Security Fixes Applied

This application has been hardened with the following security improvements:

### Critical Fixes

1. **XSS Protection in Markdown Parser**
   - Links are now sanitized to prevent `javascript:` and other dangerous protocols
   - All markdown content is properly escaped before HTML generation
   - Added `rel="noopener noreferrer"` to all external links

2. **Path Traversal Prevention**
   - All file operations now use `realpath()` validation
   - Strict directory boundary checks prevent accessing files outside allowed directories
   - Empty slugs after sanitization are rejected

3. **CORS Security**
   - Removed wildcard CORS (`Access-Control-Allow-Origin: *`)
   - CORS now requires explicit origin whitelist configuration
   - Default behavior: same-origin only

4. **Content Security Policy (CSP)**
   - Comprehensive CSP headers prevent inline script execution
   - Restricts resource loading to trusted sources
   - Prevents clickjacking and other injection attacks

5. **Input Validation & Size Limits**
   - Markdown files limited to 500KB
   - JSON API files limited to 1MB
   - Prevents memory exhaustion attacks

### Additional Security Features

6. **Rate Limiting**
   - File-based rate limiting (60 requests per minute by default)
   - Separate limits for API and blog endpoints
   - Returns 429 status with Retry-After header

7. **Security Headers**
   - `X-Frame-Options: DENY` - Prevents clickjacking
   - `X-Content-Type-Options: nosniff` - Prevents MIME sniffing
   - `X-XSS-Protection: 1; mode=block` - Browser XSS protection
   - `Strict-Transport-Security` - Forces HTTPS (when enabled)
   - `Permissions-Policy` - Restricts browser features

8. **Error Handling**
   - Production mode hides error details
   - Errors logged to server logs only
   - Generic error messages prevent information disclosure

9. **File Access Protection (.htaccess)**
   - Blocks direct access to `.md` files
   - Blocks direct access to `.json` data files
   - Blocks access to `.git` directory
   - Blocks access to hidden files (`.env`, etc.)
   - Disables directory browsing

10. **Improved Cache Headers**
    - ETag support for efficient caching
    - Last-Modified headers
    - Proper 304 Not Modified responses

11. **Removed Unsafe Functions**
    - Replaced `extract()` with explicit variable assignment
    - Prevents variable injection vulnerabilities

12. **Frontmatter Validation**
    - Only allows safe frontmatter keys (title, date, author, description)
    - Prevents arbitrary variable injection

## Configuration

### Environment Variables

Create a `.env` file (copy from `.env.example`) and configure:

```bash
# Set to 'production' to hide error details
ENVIRONMENT=production

# Enable/disable rate limiting
RATE_LIMIT_ENABLED=true
RATE_LIMIT_REQUESTS=60
RATE_LIMIT_WINDOW=60

# CORS: Add allowed origins (comma-separated)
ALLOWED_ORIGINS=https://yourdomain.com,https://app.yourdomain.com

# Only enable if behind trusted proxy
TRUST_PROXY=false
```

### HTTPS Configuration

For production, always use HTTPS:

1. Obtain SSL certificate (Let's Encrypt recommended)
2. Configure your web server to redirect HTTP to HTTPS
3. The application will automatically send HSTS headers when HTTPS is detected

### Rate Limiting

Rate limiting uses file-based storage in `/tmp/php_router_cache/`. For production:

- Consider using Redis or Memcached for better performance
- Adjust limits based on your traffic patterns
- Monitor rate limit hits in your logs

### CORS Configuration

To allow specific origins to access your API:

```php
// In index.php or .env
define('ALLOWED_ORIGINS', ['https://example.com', 'https://app.example.com']);
```

## Production Checklist

Before deploying to production:

- [ ] Set `ENVIRONMENT=production` in configuration
- [ ] Enable HTTPS and verify HSTS headers
- [ ] Configure appropriate rate limits
- [ ] Set up error logging and monitoring
- [ ] Review and adjust CSP headers for your needs
- [ ] Configure CORS if needed for API access
- [ ] Ensure `.htaccess` is active (Apache) or configure equivalent in nginx
- [ ] Block direct access to data directories at web server level
- [ ] Set up regular security updates
- [ ] Configure automated backups
- [ ] Test all security headers using securityheaders.com
- [ ] Run security scanner (e.g., OWASP ZAP)

## Monitoring & Logging

Monitor these security events:

- Rate limit violations (429 responses)
- 404 errors (potential scanning)
- Failed file access attempts
- Markdown parsing errors
- Large file upload attempts

## Remaining Considerations

While this application is now significantly more secure, consider:

1. **Web Application Firewall (WAF)** - Use Cloudflare or similar
2. **DDoS Protection** - Use CDN with DDoS mitigation
3. **Regular Updates** - Keep PHP and server software updated
4. **Security Audits** - Regular penetration testing
5. **Backup Strategy** - Automated backups with off-site storage

## Reporting Security Issues

If you discover a security vulnerability, please email security@yourdomain.com with details.
