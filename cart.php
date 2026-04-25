<?php
session_start();
require_once 'config.php';

$pageTitle = 'Название страницы';           // опционально
$extraCss   = ['css/about.css'];            // опционально: стили страницы
require __DIR__ . '/header.php';



/**
 * Получить остаток товара по id.
 */
function get_stock(mysqli $conn, int $id): int {
    $id = (int)$id;
    $res = $conn->query("SELECT stock FROM products WHERE id = {$id} LIMIT 1");
    if ($res && $res->num_rows) {
        $row = $res->fetch_assoc();
        return (int)$row['stock'];
    }
    return 0;
}

/**
 * Получить цену товара по id (для пересчёта тотала)
 */
function get_price(mysqli $conn, int $id): float {
    $id = (int)$id;
    $res = $conn->query("SELECT price FROM products WHERE id = {$id} LIMIT 1");
    if ($res && $res->num_rows) {
        $row = $res->fetch_assoc();
        return (float)$row['price'];
    }
    return 0.0;
}

// Инициализация корзины
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Сообщения (например, когда попытались добавить больше остатка)
if (!isset($_SESSION['cart_notice'])) {
    $_SESSION['cart_notice'] = [];
}

/* ---------------- Действия с корзиной ---------------- */

// Добавление в корзину
if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'add') {
    $id = (int)$_GET['id'];
    $stock = get_stock($conn, $id);

    // если товара нет на складе — сообщение и не добавляем
    if ($stock <= 0) {
        $_SESSION['cart_notice'][] = "Товар #{$id}: нет в наличии.";
    } else {
        // если уже есть в корзине — увеличиваем до лимита склада
        if (isset($_SESSION['cart'][$id])) {
            if ($_SESSION['cart'][$id] < $stock) {
                $_SESSION['cart'][$id]++;
            } else {
                $_SESSION['cart'][$id] = $stock; // не больше остатка
                $_SESSION['cart_notice'][] = "Товар #{$id}: достигнут максимальный доступный остаток ({$stock} шт.).";
            }
        } else {
            // кладём 1 шт., но не больше остатка
            $_SESSION['cart'][$id] = 1;
        }
    }
}

// Удаление 1 шт.
if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'remove') {
    $id = (int)$_GET['id'];
    if (isset($_SESSION['cart'][$id])) {
        $_SESSION['cart'][$id]--;
        if ($_SESSION['cart'][$id] <= 0) {
            unset($_SESSION['cart'][$id]);
        }
    }
}

// Полное удаление товара
if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'delete') {
    $id = (int)$_GET['id'];
    unset($_SESSION['cart'][$id]);
}

