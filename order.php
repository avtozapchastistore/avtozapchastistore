<?php
session_start();
require_once __DIR__ . '/config.php';

// Если корзина пуста — на главную
if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    header('Location: cart.php');
    exit;
}

$cart = $_SESSION['cart'];
$errors = [];
$ok_msg = '';
$items = []; // сюда соберём позиции с ценой/суммой для показа и расчёта
$total = 0.0;

// Подтягиваем текущие данные по товарам для отображения страницы
function fetchProducts(mysqli $conn, array $ids): array {
    if (!$ids) return [];
    $ids = array_map('intval', $ids);
    $in  = implode(',', $ids);
    $sql = "SELECT p.id, p.name, p.price, p.stock, c.name AS category_name
            FROM products p
            JOIN categories c ON p.category_id = c.id
            WHERE p.id IN ($in)";
    $res = $conn->query($sql);
    $out = [];
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $out[(int)$r['id']] = $r;
        }
    }
    return $out;
}

$product_ids = array_keys($cart);
$map = fetchProducts($conn, $product_ids);

// Собираем список для рендера
foreach ($cart as $pid => $qty) {
    $pid = (int)$pid;
    $qty = max(0, (int)$qty);
    if ($qty <= 0 || !isset($map[$pid])) continue;
    $name  = $map[$pid]['name'];
    $price = (float)$map[$pid]['price'];
    $stock = (int)$map[$pid]['stock'];
    $sum   = $price * $qty;
    $items[] = [
        'id' => $pid,
        'name' => $name,
        'price' => $price,
        'stock' => $stock,
        'qty' => $qty,
        'sum' => $sum,
    ];
    $total += $sum;
}

