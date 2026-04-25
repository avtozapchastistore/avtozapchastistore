<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $conn->real_escape_string($_POST['name']);
    $price = (float)$_POST['price'];
    $category_id = (int)$_POST['category_id'];
    $sql = "INSERT INTO products (name, price, category_id) VALUES ('$name', $price, $category_id)";
    if ($conn->query($sql)) {
        header("Location: index.php");
        exit;
    } else {
        echo "<p>Ошибка при добавлении товара</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить товар</title>
    <link rel="stylesheet" href="/autozip/css/common.css">
    <link rel="stylesheet" href="/autozip/css/admin_form.css">
</head>
<body>

<!-- ===== ХЕДЕР ===== -->
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

<!-- ===== ОСНОВНОЙ КОНТЕНТ ===== -->
<main class="container">
    <div class="admin-form">
        <h2>Добавить товар</h2>
        <form method="POST" action="add_product.php">
            <label for="name">Название:</label>
            <input type="text" id="name" name="name" required>

            <label for="price">Цена:</label>
            <input type="number" id="price" name="price" step="0.01" required>

            <label>
                Короткое описание (до 255 символов)
                <textarea name="short_description" maxlength="255" class="input" placeholder="Кратко о товаре"></textarea>
            </label>


            <label for="category_id">Категория:</label>
            <select id="category_id" name="category_id" required>
                <?php
                $sql = "SELECT * FROM categories";
                $result = $conn->query($sql);
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='{$row['id']}'>{$row['name']}</option>";
                }
                ?>
            </select>

            <button type="submit" class="btn btn-primary">Добавить</button>
            <a href="index.php" class="btn btn-ghost" style="margin-left:10px;">Назад</a>
        </form>
    </div>
</main>

<!-- ===== ФУТЕР ===== -->
<footer class="site-footer">
    <div class="container">
        <p>© <?php echo date("Y"); ?> Магазин автозапчастей</p>
    </div>
</footer>

</body>
</html>
<?php $conn->close(); ?>
