<?php
session_start();
require_once 'config.php';

/* -------- вспомогательные функции -------- */

function get_stock(mysqli $conn, int $id): int {
    $res = $conn->query("SELECT stock FROM products WHERE id = {$id} LIMIT 1");
    if ($res && $res->num_rows) return (int)$res->fetch_assoc()['stock'];
    return 0;
}

function get_price(mysqli $conn, int $id): float {
    $res = $conn->query("SELECT price FROM products WHERE id = {$id} LIMIT 1");
    if ($res && $res->num_rows) return (float)$res->fetch_assoc()['price'];
    return 0.0;
}

/* -------- инициализация -------- */

if (!isset($_SESSION['cart']))        $_SESSION['cart']        = [];
if (!isset($_SESSION['cart_notice'])) $_SESSION['cart_notice'] = [];

/* -------- обработка действий (до любого вывода) -------- */

if (isset($_GET['action'], $_GET['id'])) {
    $action = $_GET['action'];
    $id     = (int)$_GET['id'];

    if ($action === 'add') {
        $stock = get_stock($conn, $id);
        if ($stock <= 0) {
            $_SESSION['cart_notice'][] = "Товар #{$id}: нет в наличии.";
        } elseif (isset($_SESSION['cart'][$id])) {
            if ($_SESSION['cart'][$id] < $stock) {
                $_SESSION['cart'][$id]++;
            } else {
                $_SESSION['cart'][$id] = $stock;
                $_SESSION['cart_notice'][] = "Товар #{$id}: достигнут максимальный доступный остаток ({$stock} шт.).";
            }
        } else {
            $_SESSION['cart'][$id] = 1;
        }
    }

    if ($action === 'remove') {
        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]--;
            if ($_SESSION['cart'][$id] <= 0) unset($_SESSION['cart'][$id]);
        }
    }

    if ($action === 'delete') {
        unset($_SESSION['cart'][$id]);
    }

    if ($action === 'set') {
        $qty = isset($_GET['qty']) ? (int)$_GET['qty'] : 0;
        if ($qty <= 0) {
            unset($_SESSION['cart'][$id]);
        } else {
            $stock = get_stock($conn, $id);
            if ($qty > $stock) {
                $qty = $stock;
                $_SESSION['cart_notice'][] = "Товар #{$id}: максимальный доступный остаток ({$stock} шт.).";
            }
            $_SESSION['cart'][$id] = $qty;
        }
        header('Location: cart.php');
        exit;
    }
}

/* -------- данные для отображения -------- */

$currentCart = $_SESSION['cart'];
$notices     = $_SESSION['cart_notice'];
$_SESSION['cart_notice'] = [];

/* -------- подключение общего header -------- */

$pageTitle = 'Корзина';
$extraCss  = ['css/cart.css'];
require __DIR__ . '/header.php';
?>
<style>
    .stock-line  { font-size: 12px; color: #6b7280; }
    .stock-low   { color: #b45309; }
    .stock-zero  { color: #b91c1c; }
    .cart-notice { margin: 8px 0 0; font-size: 13px; color: #b45309; }
    .cart-alert  { padding: 10px 12px; border-radius: 10px; background:#fffbeb; border:1px solid #fde68a; color:#92400e; margin-bottom: 12px; }
    .qty-input   { width: 56px; text-align: center; border: 1px solid #d1d5db; border-radius: 6px; padding: 3px 6px; font-size: 15px; -moz-appearance: textfield; }
    .qty-input::-webkit-outer-spin-button,
    .qty-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
</style>

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
            $id  = (int)$id;
            $sql = "SELECT p.id, p.name, p.price, p.stock, c.name AS category_name
                    FROM products p JOIN categories c ON p.category_id = c.id
                    WHERE p.id = {$id} LIMIT 1";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                $row   = $result->fetch_assoc();
                $name  = $row['name'];
                $cat   = $row['category_name'];
                $price = (float)$row['price'];
                $stock = isset($row['stock']) ? (int)$row['stock'] : 0;

                if ($quantity > $stock && $stock >= 0) {
                    $quantity = max(0, $stock);
                    $_SESSION['cart'][$id] = $quantity;
                }

                $subtotal = $price * $quantity;
                $total   += $subtotal;

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
                echo "    <input type='number' class='qty-input' value='{$quantity}' min='1' max='{$stock}' data-id='{$id}' aria-label='Количество'>";
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
<script>
document.querySelectorAll('.qty-input').forEach(function(input) {
    input.addEventListener('change', function() {
        var id  = this.dataset.id;
        var qty = parseInt(this.value, 10);
        if (isNaN(qty) || qty < 0) qty = 0;
        window.location.href = 'cart.php?action=set&id=' + id + '&qty=' + qty;
    });
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') { this.blur(); }
    });
});
</script>
</body>
</html>