// Обработка POST (оформление)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['customer_name'] ?? '');
    $address = trim($_POST['customer_address'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');

    if ($name === '')    $errors[] = 'Введите имя.';
    if ($address === '') $errors[] = 'Введите адрес.';
    if ($phone === '')   $errors[] = 'Введите телефон.';

    if (!$items) $errors[] = 'Корзина пуста или товары недоступны.';

    if (!$errors) {
        // Транзакция: блокируем и списываем остатки, создаём заказ
        try {
            $conn->begin_transaction();

            $insufficient = []; // сюда сложим нехватки

            // 1) Проверка + блокировка строк продуктов
            $locked = [];
            $stmtLock = $conn->prepare("SELECT id, stock, price, name FROM products WHERE id = ? FOR UPDATE");
            foreach ($items as $it) {
                $pid = (int)$it['id'];
                $qty = (int)$it['qty'];

                $stmtLock->bind_param('i', $pid);
                if (!$stmtLock->execute()) throw new Exception('Не удалось заблокировать товар #' . $pid);
                $res = $stmtLock->get_result();
                if (!$res || !$res->num_rows) {
                    $insufficient[] = ['id' => $pid, 'name' => $it['name'], 'need' => $qty, 'left' => 0];
                    continue;
                }
                $row = $res->fetch_assoc();
                $left = (int)$row['stock'];
                if ($left < $qty) {
                    $insufficient[] = ['id' => $pid, 'name' => $row['name'], 'need' => $qty, 'left' => $left];
                } else {
                    $locked[$pid] = ['left' => $left, 'price' => (float)$row['price'], 'name' => $row['name']];
                }
            }
            $stmtLock->close();

            if (!empty($insufficient)) {
                // Есть нехватки — откатываем
                $conn->rollback();
                $msg = "Недостаточно товара на складе:<br>";
                foreach ($insufficient as $miss) {
                    $msg .= htmlspecialchars($miss['name']) . " — нужно {$miss['need']}, доступно {$miss['left']} шт.<br>";
                }
                $errors[] = $msg;
            } else {
                // 2) Списываем остатки
                $stmtUpd = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                foreach ($items as $it) {
                    $pid = (int)$it['id'];
                    $qty = (int)$it['qty'];
                    $stmtUpd->bind_param('ii', $qty, $pid);
                    if (!$stmtUpd->execute() || $stmtUpd->affected_rows < 1) {
                        throw new Exception('Не удалось списать остаток для товара #' . $pid);
                    }
                }
                $stmtUpd->close();

                // 3) Пересчитываем сумму по ценам из БД (чтобы точно)
                $order_total = 0.0;
                $order_items = [];
                foreach ($items as $it) {
                    $pid = (int)$it['id'];
                    $qty = (int)$it['qty'];
                    $price = isset($locked[$pid]) ? (float)$locked[$pid]['price'] : (float)$it['price'];
                    $order_total += $price * $qty;
                    $order_items[] = ['id' => $pid, 'quantity' => $qty];
                }

                // 4) Вставляем заказ
                $productsJson = json_encode($order_items, JSON_UNESCAPED_UNICODE);
                $status = 'pending';
                $stmtOrd = $conn->prepare("INSERT INTO orders (customer_name, customer_address, phone, total, products, status, created_at)
                                           VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $stmtOrd->bind_param('sssiss', $name, $address, $phone, $order_total, $productsJson, $status);
                if (!$stmtOrd->execute()) {
                    throw new Exception('Не удалось создать заказ.');
                }
                $orderId = $stmtOrd->insert_id;
                $stmtOrd->close();

                // 5) Коммитим
                $conn->commit();

                // Очищаем корзину
                $_SESSION['cart'] = [];

                // Покажем страницу успеха
                $ok_msg = "Заказ №{$orderId} успешно оформлен! Мы свяжемся с вами для подтверждения.";
            }
        } catch (Throwable $e) {
            $conn->rollback();
            $errors[] = 'Ошибка оформления заказа. Попробуйте ещё раз.';
        }
    }
}

// Пересчитаем тотал для отображения (если нужно заново)
$total = 0.0;
foreach ($items as $it) $total += $it['sum'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Оформление заказа</title>
  <link rel="stylesheet" href="css/common.css">
  <link rel="stylesheet" href="css/cart.css">
  <link rel="stylesheet" href="css/order.css">
</head>
<body>

<header class="site-header">
  <div class="container header-row">
    <a class="brand" href="index.php">
      <span class="brand-icon" aria-hidden="true">🧾</span>
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

<main class="container">
  <h1 class="page-title">Оформление заказа</h1>

  <?php if ($ok_msg): ?>
    <div class="notice notice-ok"><?php echo htmlspecialchars($ok_msg); ?></div>
    <p><a class="btn" href="index.php">Вернуться в каталог</a></p>
  <?php else: ?>

    <?php if ($errors): ?>
      <div class="notice notice-err">
        <?php foreach ($errors as $e) echo "<div>{$e}</div>"; ?>
      </div>
    <?php endif; ?>

    <div class="order-grid">
      <form class="card" method="post" action="order.php">
        <h2>Данные покупателя</h2>
        <label>Имя</label>
        <input type="text" name="customer_name" class="input" required value="<?php echo htmlspecialchars($_POST['customer_name'] ?? ''); ?>">

        <label>Адрес</label>
        <input type="text" name="customer_address" class="input" required value="<?php echo htmlspecialchars($_POST['customer_address'] ?? ''); ?>">

        <label>Телефон</label>
        <input type="text" name="phone" class="input" required value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">

        <div class="form-buttons" style="margin-top:12px;">
          <button type="submit" class="btn btn-success">Подтвердить заказ</button>
          <a href="cart.php" class="btn btn-ghost">Вернуться в корзину</a>
        </div>
      </form>

      <div class="card">
        <h2>Ваш заказ</h2>
        <?php if ($items): ?>
          <div class="cart-items">
            <?php foreach ($items as $it): ?>
              <div class="cart-item-card">
                <div class="cart-item-info">
                  <div class="cart-item-name"><?php echo htmlspecialchars($it['name']); ?></div>
                  <?php if ($it['qty'] > $it['stock']): ?>
                    <div class="stock-warning">Запрошено: <?php echo (int)$it['qty']; ?>, в наличии: <?php echo (int)$it['stock']; ?></div>
                  <?php else: ?>
                    <div class="cart-item-category">В наличии: <?php echo (int)$it['stock']; ?> шт.</div>
                  <?php endif; ?>
                </div>
                <div class="cart-item-actions">
                  <span class="qty"><?php echo (int)$it['qty']; ?> шт.</span>
                </div>
                <div class="cart-item-price">
                  <?php echo number_format($it['sum'], 0, ',', ' '); ?> ₽
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <div class="summary-row">
            <div>Итого:</div>
            <div class="summary-total"><?php echo number_format($total, 0, ',', ' '); ?> ₽</div>
          </div>
        <?php else: ?>
          <div class="empty-state">
            <div class="empty-icon">🛒</div>
            <div class="empty-title">Корзина пуста</div>
            <div class="empty-text">Добавьте товары, чтобы оформить заказ.</div>
          </div>
        <?php endif; ?>
      </div>
    </div>

  <?php endif; ?>
</main>

<footer class="site-footer">
  <div class="container">
    © <?php echo date("Y"); ?> Магазин автозапчастей
  </div>
</footer>

<?php $conn->close(); ?>
</body>
</html>
