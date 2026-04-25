<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = $pageTitle ?? 'Магазин автозапчастей';

// текущий файл (about.php, cart.php и т.п.)
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '';
$currentFile = trim(basename($currentPath) ?: 'index.php');

function navActive(string $file, string $currentFile): string {
    if ($file === 'index.php' && ($currentFile === '' || $currentFile === 'index.php')) return 'active';
    return $currentFile === $file ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($pageTitle) ?></title>

  <link rel="stylesheet" href="css/common.css" />
  <?php
  // при желании страница может передать дополнительные css:
  // $extraCss = ['css/about.css', 'css/order.css'];
  if (!empty($extraCss) && is_array($extraCss)) {
      foreach ($extraCss as $href) {
          echo '<link rel="stylesheet" href="'.htmlspecialchars($href).'">'.PHP_EOL;
      }
  }
  ?>
  <style>
    /* если в common.css нет подсветки активного пункта — добавим мягкую */
    .main-nav .nav-link.active{
      background: rgba(255,255,255,.22);
      color:#fff !important;
      border-radius: 12px;
      padding: 6px 10px;
    }
  </style>
</head>
<body>

<header class="site-header">
  <div class="container header-row">
    <a class="brand" href="index.php">
      <span class="brand-icon" aria-hidden="true">⚙️</span>
      <span class="brand-name">Магазин автозапчастей</span>
    </a>

    <nav class="main-nav">
      <a href="index.php"       class="nav-link <?= navActive('index.php',  $currentFile) ?>">Главная</a>
      <a href="cart.php"        class="nav-link <?= navActive('cart.php',   $currentFile) ?>">Корзина</a>
      <a href="news.php"        class="nav-link <?= navActive('news.php',   $currentFile) ?>">Новости</a>
      <a href="ai_diagnose.php" class="nav-link <?= navActive('ai_diagnose.php', $currentFile) ?>">ИИ-диагностика</a>
      <a href="about.php"       class="nav-link <?= navActive('about.php',  $currentFile) ?>">О нас</a>
    </nav>
  </div>
</header>
