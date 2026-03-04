# Quick Start Guide

Get up and running in 5 minutes!

## 1. Upload to Your Server

Upload these files to your web root:
- `index.php`
- `.htaccess`
- `/templates/` directory
- `/blog/` directory  
- `/api-data/` directory

## 2. Test It Works

Visit your site:
- **Home:** `https://yoursite.com/` - Should show blog posts
- **Blog:** `https://yoursite.com/blog/getting-started` - Should show the example post
- **API:** `https://yoursite.com/api/daily-js-data` - Should return JSON

## 3. Create Your First Blog Post

Create `/blog/my-first-post.md`:

```markdown
---
title: My First Post
date: 2025-01-25
---

# Hello!

This is my first blog post.
```

Visit: `https://yoursite.com/blog/my-first-post`

## 4. Create Your First API Endpoint

Create `/api-data/my-data.json`:

```json
{
  "message": "Hello from my API!",
  "items": [1, 2, 3]
}
```

Visit: `https://yoursite.com/api/my-data`

## 5. Customize Templates

Edit `/templates/home.php` to change your home page design.
Edit `/templates/blog.php` to change blog post layout.

## That's It!

You now have:
- ✅ A working blog
- ✅ JSON API endpoints
- ✅ Clean URLs
- ✅ No build process

## Next Steps

- **Add more posts:** Create more `.md` files in `/blog/`
- **Add more APIs:** Create more `.json` files in `/api-data/`
- **Customize design:** Edit the templates in `/templates/`
- **Configure:** Edit constants at the top of `index.php`

## Common Tasks

### Add a new blog post
1. Create `/blog/post-slug.md`
2. Add frontmatter (title, date)
3. Write in markdown
4. Access at `/blog/post-slug`

### Add a new API endpoint
1. Create `/api-data/endpoint-name.json`
2. Add valid JSON
3. Access at `/api/endpoint-name`

### Change cache time
Edit `index.php`:
```php
define('CACHE_TIME', 7200); // 2 hours
```

### Disable CORS
Edit `index.php`, find and comment out:
```php
// header('Access-Control-Allow-Origin: *');
```

## Need Help?

Check the full README.md for detailed documentation!
