<?php
    $siteTitle = getenv('SITE_TITLE') ?: 'My Site';
    $pageTitle = $siteTitle;
?>

    <header style="display: flex; gap: 1rem; align-items: center">
      <figure>
          <img style="max-inline-size: 5rem; height: auto" src="<?= base_url('assets/zerobuild-logo.jpeg') ?>" alt="Hero Image">
      </figure>
      <h1>Welcome to <?= htmlspecialchars($siteTitle) ?></h1>

    </header>
    <?php if (!empty($posts)): ?>
        <h2>Recent Posts</h2>
        <ul class="posts">
            <?php foreach ($posts as $post): ?>
                <li class="post-item">
                    <a href="<?= base_url('blog/' . htmlspecialchars($post['slug'])) ?>">
                        <?= htmlspecialchars($post['title']) ?>
                    </a>
                    <?php if ($post['date']): ?>
                        <div class="post-date"><?= htmlspecialchars($post['date']) ?></div>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No posts yet. Create a markdown file in the <code>/blog</code> directory to get started!</p>
    <?php endif; ?>
