<?php
session_start();

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("Location: login.php");
    exit;
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=auto_parts_shop;charset=utf8", "root", "root");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Throwable $e) {
    die("Ошибка подключения к БД");
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header("Location: index.php"); exit; }

// Получаем заказ
$st = $pdo->prepare("SELECT id, customer_name, customer_address, phone, total, products, status, created_at FROM orders WHERE id = ?");
$st->execute([$id]);
$order = $st->fetch(PDO::FETCH_ASSOC);
if (!$order) { header("Location: index.php"); exit; }

$items = [];
if ($order['products']) {
    $decoded = json_decode($order['products'], true);
    if (is_array($decoded)) $items = $decoded;
}

// подтянем имена товаров
function fetchProductNames(PDO $pdo, array $ids): array {
    if (!$ids) return [];
    $in = implode(',', array_fill(0, count($ids), '?'));
    $st = $pdo->prepare("SELECT id, name, price FROM products WHERE id IN ($in)");
    $st->execute($ids);
    $out = [];
    while ($r = $st->fetch(PDO::FETCH_ASSOC)) $out[(int)$r['id']] = $r;
    return $out;
}
$productIds = array_values(array_unique(array_map(fn($i)=> (int)($i['id'] ?? 0), $items)));
$namesMap = fetchProductNames($pdo, $productIds);

// Обработка POST
$ok = '';
$errs = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $csrf) {
        $errs[] = "Недействительный CSRF-токен.";
    } else {
        $customer_name    = trim($_POST['customer_name'] ?? '');
        $customer_address = trim($_POST['customer_address'] ?? '');
        $phone            = trim($_POST['phone'] ?? '');
        $status           = trim($_POST['status'] ?? 'pending');

        // Кол-ва
        $qty = $_POST['qty'] ?? []; // массив: product_id => quantity
        $newItems = [];
        foreach ($qty as $pid => $q) {
            $pid = (int)$pid;
            $q   = (int)$q;
            if ($pid > 0 && $q > 0) {
                $newItems[] = ['id' => $pid, 'quantity' => $q];
            }
        }

        if ($customer_name === '') $errs[] = "Введите имя клиента.";
        if ($customer_address === '') $errs[] = "Введите адрес.";
        if ($phone === '') $errs[] = "Введите телефон.";
        if (!in_array($status, ['pending','accepted','cancelled'], true)) $status = 'pending';
        if (!$newItems) $errs[] = "Добавьте хотя бы одну позицию.";

        // Пересчёт суммы
        $total = 0.0;
        if (!$errs) {
            $ids = array_values(array_unique(array_map(fn($i)=> (int)$i['id'], $newItems)));
            $map = fetchProductNames($pdo, $ids);
            foreach ($newItems as $it) {
                $pid = (int)$it['id'];
                $q = (int)$it['quantity'];
                $price = isset($map[$pid]) ? (float)$map[$pid]['price'] : 0.0;
                $total += $price * $q;
            }
        }

        if (!$errs) {
            $productsJson = json_encode($newItems, JSON_UNESCAPED_UNICODE);
            $upd = $pdo->prepare("UPDATE orders
                SET customer_name = ?, customer_address = ?, phone = ?, status = ?, products = ?, total = ?
                WHERE id = ?");
            $upd->execute([$customer_name, $customer_address, $phone, $status, $productsJson, $total, $id]);

            $ok = "Изменения сохранены.";
            // обновим локальные данные для перерендера
            $order['customer_name'] = $customer_name;
            $order['customer_address'] = $customer_address;
            $order['phone'] = $phone;
            $order['status'] = $status;
            $order['products'] = $productsJson;
            $order['total'] = $total;

            $items = $newItems;
            $namesMap = fetchProductNames($pdo, array_values(array_unique(array_map(fn($i)=> (int)$i['id'], $items))));
        }
    }
}

