<?php
session_start();
require_once __DIR__ . '/config.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}
$id = (int)$_GET['id'];

$stmt = $conn->prepare("
    SELECT p.id, p.name, p.price, p.short_description, p.category_id, p.image,
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

$pageTitle = $product ? $product['name'] . ' — Магазин автозапчастей' : 'Товар не найден — Магазин автозапчастей';
$extraCss = ['css/product.css'];
require __DIR__ . '/header.php';

$related = [];
if ($product) {
    $rel = $conn->prepare("
        SELECT p.id, p.name, p.price, p.short_description, p.image
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

<main class="product-page">
  <div class="container">

    <?php if ($product): ?>
      <nav class="breadcrumbs" aria-label="Хлебные крошки">
        <a href="index.php">Главная</a>
        <span aria-hidden="true">›</span>
        <a href="index.php?category=<?php echo (int)$product['category_id']; ?>">
          <?php echo htmlspecialchars($product['category_name']); ?>
        </a>
        <span aria-hidden="true">›</span>
        <span class="current"><?php echo htmlspecialchars($product['name']); ?></span>
      </nav>

      <section class="product-hero">
        <div class="product-media" aria-hidden="true">
          <?php if (!empty($product['image'])): ?>
            <img src="uploads/products/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
          <?php endif; ?>
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
              <div class="thumb" aria-hidden="true">
                <?php if (!empty($it['image'])): ?>
                  <img src="uploads/products/<?php echo htmlspecialchars($it['image']); ?>" alt="<?php echo htmlspecialchars($it['name']); ?>">
                <?php endif; ?>
              </div>
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

      <script type="application/ld+json">
      {
        "@context": "https://schema.org/",
        "@type": "Product",
        "name": <?php echo json_encode($product['name'], JSON_UNESCAPED_UNICODE); ?>,
        "category": <?php echo json_encode($product['category_name'], JSON_UNESCAPED_UNICODE); ?>,
        "offers": {
          "@type": "Offer",
          "priceCurrency": "RUB",
          "price": <?php echo json_encode(number_format((float)$product['price'], 2, '.', '')); ?>
        }
      }
      </script>

    <?php else: ?>
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

<?php require __DIR__ . '/footer.php'; ?>
