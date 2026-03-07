<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? (getenv('SITE_TITLE') ?: 'My Site')) ?></title>
    <link rel="stylesheet" href="<?= asset_url('assets/style.css') ?>">
    <link rel="alternate" type="application/rss+xml" title="<?= htmlspecialchars(getenv('SITE_TITLE') ?: 'My Site') ?> RSS Feed" href="<?= base_url('rss.xml') ?>">
</head>
<body class="<?= htmlspecialchars($bodyClass ?? '') ?>">
    
    <?= $content ?>

</body>
</html>