$status = $order['status'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Редактировать заказ #<?php echo (int)$order['id']; ?></title>
  <link rel="stylesheet" href="/css/common.css">
  <link rel="stylesheet" href="/css/admin_form.css">
  <style>
    .notice { margin-bottom:12px; padding:10px 12px; border-radius:10px; font-size:14px; }
    .notice-ok{ background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; }
    .notice-err{ background:#fef2f2; border:1px solid #fecaca; color:#991b1b; }
    table.order-items { width:100%; border-collapse: collapse; margin: 8px 0 12px; }
    table.order-items th, table.order-items td { border:1px solid #eee; padding:8px; }
    .w-80 { width:80px; }
    .muted { color:#6b7280; font-size:12px; }
  </style>
</head>
<body>
<header class="site-header">
  <div class="container header-row">
    <a href="index.php" class="brand">
      <span class="brand-name">Админ — Редактирование заказа</span>
    </a>
    <nav class="main-nav">
      <a href="../index.php" class="nav-link">Главная</a>
      <a href="index.php" class="nav-link nav-link--ghost">Админ-панель</a>
    </nav>
  </div>
</header>

<main class="container">
  <div class="admin-form">
    <h2>Заказ #<?php echo (int)$order['id']; ?></h2>

    <?php if ($ok): ?><div class="notice notice-ok"><?php echo htmlspecialchars($ok); ?></div><?php endif; ?>
    <?php if ($errs): ?><div class="notice notice-err"><?php foreach ($errs as $e) echo "<div>".htmlspecialchars($e)."</div>"; ?></div><?php endif; ?>

    <form method="post" action="edit_order.php?id=<?php echo (int)$order['id']; ?>">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">

      <label>Имя клиента</label>
      <input type="text" name="customer_name" class="input" required value="<?php echo htmlspecialchars($order['customer_name']); ?>">

      <label>Адрес</label>
      <input type="text" name="customer_address" class="input" required value="<?php echo htmlspecialchars($order['customer_address']); ?>">

      <label>Телефон</label>
      <input type="text" name="phone" class="input" required value="<?php echo htmlspecialchars($order['phone']); ?>">

      <label>Статус</label>
      <select name="status" class="input">
        <option value="pending"   <?php echo $status==='pending'?'selected':''; ?>>Ожидает</option>
        <option value="accepted"  <?php echo $status==='accepted'?'selected':''; ?>>Принят</option>
        <option value="cancelled" <?php echo $status==='cancelled'?'selected':''; ?>>Отменён</option>
      </select>

      <h3 style="margin-top:12px;">Позиции заказа</h3>
      <table class="order-items">
        <tr><th>ID</th><th>Товар</th><th class="w-80">Кол-во</th><th>Цена</th><th>Сумма</th></tr>
        <?php
        $total = 0.0;
        foreach ($items as $it) {
            $pid = (int)$it['id'];
            $q   = (int)$it['quantity'];
            $name = isset($namesMap[$pid]) ? $namesMap[$pid]['name'] : 'Не найден';
            $price = isset($namesMap[$pid]) ? (float)$namesMap[$pid]['price'] : 0.0;
            $sum = $price * $q;
            $total += $sum;
            echo "<tr>
              <td>{$pid}</td>
              <td>".htmlspecialchars($name)."</td>
              <td><input type='number' name='qty[{$pid}]' class='input' min='0' step='1' value='{$q}'></td>
              <td>".number_format($price, 0, ',', ' ')." ₽</td>
              <td>".number_format($sum, 0, ',', ' ')." ₽</td>
            </tr>";
        }
        ?>
        <tr>
          <td colspan="4" style="text-align:right;"><strong>Итого:</strong></td>
          <td><strong><?php echo number_format($total, 0, ',', ' '); ?> ₽</strong></td>
        </tr>
      </table>
      <div class="muted">Поставьте количество 0, чтобы удалить позицию.</div>

      <div class="form-buttons" style="margin-top:12px;">
        <button type="submit" class="btn btn-primary">Сохранить</button>
        <a href="index.php" class="btn btn-ghost">Назад</a>
      </div>
    </form>
  </div>
</main>

<footer class="site-footer">
  <div class="container">
    &copy; <?php echo date('Y'); ?> Магазин автозапчастей
  </div>
</footer>
</body>
</html>
