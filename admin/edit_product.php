<?php
// admin/edit_product.php
session_start();
require_once '../config.php'; // mysqli $conn

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("Location: login.php");
    exit;
}

// CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// ID товара
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header("Location: index.php"); exit; }

// Товар
$product = null;
$stmt = $conn->prepare("SELECT id, name, category_id, price, short_description, stock FROM products WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->num_rows) {
    $product = $res->fetch_assoc();
}
$stmt->close();
if (!$product) { header("Location: index.php"); exit; }

// Категории
$categories = [];
$cats = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
if ($cats && $cats->num_rows > 0) while ($row = $cats->fetch_assoc()) $categories[] = $row;

// POST
$errors = [];
$ok_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $csrf_token) {
        $errors[] = "Недействительный CSRF-токен.";
    } else {
        $name  = trim($_POST['name'] ?? '');
        $catId = (int)($_POST['category_id'] ?? 0);
        $price = (float)($_POST['price'] ?? 0);
        $short = trim($_POST['short_description'] ?? '');
        $stock = (int)($_POST['stock'] ?? 0);

        if ($name === '' || mb_strlen($name) > 255) $errors[] = "Введите корректное название (до 255 символов).";
        if ($catId <= 0) $errors[] = "Выберите категорию.";
        if ($price < 0) $errors[] = "Цена не может быть отрицательной.";
        if ($short !== '' && mb_strlen($short) > 255) $errors[] = "Короткое описание до 255 символов.";
        if ($stock < 0) $errors[] = "Остаток не может быть отрицательным.";

        if (!$errors) {
            $stmt = $conn->prepare("UPDATE products SET name = ?, category_id = ?, price = ?, short_description = ?, stock = ? WHERE id = ?");
            $stmt->bind_param('sidsii', $name, $catId, $price, $short, $stock, $id);
            if ($stmt->execute()) {
                $ok_msg = "Изменения сохранены.";
                // обновим локально
                $product['name'] = $name;
                $product['category_id'] = $catId;
                $product['price'] = $price;
                $product['short_description'] = $short;
                $product['stock'] = $stock;
            } else {
                $errors[] = "Ошибка при сохранении. Попробуйте ещё раз.";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактировать товар #<?php echo (int)$product['id']; ?></title>
    <link rel="stylesheet" href="/css/common.css">
    <link rel="stylesheet" href="/css/admin_form.css">
    <style>
        .notice { margin-bottom: 12px; padding: 10px 12px; border-radius: 10px; font-size: 14px; }
        .notice-ok { background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; }
        .notice-err { background:#fef2f2; border:1px solid #fecaca; color:#991b1b; }
        .admin-form textarea.input { min-height: 100px; resize: vertical; }
        .grid-2 { display:grid; grid-template-columns: 1fr 1fr; gap:12px; }
        @media (max-width:720px){ .grid-2 { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<header class="site-header">
    <div class="container header-row">
        <a href="../index.php" class="brand">
            <span class="brand-name">Магазин автозапчастей — Админ</span>
        </a>
        <nav class="main-nav">
            <a href="../index.php" class="nav-link">Главная</a>
            <a href="../cart.php" class="nav-link">Корзина</a>
            <a href="../news.php" class="nav-link">Новости</a>
            <a href="index.php" class="nav-link nav-link--ghost">Админ-панель</a>
            <a href="logout.php" class="nav-link">Выйти</a>
        </nav>
    </div>
</header>

<main class="container">
    <div class="admin-form">
        <h2>Редактировать товар #<?php echo (int)$product['id']; ?></h2>

        <?php if ($ok_msg): ?>
            <div class="notice notice-ok"><?php echo htmlspecialchars($ok_msg); ?></div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="notice notice-err">
                <?php foreach ($errors as $e) echo "<div>".htmlspecialchars($e)."</div>"; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="edit_product.php?id=<?php echo (int)$product['id']; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

            <label>Название товара</label>
            <input type="text" name="name" class="input" required maxlength="255"
                   value="<?php echo htmlspecialchars($product['name']); ?>">

            <div class="grid-2">
                <div>
                    <label>Категория</label>
                    <select name="category_id" class="input" required>
                        <option value="">— выберите категорию —</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo (int)$cat['id']; ?>"
                                <?php echo ((int)$product['category_id'] === (int)$cat['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Цена (₽)</label>
                    <input type="number" name="price" class="input" step="0.01" min="0"
                           value="<?php echo htmlspecialchars((string)$product['price']); ?>">
                </div>
            </div>

            <label>Короткое описание (до 255 символов)</label>
            <textarea name="short_description" maxlength="255" class="input"
                      placeholder="Кратко опишите товар..."><?php
                echo htmlspecialchars($product['short_description'] ?? '');
            ?></textarea>

            <label>Остаток на складе (шт.)</label>
            <input type="number" name="stock" class="input" step="1" min="0"
                   value="<?php echo htmlspecialchars((string)$product['stock']); ?>">

            <div class="form-buttons">
                <button type="submit" class="btn btn-primary">Сохранить</button>
                <a href="index.php" class="btn btn-ghost">Отмена</a>
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
<?php $conn->close(); ?>
