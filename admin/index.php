<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("Location: login.php"); exit;
}

// CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// ---------------------------
// Обработка действий (POST)
// ---------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $csrf) {
            throw new Exception("Недействительный CSRF-токен.");
        }

        // ----- ТОВАРЫ -----
        if (isset($_POST['product_id'])) {
            $product_id = (int)$_POST['product_id'];
            $action     = $_POST['action'] ?? '';
            if ($product_id <= 0) throw new Exception("Недействительный ID товара.");

            if ($action === 'delete') {
                $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
                $stmt->bind_param('i', $product_id);
                $stmt->execute();
            }
        }

        // ----- ЗАКАЗЫ -----
        if (isset($_POST['order_id'])) {
            $order_id = (int)$_POST['order_id'];
            $action   = $_POST['action'] ?? '';
            if ($order_id <= 0) throw new Exception("Недействительный ID заказа.");

            if ($action === 'accept') {
                $stmt = $conn->prepare("UPDATE orders SET status = 'accepted' WHERE id = ?");
                $stmt->bind_param('i', $order_id);
                $stmt->execute();
            } elseif ($action === 'delete') {
                $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
                $stmt->bind_param('i', $order_id);
                $stmt->execute();
            }
        }

        // ----- ОТЗЫВЫ -----
        if (isset($_POST['review_id'])) {
            $review_id = (int)$_POST['review_id'];
            $action    = $_POST['action'] ?? '';
            if ($review_id <= 0) throw new Exception("Недействительный ID отзыва.");

            if     ($action === 'approve') { $stmt = $conn->prepare("UPDATE reviews SET is_approved = 1 WHERE id = ?"); }
            elseif ($action === 'hide')    { $stmt = $conn->prepare("UPDATE reviews SET is_approved = 0 WHERE id = ?"); }
            elseif ($action === 'delete')  { $stmt = $conn->prepare("DELETE FROM reviews WHERE id = ?"); }
            if (isset($stmt)) { $stmt->bind_param('i', $review_id); $stmt->execute(); }
        }

        // ----- НОВОСТИ -----
        if (isset($_POST['news_id'])) {
            $news_id = (int)$_POST['news_id'];
            $action  = $_POST['action'] ?? '';
            if ($news_id <= 0) throw new Exception("Недействительный ID новости.");

            if ($action === 'delete') {
                $stmt = $conn->prepare("DELETE FROM news WHERE id = ?");
                $stmt->bind_param('i', $news_id);
                $stmt->execute();
            }
        }

        // ----- КАТЕГОРИИ -----
        if (isset($_POST['category_id'])) {
            $category_id = (int)$_POST['category_id'];
            $action      = $_POST['action'] ?? '';
            if ($category_id <= 0) throw new Exception("Недействительный ID категории.");

            if ($action === 'delete') {
                $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
                $stmt->bind_param('i', $category_id);
                if (!$stmt->execute()) {
                    $_SESSION['flash_error'] = "Невозможно удалить категорию #{$category_id}: вероятно, в ней есть товары.";
                }
            }
        }

        header("Location: index.php"); exit;

    } catch (Throwable $e) {
        error_log("Admin action error: ".$e->getMessage());
        $_SESSION['flash_error'] = "Ошибка: ".$e->getMessage();
        header("Location: index.php"); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Админ-панель</title>
  <link rel="stylesheet" href="/css/common.css">
  <link rel="stylesheet" href="/css/admin_index.css">
  <style>
    .rating { color:#f5a623; letter-spacing:1px; white-space:nowrap; }
    .nowrap { white-space:nowrap; }
    .muted { color:#666; font-size:12px; }
    .msg { max-width:520px; overflow:hidden; text-overflow:ellipsis; display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; }
    .pill { display:inline-block; padding:2px 8px; border-radius:999px; font-size:12px; border:1px solid #e5e5e5; }
    .pill-green { background:#ecfdf5; border-color:#a7f3d0; color:#065f46; }
    .pill-orange { background:#fff7ed; border-color:#fed7aa; color:#9a3412; }
    table td form { display:inline; }
    .toolbar { display:flex; gap:8px; margin:8px 0 12px; flex-wrap:wrap; }
    .notice {margin:10px 0; padding:10px 12px; border-radius:10px; font-size:14px;}
    .ok {background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46;}
    .err {background:#fef2f2; border:1px solid #fecaca; color:#991b1b;}
  </style>
</head>
<body>
<header class="site-header">
  <div class="container header-row">
    <a href="./index.php" class="brand"><span class="brand-name">Магазин автозапчастей — Админ</span></a>
    <nav class="main-nav">
      <a href="./index.php" class="nav-link">Главная</a>
      <a href="logout.php" class="nav-link">Выйти</a>
    </nav>
  </div>
</header>

<main class="admin-panel container">
  <?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="notice err"><?php echo htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
  <?php endif; ?>

  <!-- ТОВАРЫ -->
  <div class="admin-section">
    <h2>Управление товарами</h2>
    <div class="content">
      <div class="toolbar">
        <a href="add_product.php" class="btn btn-primary">Добавить товар</a>
        <a href="export_products.php" class="btn btn-ghost">Экспорт товаров (CSV)</a>
      </div>
      <table>
        <tr><th>ID</th><th>Название</th><th>Категория</th><th>Цена</th><th>Остаток</th><th>Действия</th></tr>
        <?php
        $stmt = $conn->query("SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC");
        if ($stmt && $stmt->num_rows > 0) {
          while ($row = $stmt->fetch_assoc()) {
            $stock = isset($row['stock']) ? (int)$row['stock'] : 0;
            echo "<tr>
                    <td>{$row['id']}</td>
                    <td>" . htmlspecialchars($row['name']) . "</td>
                    <td>" . htmlspecialchars($row['category_name']) . "</td>
                    <td>{$row['price']} руб.</td>
                    <td>{$stock}</td>
                    <td>
                      <a href='edit_product.php?id={$row['id']}' class='btn btn-ghost'>Редактировать</a>
                      <form method='POST' action='index.php' onsubmit="return confirm('Удалить товар &laquo;".htmlspecialchars($row['name'])."&raquo; (#{$row['id']})?');">
                        <input type='hidden' name='csrf_token' value='{$csrf}'>
                        <input type='hidden' name='product_id' value='{$row['id']}'>
                        <input type='hidden' name='action' value='delete'>
                        <button type='submit' class='btn btn-danger'>Удалить</button>
                      </form>
                    </td>
                  </tr>";
          }
        } else {
          echo "<tr><td colspan='6'>Товары не найдены</td></tr>";
        }
        ?>
      </table>
    </div>
  </div>

  <!-- КАТЕГОРИИ -->
  <div class="admin-section">
    <h2>Управление категориями</h2>
    <div class="content">
      <div class="toolbar">
        <a href="add_category.php" class="btn btn-primary">Добавить категорию</a>
      </div>
      <table>
        <tr><th>ID</th><th>Название</th><th>Действия</th></tr>
        <?php
        $stmt = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
        if ($stmt && $stmt->num_rows > 0) {
          while ($row = $stmt->fetch_assoc()) {
            $id = (int)$row['id'];
            $name = htmlspecialchars($row['name']);
            echo "<tr>
                    <td>{$id}</td>
                    <td>{$name}</td>
                    <td class='nowrap' style='display:flex;gap:.5rem;flex-wrap:wrap'>
                      <a class='btn btn-ghost' href='edit_category.php?id={$id}'>Редактировать</a>
                      <form method='POST' action='index.php' onsubmit=\"return confirm('Удалить категорию &laquo;".htmlspecialchars($row['name'])."&raquo; (#{$id})?');\">
                        <input type='hidden' name='csrf_token' value='{$csrf}'>
                        <input type='hidden' name='category_id' value='{$id}'>
                        <input type='hidden' name='action' value='delete'>
                        <button type='submit' class='btn btn-danger'>Удалить</button>
                      </form>
                    </td>
                  </tr>";
          }
        } else {
          echo "<tr><td colspan='3'>Категории не найдены</td></tr>";
        }
        ?>
      </table>
      <p class="muted" style="margin-top:8px;">Если категория используется товарами, БД может запретить удаление (связи по внешнему ключу).</p>
    </div>
  </div>

  <!-- НОВОСТИ -->
  <div class="admin-section">
    <h2>Управление новостями</h2>
    <div class="content">
      <div class="toolbar">
        <a href="add_news.php" class="btn btn-primary">Добавить новость</a>
      </div>
      <table>
        <tr><th>ID</th><th>Заголовок</th><th>Дата</th><th>Действия</th></tr>
        <?php
        $stmt = $conn->query("SELECT * FROM news ORDER BY created_at DESC");
        if ($stmt && $stmt->num_rows > 0) {
          while ($row = $stmt->fetch_assoc()) {
            $id = (int)$row['id'];
            $title = htmlspecialchars($row['title']);
            $date = date("d.m.Y H:i", strtotime($row['created_at']));
            echo "<tr>
                    <td>{$id}</td>
                    <td>{$title}</td>
                    <td>{$date}</td>
                    <td class='nowrap' style='display:flex;gap:.5rem;flex-wrap:wrap'>
                      <a class='btn btn-ghost' href='edit_news.php?id={$id}'>Редактировать</a>
                      <form method='POST' action='index.php' onsubmit=\"return confirm('Удалить новость #{$id}?');\">
                        <input type='hidden' name='csrf_token' value='{$csrf}'>
                        <input type='hidden' name='news_id' value='{$id}'>
                        <input type='hidden' name='action' value='delete'>
                        <button type='submit' class='btn btn-danger'>Удалить</button>
                      </form>
                    </td>
                  </tr>";
          }
        } else {
          echo "<tr><td colspan='4'>Новости не найдены</td></tr>";
        }
        ?>
      </table>
    </div>
  </div>

  <!-- ЗАКАЗЫ -->
  <div class="admin-section">
    <h2>Управление заказами</h2>
    <div class="content">
      <div class="toolbar">
        <a href="export_orders.php" class="btn btn-ghost">Экспорт заказов (CSV)</a>
      </div>
      <table>
        <tr><th>ID</th><th>Клиент</th><th>Адрес</th><th>Телефон</th><th>Цена</th><th>Позиции заказа</th><th>Статус</th><th>Действия</th></tr>
        <?php
        $stmt = $conn->query("SELECT id, customer_name, customer_address, phone, total, products, status, created_at FROM orders ORDER BY created_at DESC");
        if ($stmt && $stmt->num_rows > 0) {
          while ($row = $stmt->fetch_assoc()) {
            $products = json_decode($row['products'], true);
            $product_list = '';
            if (is_array($products)) {
              foreach ($products as $product) {
                $prod_id = (int)($product['id'] ?? 0);
                $quantity = (int)($product['quantity'] ?? 0);
                if ($prod_id > 0) {
                  $prod_stmt = $conn->prepare("SELECT name FROM products WHERE id = ?");
                  $prod_stmt->bind_param('i', $prod_id);
                  $prod_stmt->execute();
                  $prod_res = $prod_stmt->get_result();
                  $prod_row = $prod_res->fetch_assoc();
                  if ($prod_row) {
                    $product_list .= htmlspecialchars($prod_row['name']) . " ({$quantity} шт.), ";
                  }
                }
              }
              $product_list = rtrim($product_list, ', ');
            } else {
              $product_list = 'Нет данных';
            }

            $statusRu = $row['status'] === 'accepted' ? 'Принят' : ($row['status'] === 'cancelled' ? 'Отменён' : 'Ожидает');
            echo "<tr>
                    <td>{$row['id']}</td>
                    <td>" . htmlspecialchars($row['customer_name']) . "</td>
                    <td>" . htmlspecialchars($row['customer_address']) . "</td>
                    <td>" . htmlspecialchars($row['phone']) . "</td>
                    <td>{$row['total']} руб.</td>
                    <td>$product_list</td>
                    <td>{$statusRu}</td>
                    <td class='nowrap'>
                      <a href='edit_order.php?id={$row['id']}' class='btn btn-ghost'>Редактировать</a>
                      <form method='POST' action='index.php' style='display:inline;'>
                        <input type='hidden' name='order_id' value='{$row['id']}'>
                        <input type='hidden' name='csrf_token' value='{$csrf}'>
                        <input type='hidden' name='action' value='accept'>
                        <button type='submit' class='btn btn-primary' " . ($row['status'] === 'accepted' ? 'disabled' : '') . ">Принять</button>
                      </form>
                      <form method='POST' action='index.php' style='display:inline;' onsubmit=\"return confirm('Удалить заказ #{$row['id']}?');\">
                        <input type='hidden' name='order_id' value='{$row['id']}'>
                        <input type='hidden' name='csrf_token' value='{$csrf}'>
                        <input type='hidden' name='action' value='delete'>
                        <button type='submit' class='btn btn-danger'>Удалить</button>
                      </form>
                    </td>
                  </tr>";
          }
        } else {
          echo "<tr><td colspan='8'>Заказы не найдены</td></tr>";
        }
        ?>
      </table>
    </div>
  </div>

  <!-- ОТЗЫВЫ -->
  <div class="admin-section">
    <h2>Управление отзывами</h2>
    <div class="content">
      <?php
      $r = $conn->query("SELECT COUNT(*) AS cnt FROM reviews WHERE is_approved = 0");
      $pendingCount  = $r ? (int)$r->fetch_assoc()['cnt'] : 0;
      $r = $conn->query("SELECT COUNT(*) AS cnt FROM reviews WHERE is_approved = 1");
      $approvedCount = $r ? (int)$r->fetch_assoc()['cnt'] : 0;
      ?>
      <p class="muted">
        Ожидают модерации: <span class="pill pill-orange"><?php echo $pendingCount; ?></span>
        &nbsp;•&nbsp;
        Опубликованы: <span class="pill pill-green"><?php echo $approvedCount; ?></span>
      </p>

      <table>
        <tr>
          <th class="nowrap">ID</th>
          <th>Имя</th>
          <th>Оценка</th>
          <th>Отзыв</th>
          <th class="nowrap">Дата</th>
          <th class="nowrap">Статус</th>
          <th class="nowrap">Действия</th>
        </tr>
        <?php
        $stmt = $conn->query("
          SELECT id, name, rating, message, is_approved, created_at
          FROM reviews
          ORDER BY is_approved ASC, created_at DESC
          LIMIT 200
        ");
        if ($stmt && $stmt->num_rows > 0) {
          while ($r = $stmt->fetch_assoc()) {
            $id = (int)$r['id'];
            $name = htmlspecialchars($r['name']);
            $rating = max(1, min(5, (int)$r['rating']));
            $stars = str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
            $msg = htmlspecialchars($r['message']);
            $msgShort = mb_strimwidth($msg, 0, 300, '…', 'UTF-8');
            $date = date("d.m.Y H:i", strtotime($r['created_at']));
            $approved = (int)$r['is_approved'] === 1;

            echo "<tr>
                    <td class='nowrap'>{$id}</td>
                    <td>{$name}</td>
                    <td class='rating' aria-label='Оценка {$rating} из 5'>{$stars}</td>
                    <td><div class='msg' title='{$msg}'>{$msgShort}</div></td>
                    <td class='nowrap'>{$date}</td>
                    <td class='nowrap'>". ($approved ? "<span class='pill pill-green'>Опубликован</span>" : "<span class='pill pill-orange'>Ожидает</span>") ."</td>
                    <td class='nowrap'>
                      ". (!$approved ? "
                      <form method='POST' action='index.php'>
                        <input type='hidden' name='csrf_token' value='{$csrf}'>
                        <input type='hidden' name='review_id' value='{$id}'>
                        <input type='hidden' name='action' value='approve'>
                        <button type='submit' class='btn btn-primary'>Одобрить</button>
                      </form>" : "
                      <form method='POST' action='index.php'>
                        <input type='hidden' name='csrf_token' value='{$csrf}'>
                        <input type='hidden' name='review_id' value='{$id}'>
                        <input type='hidden' name='action' value='hide'>
                        <button type='submit' class='btn btn-ghost'>Скрыть</button>
                      </form>") ."
                      <form method='POST' action='index.php' onsubmit=\"return confirm('Удалить отзыв #{$id}?');\">
                        <input type='hidden' name='csrf_token' value='{$csrf}'>
                        <input type='hidden' name='review_id' value='{$id}'>
                        <input type='hidden' name='action' value='delete'>
                        <button type='submit' class='btn btn-danger'>Удалить</button>
                      </form>
                    </td>
                  </tr>";
          }
        } else {
          echo "<tr><td colspan='7'>Отзывы не найдены</td></tr>";
        }
        ?>
      </table>
    </div>
  </div>

</main>

<footer class="site-footer">
  <div class="container">
    <p>© <?php echo date('Y'); ?> Магазин автозапчастей</p>
  </div>
</footer>
</body>
</html>
