<?php

declare(strict_types=1);

/** @var string $title */
/** @var string $content */
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
</head>
<body>
    <main><?= $content ?></main>
</body>
</html>

