<?php
session_start();
require_once __DIR__ . '/config.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}
$id = (int)$_GET['id'];

// Получаем товар
$stmt = $conn->prepare("
    SELECT p.id, p.name, p.price, p.short_description, p.category_id,
           c.name AS category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.id = ?
    LIMIT 1
");
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$product = $res->num_rows ? $res->fetch_assoc() : null;
$stmt->close();

// Заголовки/мета
$title = $product ? $product['name'] . ' — Магазин автозапчастей' : 'Товар не найден — Магазин автозапчастей';
$desc  = $product && !empty($product['short_description'])
        ? mb_substr(trim($product['short_description']), 0, 150)
        : 'Запчасти, аксессуары и расходники — магазин автозапчастей.';

// Для JSON-LD
$priceFormatted = $product ? number_format((float)$product['price'], 2, '.', '') : null;

// Похожие товары (та же категория, исключая текущий)
$related = [];
if ($product) {
    $rel = $conn->prepare("
        SELECT p.id, p.name, p.price, p.short_description
        FROM products p
        WHERE p.category_id = ? AND p.id <> ?
        ORDER BY p.id DESC
        LIMIT 6
    ");
    $rel->bind_param('ii', $product['category_id'], $product['id']);
    $rel->execute();
    $rres = $rel->get_result();
    while ($row = $rres->fetch_assoc()) $related[] = $row;
    $rel->close();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($title); ?></title>
  <meta name="description" content="<?php echo htmlspecialchars($desc); ?>">

  <!-- Open Graph -->
  <meta property="og:type" content="product">
  <meta property="og:title" content="<?php echo htmlspecialchars($product ? $product['name'] : 'Товар не найден'); ?>">
  <meta property="og:description" content="<?php echo htmlspecialchars($desc); ?>">
  <meta property="og:url" content="<?php echo htmlspecialchars((isset($_SERVER['REQUEST_SCHEME'])?$_SERVER['REQUEST_SCHEME']:'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>">

  <link rel="stylesheet" href="css/common.css">
  <link rel="stylesheet" href="css/product.css"> <!-- Твой отдельный CSS для карточки товара -->
</head>
<body>

<header class="site-header">
  <div class="container header-row">
    <a class="brand" href="index.php">
      <span class="brand-icon" aria-hidden="true">⚙️</span>
      <span class="brand-name">Магазин автозапчастей</span>
    </a>
    <nav class="main-nav">
      <a href="index.php" class="nav-link">Главная</a>
      <a href="cart.php" class="nav-link">Корзина</a>
      <a href="news.php" class="nav-link">Новости</a>
      <a href="admin/index.php" class="nav-link nav-link--ghost">Админ-панель</a>
    </nav>
  </div>
</header>

<main class="product-page">
  <div class="container">

    <?php if ($product): ?>
      <!-- Хлебные крошки -->
      <nav class="breadcrumbs" aria-label="Хлебные крошки">
        <a href="index.php">Главная</a>
        <span aria-hidden="true">›</span>
        <a href="index.php?category=<?php echo (int)$product['category_id']; ?>">
          <?php echo htmlspecialchars($product['category_name']); ?>
        </a>
        <span aria-hidden="true">›</span>
        <span class="current"><?php echo htmlspecialchars($product['name']); ?></span>
      </nav>

      <!-- Hero блок -->
      <section class="product-hero">
        <!-- Медиа/изображение. Сейчас заглушка-блок, подставь реальное изображение когда появится поле -->
        <div class="product-media" aria-hidden="true">
          <!-- Пример: <img src="uploads/products/<?php echo (int)$product['id']; ?>.jpg" alt="<?php echo htmlspecialchars($product['name']); ?>"> -->
        </div>

        <div class="product-info">
          <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
          <div class="product-meta">
            Категория: <?php echo htmlspecialchars($product['category_name']); ?>
          </div>

          <?php if (!empty($product['short_description'])): ?>
            <p class="product-excerpt">
              <?php echo nl2br(htmlspecialchars($product['short_description'])); ?>
            </p>
          <?php endif; ?>

          <div class="product-buy">
            <div class="product-price">
              <?php echo number_format((float)$product['price'], 0, ',', ' '); ?> ₽
            </div>
            <a class="btn btn-success btn-lg" href="cart.php?action=add&id=<?php echo (int)$product['id']; ?>">
              В корзину
            </a>
          </div>

          <ul class="product-benefits">
            <li>Оригинальные или проверенные аналоги</li>
            <li>Гарантия возврата 14 дней</li>
            <li>Доставка по РФ</li>
          </ul>
        </div>
      </section>

      <!-- Дополнительные блоки -->
      <section class="product-panels">
        <div class="panel">
          <h3>Совместимость</h3>
          <p>Если сомневаетесь в совместимости — сообщите VIN/Frame номер, и мы проверим перед покупкой.</p>
        </div>
        <div class="panel">
          <h3>Оплата и доставка</h3>
          <p>Оплата картой онлайн или при получении. Доставка курьером/ПВЗ. Сроки и стоимость зависят от региона.</p>
        </div>
        <div class="panel">
          <h3>Возврат</h3>
          <p>Возврат/обмен возможен в течение 14 дней при сохранении товарного вида и упаковки.</p>
        </div>
      </section>

      <?php if (!empty($related)): ?>
      <section class="related-products">
        <div class="section-head">
          <h2 class="section-title">Похожие товары</h2>
          <a class="link" href="index.php?category=<?php echo (int)$product['category_id']; ?>">Все из категории</a>
        </div>

        <div class="products-grid">
          <?php foreach ($related as $it):
              $rPrice = number_format((float)$it['price'], 0, ',', ' ');
              $excerpt = '';
              if (!empty($it['short_description'])) {
                  if (function_exists('mb_strimwidth')) {
                      $excerpt = htmlspecialchars(mb_strimwidth($it['short_description'], 0, 110, '…', 'UTF-8'));
                  } else {
                      $excerpt = htmlspecialchars(substr($it['short_description'], 0, 110)) . '…';
                  }
              }
          ?>
            <article class="product-card">
              <div class="thumb" aria-hidden="true"></div>
              <div class="info">
                <h3 class="title"><a href="product.php?id=<?php echo (int)$it['id']; ?>"><?php echo htmlspecialchars($it['name']); ?></a></h3>
                <?php if ($excerpt): ?><p class="excerpt"><?php echo $excerpt; ?></p><?php endif; ?>
                <div class="bottom">
                  <div class="price"><?php echo $rPrice; ?> ₽</div>
                  <a class="btn btn-ghost" href="product.php?id=<?php echo (int)$it['id']; ?>">Подробнее</a>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
      <?php endif; ?>

      <!-- JSON-LD (структурированные данные товара) -->
      <script type="application/ld+json">
      {
        "@context": "https://schema.org/",
        "@type": "Product",
        "name": <?php echo json_encode($product['name'], JSON_UNESCAPED_UNICODE); ?>,
        "category": <?php echo json_encode($product['category_name'], JSON_UNESCAPED_UNICODE); ?>,
        "offers": {
          "@type": "Offer",
          "priceCurrency": "RUB",
          "price": <?php echo json_encode($priceFormatted); ?>,
          "url": <?php echo json_encode((isset($_SERVER['REQUEST_SCHEME'])?$_SERVER['REQUEST_SCHEME']:'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>
        }
      }
      </script>

    <?php else: ?>
      <!-- Товар не найден -->
      <section class="not-found">
        <div class="empty-state">
          <div class="empty-icon">🔧</div>
          <div class="empty-title">Товар не найден</div>
          <div class="empty-text">
            <a class="btn" href="index.php">Вернуться на главную</a>
          </div>
        </div>
      </section>
    <?php endif; ?>

  </div>
</main>

<footer class="site-footer">
  <div class="container">
    © <?php echo date("Y"); ?> Магазин автозапчастей
  </div>
</footer>

</body>
</html>
<?php $conn->close(); ?>
