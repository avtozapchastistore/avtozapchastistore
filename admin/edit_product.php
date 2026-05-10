<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header("Location: index.php"); exit; }

$product = null;
$stmt = $conn->prepare("SELECT id, name, category_id, price, short_description, stock, image FROM products WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->num_rows) {
    $product = $res->fetch_assoc();
}
$stmt->close();
if (!$product) { header("Location: index.php"); exit; }

$categories = [];
$cats = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
if ($cats && $cats->num_rows > 0) while ($row = $cats->fetch_assoc()) $categories[] = $row;

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
        $image = $product['image'];

        if ($name === '' || mb_strlen($name) > 255) $errors[] = "Введите корректное название (до 255 символов).";
        if ($catId <= 0) $errors[] = "Выберите категорию.";
        if ($price < 0) $errors[] = "Цена не может быть отрицательной.";
        if ($short !== '' && mb_strlen($short) > 255) $errors[] = "Короткое описание до 255 символов.";
        if ($stock < 0) $errors[] = "Остаток не может быть отрицательным.";

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
                    if ($image && file_exists($upload_dir . $image)) {
                        @unlink($upload_dir . $image);
                    }
                    $image = $filename;
                } else {
                    $errors[] = 'Не удалось сохранить изображение.';
                }
            }
        }

        if (!$errors) {
            $stmt = $conn->prepare("UPDATE products SET name = ?, category_id = ?, price = ?, short_description = ?, stock = ?, image = ? WHERE id = ?");
            $stmt->bind_param('sidsisi', $name, $catId, $price, $short, $stock, $image, $id);
            if ($stmt->execute()) {
                $ok_msg = "Изменения сохранены.";
                $product['name'] = $name;
                $product['category_id'] = $catId;
                $product['price'] = $price;
                $product['short_description'] = $short;
                $product['stock'] = $stock;
                $product['image'] = $image;
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
        .image-preview { margin-top: 8px; max-width: 200px; }
        .image-preview img { max-width: 100%; border-radius: 8px; }
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

        <form method="POST" action="edit_product.php?id=<?php echo (int)$product['id']; ?>" enctype="multipart/form-data">
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

            <label>Изображение товара</label>
            <input type="hidden" name="MAX_FILE_SIZE" value="5242880">
            <input type="file" name="image" class="input" accept="image/jpeg,image/png,image/gif,image/webp">
            <small style="color:#666;">JPEG, PNG, GIF, WEBP. Макс. 5 МБ.</small>

            <?php if (!empty($product['image']) && file_exists(__DIR__ . '/../uploads/products/' . $product['image'])): ?>
                <div class="image-preview">
                    <p style="margin:0 0 6px;font-size:13px;color:#666;">Текущее изображение:</p>
                    <img src="../uploads/products/<?php echo htmlspecialchars($product['image']); ?>" alt="Текущее изображение">
                </div>
            <?php endif; ?>

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