$currentCart = $_SESSION['cart'];
$notices = $_SESSION['cart_notice'];
// очищаем уведомления, чтобы не повторялись
$_SESSION['cart_notice'] = [];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Корзина</title>
    <link rel="stylesheet" href="css/common.css">
    <link rel="stylesheet" href="css/cart.css">
    <style>
        /* легкие подсветки для остатков */
        .stock-line { font-size: 12px; color: #6b7280; }
        .stock-low { color: #b45309; }     /* мало */
        .stock-zero { color: #b91c1c; }    /* нет на складе */
        .cart-notice { margin: 8px 0 0; font-size: 13px; color: #b45309; }
        .cart-alert { padding: 10px 12px; border-radius: 10px; background:#fffbeb; border:1px solid #fde68a; color:#92400e; margin-bottom: 12px; }
    </style>
</head>
<body>


<main class="container cart-page">
    <h1 class="page-title">Ваша корзина</h1>

    <?php if (!empty($notices)): ?>
      <div class="cart-alert">
        <?php foreach ($notices as $n) echo "<div>".htmlspecialchars($n)."</div>"; ?>
      </div>
    <?php endif; ?>

    <?php
    if (!empty($currentCart)) {
        $total = 0;
        echo '<div class="cart-items">';

        foreach ($currentCart as $id => $quantity) {
            $id = (int)$id;
            $sql = "
                SELECT p.id, p.name, p.price, p.stock, c.name AS category_name
                FROM products p
                JOIN categories c ON p.category_id = c.id
                WHERE p.id = {$id}
                LIMIT 1
            ";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();

                $name   = $row['name'];
                $cat    = $row['category_name'];
                $price  = (float)$row['price'];
                $stock  = isset($row['stock']) ? (int)$row['stock'] : 0;

                // если по какой-то причине в корзине больше, чем на складе — приводим к стоку
                if ($quantity > $stock && $stock >= 0) {
                    $quantity = max(0, $stock);
                    $_SESSION['cart'][$id] = $quantity;
                }

                $subtotal = $price * $quantity;
                $total += $subtotal;

                // оформление строки
                $stockClass = 'stock-line';
                $stockText  = "В наличии: {$stock} шт.";
                if ($stock <= 0) {
                    $stockClass .= ' stock-zero';
                    $stockText = "Нет в наличии";
                } elseif ($stock <= 3) {
                    $stockClass .= ' stock-low';
                    $stockText = "Осталось мало: {$stock} шт.";
                } elseif ($quantity === $stock) {
                    $stockClass .= ' stock-low';
                    $stockText = "Достигнут максимум: {$stock} шт.";
                }

                echo "<div class='cart-item-card'>";

                echo "  <div class='cart-item-info'>";
                echo "    <div class='cart-item-name'>".htmlspecialchars($name)."</div>";
                echo "    <div class='cart-item-category'>Категория: ".htmlspecialchars($cat)."</div>";
                echo "    <div class='{$stockClass}'>".$stockText."</div>";
                echo "  </div>";

                echo "  <div class='cart-item-actions'>";
                echo "    <a href='cart.php?action=remove&id={$id}' class='qty-btn' aria-label='Убавить'>−</a>";
                echo "    <span class='qty'>{$quantity}</span>";

                // Кнопка + активна только если можем добавить
                if ($stock > $quantity) {
                    echo "    <a href='cart.php?action=add&id={$id}' class='qty-btn' aria-label='Добавить'>+</a>";
                } else {
                    echo "    <span class='qty-btn' style='opacity:.4; cursor:not-allowed;'>+</span>";
                }
                echo "  </div>";

                echo "  <div class='cart-item-price'>".number_format($subtotal, 0, ',', ' ')." ₽</div>";
                echo "  <a href='cart.php?action=delete&id={$id}' class='delete-btn' aria-label='Убрать из корзины'>×</a>";

                echo "</div>";
            }
        }
        echo '</div>';

        echo "<div class='cart-total'>Итого: <span>".number_format($total, 0, ',', ' ')." ₽</span></div>";

        // Кнопка оформления: если в корзине есть позиции с нулевым доступным количеством — можно заблокировать переход
        $hasZero = false;
        foreach ($_SESSION['cart'] as $id => $q) {
            if (get_stock($conn, (int)$id) <= 0) { $hasZero = true; break; }
        }

        if ($hasZero) {
            echo "<div class='cart-notice'>В корзине есть позиции, отсутствующие на складе. Уберите их или уменьшите количество.</div>";
            echo "<a href='order.php' class='btn btn-success' style='pointer-events:none; opacity:.6;' aria-disabled='true' title='Есть товары без наличия'>Оформить заказ</a>";
        } else {
            echo "<a href='order.php' class='btn btn-success'>Оформить заказ</a>";
        }

    } else {
        echo "<div class='empty-state'>
                <div class='empty-icon'>🛒</div>
                <div class='empty-title'>Корзина пуста</div>
                <div class='empty-text'>Добавьте товары, чтобы оформить заказ.</div>
              </div>";
    }
    ?>
</main>

<footer class="site-footer">
  <div class="container">
    © <?php echo date("Y"); ?> Магазин автозапчастей
  </div>
</footer>

<?php $conn->close(); ?>
</body>
</html>
