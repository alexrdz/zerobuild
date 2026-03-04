<?php
    $pageTitle = $title;
?>

    <a href="/" class="back-link">← Back to home</a>

    <article>
        <h1><?= htmlspecialchars($title) ?></h1>

        <?php if ($date): ?>
            <div class="post-date"><?= htmlspecialchars($date) ?></div>
        <?php endif; ?>

        <div class="content">
            <?= $blogContent ?>
        </div>
    </article>
