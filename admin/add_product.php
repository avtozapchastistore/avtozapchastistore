<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("Location: login.php");
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? '');
    $price = (float)$_POST['price'];
    $category_id = (int)$_POST['category_id'];
    $short_description = trim($_POST['short_description'] ?? '');
    $stock = (int)($_POST['stock'] ?? 0);
    $image = '';

    if ($name === '') $errors[] = 'Введите название товара.';
    if ($price < 0) $errors[] = 'Цена не может быть отрицательной.';
    if ($category_id <= 0) $errors[] = 'Выберите категорию.';

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $mime = mime_content_type($_FILES['image']['tmp_name']);
        if (!in_array($mime, $allowed)) {
            $errors[] = 'Разрешены только изображения: JPEG, PNG, GIF, WEBP.';
        } else {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('product_') . '.' . $ext;
            $upload_dir = __DIR__ . '/../uploads/products/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $dest = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                $image = $filename;
            } else {
                $errors[] = 'Не удалось сохранить изображение.';
            }
        }
    } elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $errors[] = 'Ошибка загрузки изображения.';
    }

    if (!$errors) {
        $stmt = $conn->prepare("INSERT INTO products (name, price, category_id, short_description, stock, image) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('sdisis', $name, $price, $category_id, $short_description, $stock, $image);
        if ($stmt->execute()) {
            header("Location: index.php");
            exit;
        } else {
            $errors[] = 'Ошибка при добавлении товара.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить товар</title>
    <link rel="stylesheet" href="/css/common.css">
    <link rel="stylesheet" href="/css/admin_form.css">
    <style>
        .notice { margin-bottom: 12px; padding: 10px 12px; border-radius: 10px; font-size: 14px; }
        .notice-err { background:#fef2f2; border:1px solid #fecaca; color:#991b1b; }
        .admin-form textarea.input { min-height: 100px; resize: vertical; }
        .grid-2 { display:grid; grid-template-columns: 1fr 1fr; gap:12px; }
        @media (max-width:720px){ .grid-2 { grid-template-columns: 1fr; } }
        .file-preview { margin-top: 8px; max-width: 200px; }
        .file-preview img { max-width: 100%; border-radius: 8px; }
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
        <h2>Добавить товар</h2>

        <?php if ($errors): ?>
            <div class="notice notice-err">
                <?php foreach ($errors as $e) echo '<div>' . htmlspecialchars($e) . '</div>'; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="add_product.php" enctype="multipart/form-data">
            <label>Название товара</label>
            <input type="text" name="name" class="input" required maxlength="255" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">

            <div class="grid-2">
                <div>
                    <label>Категория</label>
                    <select name="category_id" class="input" required>
                        <option value="">— выберите категорию —</option>
                        <?php
                        $sql = "SELECT * FROM categories";
                        $result = $conn->query($sql);
                        while ($row = $result->fetch_assoc()) {
                            $sel = ((int)($category_id ?? 0) === (int)$row['id']) ? 'selected' : '';
                            echo "<option value='{$row['id']}' $sel>{$row['name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div>
                    <label>Цена (₽)</label>
                    <input type="number" name="price" class="input" step="0.01" min="0" required
                           value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>">
                </div>
            </div>

            <label>Короткое описание (до 255 символов)</label>
            <textarea name="short_description" maxlength="255" class="input"
                      placeholder="Кратко опишите товар..."><?php echo htmlspecialchars($_POST['short_description'] ?? ''); ?></textarea>

            <label>Остаток на складе (шт.)</label>
            <input type="number" name="stock" class="input" step="1" min="0"
                   value="<?php echo htmlspecialchars($_POST['stock'] ?? '0'); ?>">

            <label>Изображение товара</label>
            <input type="hidden" name="MAX_FILE_SIZE" value="5242880">
            <input type="file" name="image" class="input" accept="image/jpeg,image/png,image/gif,image/webp">
            <small style="color:#666;">JPEG, PNG, GIF, WEBP. Макс. 5 МБ.</small>

            <div class="form-buttons">
                <button type="submit" class="btn btn-primary">Добавить</button>
                <a href="index.php" class="btn btn-ghost">Отмена</a>
            </div>
        </form>
    </div>
</main>

<footer class="site-footer">
    <div class="container">
        <p>© <?php echo date("Y"); ?> Магазин автозапчастей</p>
    </div>
</footer>

</body>
</html>
<?php $conn->close(); ?>
